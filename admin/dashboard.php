<?php
/**
 * HERITAGE COASTAL VILLA - ADMIN DASHBOARD (admin/dashboard.php)
 * Central Administration for Reservations, Status Management & Revenue
 */
require_once __DIR__ . '/../config/db.php';

// Access Control: Admin Only
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php?msg=login_required");
    exit;
}

// Allowed status changes: current status => statuses it may move to
$transitions = [
    'pending'   => ['confirmed', 'cancelled'],
    'confirmed' => ['completed', 'cancelled'],
    'cancelled' => [],
    'completed' => [],
];

// Handle Status Updates (POST + CSRF + valid transition + overlap check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    requireCsrf();
    $bookingId = (int)$_POST['booking_id'];
    $action    = (string)$_POST['action'];

    $stmtCur = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
    $stmtCur->execute([':id' => $bookingId]);
    $current = $stmtCur->fetch();

    if (!$current) {
        $_SESSION['admin_error'] = "Booking not found.";
    } elseif (!in_array($action, $transitions[$current['booking_status']] ?? [], true)) {
        $_SESSION['admin_error'] = "A " . $current['booking_status'] . " booking cannot be changed to " . $action . ".";
    } else {
        $ok = true;
        if ($action === 'confirmed') {
            // Make sure no other active booking overlaps this one
            $stmtOv = $pdo->prepare("
                SELECT id FROM bookings
                WHERE room_id = :room_id AND id <> :id
                  AND booking_status = 'confirmed'
                  AND NOT (check_out <= :ci OR check_in >= :co)
                LIMIT 1
            ");
            $stmtOv->execute([
                ':room_id' => $current['room_id'],
                ':id'      => $bookingId,
                ':ci'      => $current['check_in'],
                ':co'      => $current['check_out'],
            ]);
            if ($stmtOv->fetch()) {
                $ok = false;
                $_SESSION['admin_error'] = "Cannot confirm " . bookingRef($bookingId) . ": the room is already confirmed for overlapping dates.";
            }
        }
        if ($ok) {
            $stmtUpdate = $pdo->prepare("UPDATE bookings SET booking_status = :status WHERE id = :id");
            $stmtUpdate->execute([':status' => $action, ':id' => $bookingId]);
            $_SESSION['admin_msg'] = "Booking " . bookingRef($bookingId) . " marked as " . ucfirst($action) . ".";
        }
    }
    header("Location: dashboard.php");
    exit;
}

// 1. Calculate Metrics
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue  = $pdo->query("SELECT SUM(grand_total) FROM bookings WHERE booking_status IN ('confirmed', 'completed')")->fetchColumn() ?: 0.00;
$pendingCount  = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'")->fetchColumn();
$roomsCount    = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();

// 2. Optional status filter
$filter = $_GET['status'] ?? 'all';
$validFilters = ['all', 'pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($filter, $validFilters, true)) {
    $filter = 'all';
}

// 3. Fetch Bookings with User and Room details
$sql = "
    SELECT b.*, u.full_name, u.email, u.phone, r.room_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
";
$params = [];
if ($filter !== 'all') {
    $sql .= " WHERE b.booking_status = :status";
    $params[':status'] = $filter;
}
$sql .= " ORDER BY b.created_at DESC";
$stmtAllBookings = $pdo->prepare($sql);
$stmtAllBookings->execute($params);
$bookings = $stmtAllBookings->fetchAll();

