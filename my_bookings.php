<?php
/**
 * HERITAGE COASTAL VILLA - GUEST DASHBOARD (my_bookings.php)
 * View and manage personal bookings and add-on experiences (LKR Primary + USD Secondary).
 */
define('PAGE_TITLE', 'My Reservations');
require_once __DIR__ . '/config/db.php';

$exchangeRate = defined('USD_TO_LKR') ? (float)USD_TO_LKR : 320.0;

function showDualCurrency($amountUSD, $rate) {
    $usd = (float)$amountUSD;
    $lkr = $usd * $rate;
    return '$' . number_format($usd, 2) . ' (Rs. ' . number_format($lkr, 2) . ')';
}

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: login.php?msg=login_required");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Handle Guest Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    requireCsrf();
    $cancelId = (int)$_POST['cancel_id'];
    $stmtCancel = $pdo->prepare("
        UPDATE bookings
        SET booking_status = 'cancelled'
        WHERE id = :id AND user_id = :user_id AND booking_status = 'pending'
    ");
    $stmtCancel->execute([':id' => $cancelId, ':user_id' => $userId]);

    if ($stmtCancel->rowCount() > 0) {
        $_SESSION['msg_success'] = "Reservation " . bookingRef($cancelId) . " has been successfully cancelled.";
    } else {
        $_SESSION['msg_error'] = "That reservation could not be cancelled (it may no longer be pending).";
    }
    header("Location: my_bookings.php");
    exit;
}

// Fetch all bookings for this user
$stmtBookings = $pdo->prepare("
    SELECT b.*, r.room_name, r.room_type, r.image_url
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.user_id = :user_id
    ORDER BY b.created_at DESC
");
$stmtBookings->execute([':user_id' => $userId]);
$bookings = $stmtBookings->fetchAll();

// Fetch all attached services
$servicesByBooking = [];
if (!empty($bookings)) {
    $ids = array_column($bookings, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtBS = $pdo->prepare("
        SELECT bs.booking_id, s.service_name, s.category, bs.service_price
        FROM booking_services bs
        JOIN services s ON bs.service_id = s.id
        WHERE bs.booking_id IN ($placeholders)
    ");
    $stmtBS->execute($ids);
    foreach ($stmtBS->fetchAll() as $row) {
        $servicesByBooking[$row['booking_id']][] = $row;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 40px; min-height: 500px;">
    <div class="section-header" style="margin-bottom: 25px;">
        <span style="font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--color-olive); font-weight: 700;">Guest Portal</span>
        <h2 class="section-title">My Luxury Itineraries</h2>
        <div class="batik-divider"></div>
        <p class="section-subtitle">Welcome back, <?php echo e($_SESSION['user_name'] ?? 'Valued Guest'); ?>. Review your active reservations and chosen experiences.</p>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['booking_success'])): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> <?php echo e($_SESSION['booking_success']); unset($_SESSION['booking_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['msg_success'])): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> <?php echo e($_SESSION['msg_success']); unset($_SESSION['msg_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['msg_error'])): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($_SESSION['msg_error']); unset($_SESSION['msg_error']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div style="background: #ffffff; border: 1px solid var(--color-sand-border); padding: 50px 20px; text-align: center; border-radius: 4px;">
            <i class="fa-solid fa-calendar-xmark" style="font-size: 40px; color: var(--color-accent-gold); margin-bottom: 15px;"></i>
            <h3>No Reservations Found</h3>
            <p style="color: var(--color-text-muted); margin-bottom: 20px;">You haven't made any reservations yet. Indulge in our tropical coastal retreat today.</p>
            <a href="book.php" class="btn btn-gold"><i class="fa-solid fa-calendar-plus"></i> Book a Suite</a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Booking Ref</th>
                        <th>Suite Name</th>
                        <th>Dates & Duration</th>
                        <th>Add-on Experiences & Wellness</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <?php
                        $attachedServices = $servicesByBooking[$b['id']] ?? [];
                        $nights = (new DateTime($b['check_in']))->diff(new DateTime($b['check_out']))->days;
                        ?>
                        <tr>
                            <td><strong><?php echo bookingRef($b['id']); ?></strong></td>
                            <td>
                                <strong><?php echo e($b['room_name']); ?></strong><br>
                                <small style="color: var(--color-text-muted);"><?php echo e($b['room_type']); ?> &bull; <?php echo (int)$b['num_guests']; ?> Guest(s)</small>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($b['check_in'])); ?> &rarr; <?php echo date('M d, Y', strtotime($b['check_out'])); ?><br>
                                <small style="color: var(--color-olive); font-weight:600;"><?php echo $nights; ?> Night(s)</small>
                            </td>
                            <td>
                                <?php if (!empty($attachedServices)): ?>
                                    <ul style="padding-left: 15px; margin: 0; font-size: 12px; color: var(--color-text-dark);">
                                        <?php foreach ($attachedServices as $as): ?>
                                            <li>
                                                <?php echo e($as['service_name']); ?>
                                                <small style="color:var(--color-accent-gold);">(<?php echo showDualCurrency((float)$as['service_price'], $exchangeRate); ?>)</small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <em style="font-size: 12px; color: var(--color-text-muted);">Suite only (No add-ons)</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="font-size: 15px; color: var(--color-primary);"><?php echo showDualCurrency((float)$b['grand_total'], $exchangeRate); ?></strong>
                                <?php if ((float)$b['discount_amount'] > 0): ?>
                                    <br><small style="color: var(--color-olive); font-weight:600;">Includes long-stay saving of <?php echo showDualCurrency((float)$b['discount_amount'], $exchangeRate); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo e($b['booking_status']); ?>">
                                    <?php echo ucfirst(e($b['booking_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($b['booking_status'] === 'pending'): ?>
                                    <form method="POST" action="my_bookings.php" style="margin:0;"
                                          onsubmit="return confirm('Are you sure you want to cancel reservation <?php echo bookingRef($b['id']); ?>?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="cancel_id" value="<?php echo (int)$b['id']; ?>">
                                        <button type="submit" class="btn btn-outline btn-sm"
                                                style="color: var(--color-danger); border-color: var(--color-danger); font-size:10px; cursor:pointer;">
                                            <i class="fa-solid fa-xmark"></i> Cancel
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--color-text-muted);">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
