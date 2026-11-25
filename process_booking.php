<?php
/**
 * HERITAGE COASTAL VILLA - BOOKING PROCESSOR (process_booking.php)
 * Handles Server-Side Validation, Date Overlap Check, Calculations & Transactions
 */
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: book.php");
    exit;
}

requireCsrf();

// 1. Retrieve Input
$roomId           = (int)($_POST['room_id'] ?? 0);
$checkIn          = trim($_POST['check_in'] ?? '');
$checkOut         = trim($_POST['check_out'] ?? '');
$numGuests        = (int)($_POST['num_guests'] ?? 1);
$specialRequests  = trim($_POST['special_requests'] ?? '');
$agreePolicies    = isset($_POST['agree_policies']) && $_POST['agree_policies'] === '1';
$selectedServices = $_POST['services'] ?? [];
if (!is_array($selectedServices)) {
    $selectedServices = [];
}
// Keep only unique positive integer IDs
$selectedServices = array_values(array_unique(array_filter(array_map('intval', $selectedServices), fn($v) => $v > 0)));

// 2. Guests must be logged in (prevents anyone booking into someone else's account).
//    Save the form so it can be restored after login.
if (!isLoggedIn()) {
    $_SESSION['booking_draft'] = [
        'room_id'          => $roomId,
        'check_in'         => $checkIn,
        'check_out'        => $checkOut,
        'num_guests'       => $numGuests,
        'services'         => $selectedServices,
        'special_requests' => $specialRequests,
    ];
    header("Location: login.php?msg=booking_login");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Helper to go back to the form with an error and keep the user's selections
function bookingFail($message, $roomId = 0) {
    global $checkIn, $checkOut, $numGuests, $selectedServices, $specialRequests;
    $_SESSION['booking_error'] = $message;
    $_SESSION['booking_draft'] = [
        'room_id'          => $roomId,
        'check_in'         => $checkIn,
        'check_out'        => $checkOut,
        'num_guests'       => $numGuests,
        'services'         => $selectedServices,
        'special_requests' => $specialRequests,
    ];
    header("Location: book.php");
    exit;
}

// 3. Validation
if (!$agreePolicies) {
    bookingFail("You must agree to the Villa Policies & House Rules to proceed with your booking.", $roomId);
}
if ($roomId <= 0 || $checkIn === '' || $checkOut === '') {
    bookingFail("Please fill in all required fields.", $roomId);
}
if (mb_strlen($specialRequests) > 1000) {
    bookingFail("Special requests are too long (max 1000 characters).", $roomId);
}

$dateIn  = parseDate($checkIn);
$dateOut = parseDate($checkOut);
if (!$dateIn || !$dateOut) {
    bookingFail("Please choose valid check-in and check-out dates.", $roomId);
}

$today = new DateTime('today');
if ($dateIn < $today) {
    bookingFail("Check-in date cannot be in the past.", $roomId);
}
if ($dateOut <= $dateIn) {
    bookingFail("Check-out date must be at least one day after check-in.", $roomId);
}

$nights = $dateIn->diff($dateOut)->days;
if ($nights > 60) {
    bookingFail("Online bookings are limited to 60 nights. Please contact the villa for longer stays.", $roomId);
}
if ($numGuests < 1) {
    bookingFail("Please select at least 1 guest.", $roomId);
}

try {
    $pdo->beginTransaction();

    // 4. Lock the room row so two people cannot book the same room at the same moment
    $stmtRoom = $pdo->prepare("SELECT * FROM rooms WHERE id = :id AND status = 'available' FOR UPDATE");
    $stmtRoom->execute([':id' => $roomId]);
    $room = $stmtRoom->fetch();

    if (!$room) {
        $pdo->rollBack();
        bookingFail("The selected suite is currently unavailable.", 0);
    }

    if ($numGuests > (int)$room['max_guests']) {
        $pdo->rollBack();
        bookingFail($room['room_name'] . " allows a maximum of " . (int)$room['max_guests'] . " guests.", $roomId);
    }

    // 5. Prevent overlapping bookings (checked INSIDE the transaction, after the lock)
    $stmtOverlap = $pdo->prepare("
        SELECT id FROM bookings
        WHERE room_id = :room_id
          AND booking_status IN ('pending', 'confirmed')
          AND NOT (check_out <= :new_check_in OR check_in >= :new_check_out)
        LIMIT 1
    ");
    $stmtOverlap->execute([
        ':room_id'       => $roomId,
        ':new_check_in'  => $checkIn,
        ':new_check_out' => $checkOut
    ]);

    if ($stmtOverlap->fetch()) {
        $pdo->rollBack();
        bookingFail("Sorry, " . $room['room_name'] . " is already booked for the selected dates. Please choose different dates or select another suite.", $roomId);
    }

    // 6. Calculate totals securely on the server
    $roomTotal      = (float)$room['price_per_night'] * $nights;   // before discount
    $discountPct    = discountPercent($nights);
    $discountAmount = round($roomTotal * $discountPct / 100, 2);   // long-stay discount on the suite only
    $servicesTotal = 0.00;
    $validServicesToInsert = [];

    if (!empty($selectedServices)) {
        $placeholders = implode(',', array_fill(0, count($selectedServices), '?'));
        $stmtSrv = $pdo->prepare("SELECT id, price FROM services WHERE id IN ($placeholders) AND status = 'active'");
        $stmtSrv->execute($selectedServices);

        foreach ($stmtSrv->fetchAll() as $fs) {
            $servicesTotal += (float)$fs['price'];
            $validServicesToInsert[] = [
                'service_id'    => $fs['id'],
                'service_price' => $fs['price']
            ];
        }
    }

    $netSubtotal = $roomTotal - $discountAmount + $servicesTotal;
    $vatAmount   = round($netSubtotal * (defined('VAT_RATE') ? (float)VAT_RATE : 0.08), 2);
    $grandTotal  = $netSubtotal + $vatAmount;

    // 7. Insert booking
    $stmtBooking = $pdo->prepare("
        INSERT INTO bookings (user_id, room_id, check_in, check_out, num_guests, room_total, discount_amount, services_total, grand_total, booking_status, special_requests)
        VALUES (:user_id, :room_id, :check_in, :check_out, :num_guests, :room_total, :discount_amount, :services_total, :grand_total, 'pending', :special_requests)
    ");
    $stmtBooking->execute([
        ':user_id'          => $userId,
        ':room_id'          => $roomId,
        ':check_in'         => $checkIn,
        ':check_out'        => $checkOut,
        ':num_guests'       => $numGuests,
        ':room_total'       => $roomTotal,
        ':discount_amount'  => $discountAmount,
        ':services_total'   => $servicesTotal,
        ':grand_total'      => $grandTotal,
        ':special_requests' => $specialRequests
    ]);
    $bookingId = $pdo->lastInsertId();

    if (!empty($validServicesToInsert)) {
        $stmtInsertBS = $pdo->prepare("
            INSERT INTO booking_services (booking_id, service_id, service_price)
            VALUES (:booking_id, :service_id, :service_price)
        ");
        foreach ($validServicesToInsert as $bs) {
            $stmtInsertBS->execute([
                ':booking_id'    => $bookingId,
                ':service_id'    => $bs['service_id'],
                ':service_price' => $bs['service_price']
            ]);
        }
    }

    $pdo->commit();

    unset($_SESSION['booking_draft']);
    $_SESSION['booking_success'] = "Congratulations! Your reservation for " . $room['room_name'] . " (Reference " . bookingRef($bookingId) . ") has been received successfully.";
    header("Location: my_bookings.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Booking failed: ' . $e->getMessage());
    bookingFail("We could not complete your reservation due to a system error. Please try again.", $roomId);
}