// 4. Fetch all services for these bookings in ONE query
$servicesByBooking = [];
if (!empty($bookings)) {
    $ids = array_column($bookings, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtBS = $pdo->prepare("
        SELECT bs.booking_id, s.service_name, bs.service_price
        FROM booking_services bs
        JOIN services s ON bs.service_id = s.id
        WHERE bs.booking_id IN ($placeholders)
    ");
    $stmtBS->execute($ids);
    foreach ($stmtBS->fetchAll() as $row) {
        $servicesByBooking[$row['booking_id']][] = $row;
    }
}

// Small helper to print a POST action button
function statusButton($bookingId, $action, $classes, $style, $title, $icon, $confirmText = '') {
    $onsubmit = $confirmText !== '' ? ' onsubmit="return confirm(\'' . e($confirmText) . '\');"' : '';
    echo '<form method="POST" action="dashboard.php" style="margin:0;"' . $onsubmit . '>';
    echo csrfField();
    echo '<input type="hidden" name="booking_id" value="' . (int)$bookingId . '">';
    echo '<input type="hidden" name="action" value="' . e($action) . '">';
    echo '<button type="submit" class="btn btn-sm ' . e($classes) . '" style="' . e($style) . ' cursor:pointer;" title="' . e($title) . '"><i class="fa-solid ' . e($icon) . '"></i></button>';
    echo '</form>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo SITE_NAME; ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: #f4efe6;">

<!-- Admin Navbar -->
<nav class="navbar" style="background:#111111; border-bottom: 2px solid var(--color-accent-gold);">
    <div class="container nav-container">
        <a href="dashboard.php" class="brand-logo" style="display: flex; flex-direction: row; align-items: center; gap: 12px; text-decoration: none;">
            <img src="../assets/images/logo.jpg" alt="<?php echo SITE_NAME; ?>" style="height: 42px; width: auto; object-fit: contain; background: #fff; padding: 2px; border-radius: 4px;">
            <div style="display: flex; flex-direction: column;">
                <span class="logo-title" style="color: #ffffff;"><?php echo SITE_NAME; ?></span>
                <span class="logo-subtitle" style="color: var(--color-accent-gold);">Administration Portal</span>
            </div>
        </a>

        <ul class="nav-links">
            <li><a href="dashboard.php" class="active" style="color:#ffffff;"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="rooms.php" style="color:#ffffff;"><i class="fa-solid fa-bed"></i> Manage Rooms</a></li>
            <li><a href="services.php" style="color:#ffffff;"><i class="fa-solid fa-spa"></i> Manage Experiences</a></li>
            <li><a href="../index.php" target="_blank" style="color:#ffffff;"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Website</a></li>
            <li><a href="../logout.php" style="color: #ff6b6b;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container" style="margin-top: 35px; min-height: 600px;">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <div>
            <h2 style="font-size: 24px;">Management Console</h2>
            <p style="font-size: 13px; color: var(--color-text-muted);">Overview of guest reservations, occupancy, and experience add-ons.</p>
        </div>
        <div>
            <a href="rooms.php" class="btn btn-gold btn-sm"><i class="fa-solid fa-plus"></i> New Room</a>
            <a href="services.php" class="btn btn-olive btn-sm"><i class="fa-solid fa-plus"></i> New Service</a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['admin_msg'])): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> <?php echo e($_SESSION['admin_msg']); unset($_SESSION['admin_msg']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($_SESSION['admin_error']); unset($_SESSION['admin_error']); ?>
        </div>
    <?php endif; ?>

    <!-- Stat Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px;">
        <div style="background: #ffffff; padding: 22px; border-radius: 4px; border-left: 4px solid var(--color-accent-gold); box-shadow: var(--shadow-soft);">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--color-text-muted); font-weight:600;">Total Bookings</span>
            <h3 style="font-size: 28px; margin-top: 8px;"><?php echo (int)$totalBookings; ?></h3>
        </div>

        <div style="background: #ffffff; padding: 22px; border-radius: 4px; border-left: 4px solid var(--color-success); box-shadow: var(--shadow-soft);">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--color-text-muted); font-weight:600;">Confirmed Revenue</span>
            <h3 style="font-size: 28px; margin-top: 8px; color: var(--color-success);"><?php echo formatPrice($totalRevenue); ?></h3>
        </div>

        <div style="background: #ffffff; padding: 22px; border-radius: 4px; border-left: 4px solid #f39c12; box-shadow: var(--shadow-soft);">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--color-text-muted); font-weight:600;">Pending Approvals</span>
            <h3 style="font-size: 28px; margin-top: 8px; color: #d35400;"><?php echo (int)$pendingCount; ?></h3>
        </div>

        <div style="background: #ffffff; padding: 22px; border-radius: 4px; border-left: 4px solid var(--color-olive); box-shadow: var(--shadow-soft);">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--color-text-muted); font-weight:600;">Active Suites</span>
            <h3 style="font-size: 28px; margin-top: 8px; color: var(--color-olive);"><?php echo (int)$roomsCount; ?></h3>
        </div>
    </div>

    <!-- Bookings Table -->
    <div style="background: #ffffff; padding: 25px; border-radius: 4px; box-shadow: var(--shadow-soft);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom: 20px;">
            <h3 style="font-size: 18px; color: var(--color-primary);">
                <i class="fa-solid fa-clipboard-list" style="color:var(--color-accent-gold);"></i> All Guest Reservations & Add-ons
            </h3>
            <form method="GET" action="dashboard.php" style="display:flex; gap:8px; align-items:center; margin:0;">
                <label for="status_filter" style="font-size:12px; color:var(--color-text-muted);">Filter:</label>
                <select id="status_filter" name="status" class="form-control" style="width:auto; padding:6px 10px;" onchange="this.form.submit()">
                    <?php foreach ($validFilters as $f): ?>
                        <option value="<?php echo e($f); ?>" <?php echo $f === $filter ? 'selected' : ''; ?>><?php echo ucfirst($f); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (empty($bookings)): ?>
            <p style="color: var(--color-text-muted); text-align: center; padding: 30px;">No bookings found.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="custom-table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Guest Details</th>
                            <th>Suite</th>
                            <th>Dates</th>
                            <th>Chosen Experiences & Services</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <?php $servicesList = $servicesByBooking[$b['id']] ?? []; ?>
                            <tr>
                                <td><strong><?php echo bookingRef($b['id']); ?></strong></td>
                                <td>
                                    <strong><?php echo e($b['full_name']); ?></strong><br>
                                    <small><?php echo e($b['email']); ?></small><br>
                                    <small style="color:var(--color-olive);"><?php echo e($b['phone']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo e($b['room_name']); ?></strong><br>
                                    <small><?php echo (int)$b['num_guests']; ?> Guest(s)</small>
                                </td>
                                <td>
                                    <?php echo date('M d', strtotime($b['check_in'])); ?> &rarr; <?php echo date('M d, Y', strtotime($b['check_out'])); ?>
                                </td>
                                <td>
                                    <?php if (!empty($servicesList)): ?>
                                        <ul style="padding-left: 14px; margin: 0; font-size: 11px;">
                                            <?php foreach ($servicesList as $sl): ?>
                                                <li><?php echo e($sl['service_name']); ?> (<?php echo formatPrice($sl['service_price']); ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <em style="font-size: 11px; color: var(--color-text-muted);">None</em>
                                    <?php endif; ?>

                                    <?php if (!empty($b['special_requests'])): ?>
                                        <div style="font-size: 11px; color: #856404; background:#fff9e6; padding: 4px; margin-top: 5px; border-radius: 2px;">
                                            <strong>Note:</strong> <?php echo e($b['special_requests']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo formatPrice($b['grand_total']); ?></strong>
                                    <?php if ((float)$b['discount_amount'] > 0): ?>
                                        <br><small style="color: var(--color-olive);">Discount: <?php echo formatPrice($b['discount_amount']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo e($b['booking_status']); ?>">
                                        <?php echo ucfirst(e($b['booking_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php $allowed = $transitions[$b['booking_status']] ?? []; ?>

                                        <?php if (in_array('confirmed', $allowed, true)): ?>
                                            <?php statusButton($b['id'], 'confirmed', 'btn-gold', 'padding: 4px 8px; font-size: 10px;', 'Confirm Booking', 'fa-check'); ?>
                                        <?php endif; ?>

                                        <?php if (in_array('completed', $allowed, true)): ?>
                                            <?php statusButton($b['id'], 'completed', 'btn-olive', 'padding: 4px 8px; font-size: 10px;', 'Mark Completed', 'fa-flag-checkered'); ?>
                                        <?php endif; ?>

                                        <?php if (in_array('cancelled', $allowed, true)): ?>
                                            <?php statusButton($b['id'], 'cancelled', '', 'padding: 4px 8px; font-size: 10px; background:#b33939; color:#fff;', 'Cancel Booking', 'fa-ban', 'Cancel this reservation?'); ?>
                                        <?php endif; ?>

                                        <?php if (empty($allowed)): ?>
                                            <span style="font-size: 11px; color: var(--color-text-muted);">&mdash;</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
