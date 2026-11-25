<?php
/**
 * HERITAGE COASTAL VILLA - ROOM CRUD MANAGEMENT (admin/rooms.php)
 * Add, edit, delete, and view all villa accommodations.
 */
require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$editRoom = null;

// Handle Delete Room (POST + CSRF). Rooms that have bookings cannot be deleted (history is kept).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    requireCsrf();
    $delId = (int)$_POST['delete_id'];

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = :id");
    $stmtCount->execute([':id' => $delId]);

    if ((int)$stmtCount->fetchColumn() > 0) {
        $_SESSION['room_error'] = "This room has booking history and cannot be deleted. Set its status to 'Maintenance' instead to hide it from guests.";
    } else {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM rooms WHERE id = :id");
            $stmtDel->execute([':id' => $delId]);
            $_SESSION['room_msg'] = "Room deleted successfully.";
        } catch (PDOException $ex) {
            error_log('Room delete failed: ' . $ex->getMessage());
            $_SESSION['room_error'] = "The room could not be deleted.";
        }
    }
    header("Location: rooms.php");
    exit;
}

// Handle Form Submission (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_name'])) {
    requireCsrf();

    $roomId      = (int)($_POST['room_id'] ?? 0);
    $roomName    = trim($_POST['room_name'] ?? '');
    $roomType    = trim($_POST['room_type'] ?? '');
    $price       = (float)($_POST['price_per_night'] ?? 0);
    $maxGuests   = (int)($_POST['max_guests'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $imageUrl    = trim($_POST['image_url'] ?? '');
    $status      = $_POST['status'] ?? 'available';

    $error = '';
    if ($roomName === '' || mb_strlen($roomName) > 100) {
        $error = "Room name is required (max 100 characters).";
    } elseif ($roomType === '' || mb_strlen($roomType) > 50) {
        $error = "Room type is required (max 50 characters).";
    } elseif ($price <= 0 || $price > 99999999) {
        $error = "Rate per night must be greater than 0.";
    } elseif ($maxGuests < 1 || $maxGuests > 20) {
        $error = "Max guests must be between 1 and 20.";
    } elseif (!in_array($status, ['available', 'maintenance'], true)) {
        $error = "Invalid status.";
    } elseif (mb_strlen($imageUrl) > 255) {
        $error = "Image path/URL is too long (max 255 characters).";
    } else {
        // Duplicate name check
        $stmtDup = $pdo->prepare("SELECT id FROM rooms WHERE room_name = :name AND id <> :id LIMIT 1");
        $stmtDup->execute([':name' => $roomName, ':id' => $roomId]);
        if ($stmtDup->fetch()) {
            $error = "Another room already uses that name.";
        }
    }

    if ($error !== '') {
        $_SESSION['room_error'] = $error;
        header("Location: rooms.php" . ($roomId > 0 ? "?edit=" . $roomId : ""));
        exit;
    }

    if ($roomId > 0) {
        $stmtUpdate = $pdo->prepare("
            UPDATE rooms
            SET room_name = :name, room_type = :type, price_per_night = :price, max_guests = :guests, description = :desc, image_url = :img, status = :status
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':name'   => $roomName,
            ':type'   => $roomType,
            ':price'  => $price,
            ':guests' => $maxGuests,
            ':desc'   => $description,
            ':img'    => $imageUrl,
            ':status' => $status,
            ':id'     => $roomId
        ]);
        $_SESSION['room_msg'] = "Room updated successfully.";
    } else {
        $stmtInsert = $pdo->prepare("
            INSERT INTO rooms (room_name, room_type, price_per_night, max_guests, description, image_url, status)
            VALUES (:name, :type, :price, :guests, :desc, :img, :status)
        ");
        $stmtInsert->execute([
            ':name'   => $roomName,
            ':type'   => $roomType,
            ':price'  => $price,
            ':guests' => $maxGuests,
            ':desc'   => $description,
            ':img'    => $imageUrl,
            ':status' => $status
        ]);
        $_SESSION['room_msg'] = "New room added successfully.";
    }

    header("Location: rooms.php");
    exit;
}

// Check if Editing a Room
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM rooms WHERE id = :id");
    $stmtEdit->execute([':id' => $editId]);
    $editRoom = $stmtEdit->fetch() ?: null;
}

// Fetch all rooms
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: #f4efe6;">

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
            <li><a href="dashboard.php" style="color:#ffffff;"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="rooms.php" class="active" style="color:#ffffff;"><i class="fa-solid fa-bed"></i> Manage Rooms</a></li>
            <li><a href="services.php" style="color:#ffffff;"><i class="fa-solid fa-spa"></i> Manage Experiences</a></li>
            <li><a href="../index.php" target="_blank" style="color:#ffffff;"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Website</a></li>
            <li><a href="../logout.php" style="color: #ff6b6b;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container" style="margin-top: 35px; min-height: 600px;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fa-solid fa-bed" style="color:var(--color-accent-gold);"></i> Manage Villa Accommodations</h2>
        <a href="rooms.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-plus"></i> Add New Room</a>
    </div>

    <?php if (isset($_SESSION['room_msg'])): ?>
        <div class="alert alert-success"><?php echo e($_SESSION['room_msg']); unset($_SESSION['room_msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['room_error'])): ?>
        <div class="alert alert-danger"><?php echo e($_SESSION['room_error']); unset($_SESSION['room_error']); ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">

        <!-- Add / Edit Form -->
        <div class="booking-form-card" style="padding: 25px;">
            <h3><?php echo $editRoom ? 'Edit Suite' : 'Add New Suite'; ?></h3>
            <div class="batik-divider" style="margin: 10px 0 20px;"></div>

            <form action="rooms.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="room_id" value="<?php echo (int)($editRoom['id'] ?? 0); ?>">

                <div class="form-group">
                    <label class="form-label">Room / Suite Name *</label>
                    <input type="text" name="room_name" class="form-control" required maxlength="100" value="<?php echo e($editRoom['room_name'] ?? ''); ?>" placeholder="e.g. Sunset Royal Villa">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Type *</label>
                        <input type="text" name="room_type" class="form-control" required maxlength="50" value="<?php echo e($editRoom['room_type'] ?? 'Suite'); ?>" placeholder="Suite / Villa">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rate / Night ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="price_per_night" class="form-control" required value="<?php echo e($editRoom['price_per_night'] ?? '100.00'); ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Max Guests *</label>
                        <input type="number" min="1" max="20" name="max_guests" class="form-control" required value="<?php echo e($editRoom['max_guests'] ?? '2'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="available" <?php echo ($editRoom['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="maintenance" <?php echo ($editRoom['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Image URL or local path</label>
                    <input type="text" name="image_url" class="form-control" maxlength="255" value="<?php echo e($editRoom['image_url'] ?? ''); ?>" placeholder="https://... or assets/images/room.jpeg">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo e($editRoom['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-gold" style="width: 100%;">
                    <?php echo $editRoom ? 'Update Room' : 'Save Room'; ?>
                </button>
            </form>
        </div>

        <!-- Rooms List Table -->
        <div style="background: #ffffff; padding: 25px; border-radius: 4px; box-shadow: var(--shadow-soft);">
            <h3>Existing Accommodations (<?php echo count($rooms); ?>)</h3>
            <div class="batik-divider" style="margin: 10px 0 20px;"></div>

            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Suite Name</th>
                            <th>Type</th>
                            <th>Rate</th>
                            <th>Max</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $r): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo e($r['image_url']); ?>" alt="" style="width: 50px; height: 40px; object-fit: cover; border-radius: 2px;">
                                </td>
                                <td><strong><?php echo e($r['room_name']); ?></strong></td>
                                <td><?php echo e($r['room_type']); ?></td>
                                <td><strong><?php echo formatPrice($r['price_per_night']); ?></strong></td>
                                <td><?php echo (int)$r['max_guests']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $r['status'] === 'available' ? 'confirmed' : 'cancelled'; ?>">
                                        <?php echo ucfirst(e($r['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <a href="rooms.php?edit=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-gold" style="padding: 4px 8px; font-size:10px;"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="rooms.php" style="margin:0;" onsubmit="return confirm('Delete this room permanently?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo (int)$r['id']; ?>">
                                            <button type="submit" class="btn btn-sm" style="padding: 4px 8px; font-size:10px; background:#b33939; color:#fff; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

</body>
</html>
