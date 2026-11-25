<?php
/**
 * HERITAGE COASTAL VILLA - EXPERIENCES & WELLNESS CRUD (admin/services.php)
 * Manage In-House Luxuries, Spa & Steam Bath, Marine, Cultural & Transport.
 */
require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$editSrv = null;
$validCategories = ['free_facility', 'in_house', 'wellness', 'marine', 'cultural', 'travel'];

// Handle Delete Service (POST + CSRF). Services already used in bookings are kept (history).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    requireCsrf();
    $delId = (int)$_POST['delete_id'];

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM booking_services WHERE service_id = :id");
    $stmtCount->execute([':id' => $delId]);

    if ((int)$stmtCount->fetchColumn() > 0) {
        $_SESSION['srv_error'] = "This experience is part of existing bookings and cannot be deleted. Set it to 'Inactive' to hide it from guests.";
    } else {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM services WHERE id = :id");
            $stmtDel->execute([':id' => $delId]);
            $_SESSION['srv_msg'] = "Service deleted successfully.";
        } catch (PDOException $ex) {
            error_log('Service delete failed: ' . $ex->getMessage());
            $_SESSION['srv_error'] = "The service could not be deleted.";
        }
    }
    header("Location: services.php");
    exit;
}

// Handle Form Submission (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_name'])) {
    requireCsrf();

    $srvId       = (int)($_POST['service_id'] ?? 0);
    $serviceName = trim($_POST['service_name'] ?? '');
    $category    = $_POST['category'] ?? '';
    $price       = (float)($_POST['price'] ?? 0);
    $pricingUnit = trim($_POST['pricing_unit'] ?? 'per session');
    $description = trim($_POST['description'] ?? '');
    $imageUrl    = trim($_POST['image_url'] ?? '');
    $icon        = trim($_POST['icon'] ?? 'fa-star');
    $status      = $_POST['status'] ?? 'active';

    $error = '';
    if ($serviceName === '' || mb_strlen($serviceName) > 120) {
        $error = "Service name is required (max 120 characters).";
    } elseif (!in_array($category, $validCategories, true)) {
        $error = "Invalid category.";
    } elseif ($price < 0 || $price > 99999999) {
        $error = "Price cannot be negative.";
    } elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error = "Invalid status.";
    } elseif (mb_strlen($pricingUnit) > 50) {
        $error = "Pricing unit is too long (max 50 characters).";
    } elseif (mb_strlen($imageUrl) > 255) {
        $error = "Image path/URL is too long (max 255 characters).";
    } elseif (!preg_match('/^fa-[a-z0-9-]{1,45}$/', $icon)) {
        $error = "Icon must look like 'fa-spa' (letters, numbers and dashes only).";
    }

    if ($error !== '') {
        $_SESSION['srv_error'] = $error;
        header("Location: services.php" . ($srvId > 0 ? "?edit=" . $srvId : ""));
        exit;
    }

    if ($srvId > 0) {
        $stmtUpdate = $pdo->prepare("
            UPDATE services
            SET service_name = :name, category = :cat, price = :price, pricing_unit = :unit, description = :desc, image_url = :img, icon = :icon, status = :status
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':name'   => $serviceName,
            ':cat'    => $category,
            ':price'  => $price,
            ':unit'   => $pricingUnit,
            ':desc'   => $description,
            ':img'    => $imageUrl,
            ':icon'   => $icon,
            ':status' => $status,
            ':id'     => $srvId
        ]);
        $_SESSION['srv_msg'] = "Experience updated successfully.";
    } else {
        $stmtInsert = $pdo->prepare("
            INSERT INTO services (service_name, category, price, pricing_unit, description, image_url, icon, status)
            VALUES (:name, :cat, :price, :unit, :desc, :img, :icon, :status)
        ");
        $stmtInsert->execute([
            ':name'   => $serviceName,
            ':cat'    => $category,
            ':price'  => $price,
            ':unit'   => $pricingUnit,
            ':desc'   => $description,
            ':img'    => $imageUrl,
            ':icon'   => $icon,
            ':status' => $status
        ]);
        $_SESSION['srv_msg'] = "New experience created successfully.";
    }

    header("Location: services.php");
    exit;
}

// Check if Editing
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM services WHERE id = :id");
    $stmtEdit->execute([':id' => $editId]);
    $editSrv = $stmtEdit->fetch() ?: null;
}

// Fetch all services
$services = $pdo->query("SELECT * FROM services ORDER BY category ASC, price ASC")->fetchAll();

// Resolve image path for admin display
foreach ($services as &$s) {
    if (empty($s['image_url']) || !file_exists(__DIR__ . '/../' . $s['image_url'])) {
        $n = strtolower($s['service_name']);
        if (strpos($n, 'hamper') !== false) $s['image_url'] = 'assets/images/ayurvedic hamper.jpeg';
        elseif (strpos($n, 'bar') !== false || strpos($n, 'lounge') !== false) $s['image_url'] = 'assets/images/bar.jpeg';
        elseif (strpos($n, 'bag') !== false) $s['image_url'] = 'assets/images/beach bag.jpeg';
        elseif (strpos($n, 'pool') !== false) $s['image_url'] = 'assets/images/beach font pool.jpeg';
        elseif (strpos($n, 'sunbed') !== false) $s['image_url'] = 'assets/images/sunbeds.jpeg';
        elseif (strpos($n, 'tea') !== false) $s['image_url'] = 'assets/images/ceylon tea.jpeg';
    }
}
unset($s);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Experiences & Spa | <?php echo SITE_NAME; ?></title>
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
            <li><a href="rooms.php" style="color:#ffffff;"><i class="fa-solid fa-bed"></i> Manage Rooms</a></li>
            <li><a href="services.php" class="active" style="color:#ffffff;"><i class="fa-solid fa-spa"></i> Manage Experiences</a></li>
            <li><a href="../index.php" target="_blank" style="color:#ffffff;"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Website</a></li>
            <li><a href="../logout.php" style="color: #ff6b6b;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container" style="margin-top: 35px; min-height: 600px;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--color-accent-gold);"></i> Manage Experiences, Spa & Packages</h2>
        <a href="services.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-plus"></i> Add New Service</a>
    </div>

    <?php if (isset($_SESSION['srv_msg'])): ?>
        <div class="alert alert-success"><?php echo e($_SESSION['srv_msg']); unset($_SESSION['srv_msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['srv_error'])): ?>
        <div class="alert alert-danger"><?php echo e($_SESSION['srv_error']); unset($_SESSION['srv_error']); ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">

        <!-- Form -->
        <div class="booking-form-card" style="padding: 25px;">
            <h3><?php echo $editSrv ? 'Edit Experience' : 'Add New Experience'; ?></h3>
            <div class="batik-divider" style="margin: 10px 0 20px;"></div>

            <form action="services.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="service_id" value="<?php echo (int)($editSrv['id'] ?? 0); ?>">

                <div class="form-group">
                    <label class="form-label">Service / Add-on Name *</label>
                    <input type="text" name="service_name" class="form-control" required maxlength="120" value="<?php echo e($editSrv['service_name'] ?? ''); ?>" placeholder="e.g. Floating Ceylon Breakfast">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-control" required id="category_select" onchange="togglePriceForFree(this.value)">
                            <option value="free_facility" <?php echo ($editSrv['category'] ?? '') === 'free_facility' ? 'selected' : ''; ?>>Includes Privilleges</option>
                            <option value="in_house" <?php echo ($editSrv['category'] ?? '') === 'in_house' ? 'selected' : ''; ?>>In-House Luxury</option>
                            <option value="wellness" <?php echo ($editSrv['category'] ?? '') === 'wellness' ? 'selected' : ''; ?>>Wellness and spa</option>
                            <option value="marine" <?php echo ($editSrv['category'] ?? '') === 'marine' ? 'selected' : ''; ?>>Marine Adventures</option>
                            <option value="cultural" <?php echo ($editSrv['category'] ?? '') === 'cultural' ? 'selected' : ''; ?>>Cultural Heritage</option>
                            <option value="travel" <?php echo ($editSrv['category'] ?? '') === 'travel' ? 'selected' : ''; ?>>Travel & Transport</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price ($) *</label>
                        <input type="number" step="0.01" min="0.00" name="price" id="service_price" class="form-control" required value="<?php echo e(isset($editSrv['price']) ? $editSrv['price'] : '25.00'); ?>">
                        <small style="font-size:11px; color:#777;">(Enter 0.00 for Free Facilities)</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Photo URL or local path</label>
                    <input type="text" name="image_url" class="form-control" maxlength="255" value="<?php echo e($editSrv['image_url'] ?? ''); ?>" placeholder="assets/images/photo.jpeg or https://...">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Pricing Unit</label>
                        <input type="text" name="pricing_unit" class="form-control" maxlength="50" value="<?php echo e($editSrv['pricing_unit'] ?? 'per session'); ?>" placeholder="per session / couple">
                    </div>
                    <div class="form-group">
                        <label class="form-label">FontAwesome Icon</label>
                        <input type="text" name="icon" class="form-control" maxlength="50" value="<?php echo e($editSrv['icon'] ?? 'fa-star'); ?>" placeholder="fa-spa, fa-water">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo ($editSrv['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active (Visible)</option>
                        <option value="inactive" <?php echo ($editSrv['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo e($editSrv['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-gold" style="width: 100%;">
                    <?php echo $editSrv ? 'Update Service' : 'Save Service'; ?>
                </button>
            </form>
        </div>

        <script>
        function togglePriceForFree(cat) {
            const priceInput = document.getElementById('service_price');
            if (cat === 'free_facility') {
                if (priceInput && (!priceInput.value || parseFloat(priceInput.value) > 0)) {
                    priceInput.value = '0.00';
                }
            }
        }
        </script>

        <!-- Table -->
        <div style="background: #ffffff; padding: 25px; border-radius: 4px; box-shadow: var(--shadow-soft);">
            <h3>All Services & Experiences (<?php echo count($services); ?>)</h3>
            <div class="batik-divider" style="margin: 10px 0 20px;"></div>

            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($s['image_url'])): ?>
                                        <img src="<?php echo e($s['image_url']); ?>" alt="" style="width: 50px; height: 40px; object-fit: cover; border-radius: 2px;">
                                    <?php else: ?>
                                        <div style="width:50px; height:40px; background:#eee; display:flex; align-items:center; justify-content:center; border-radius:2px;">
                                            <i class="fa-solid fa-image" style="color:#aaa;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fa-solid <?php echo e($s['icon']); ?>" style="color:var(--color-olive); margin-right:6px;"></i>
                                    <strong><?php echo e($s['service_name']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($s['category'] === 'free_facility'): ?>
                                        <span class="badge" style="background:#e8f5e9; color:#2e7d32; font-weight:700;">FREE FACILITY</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#eef2ec; color:var(--color-olive);"><?php echo e(strtoupper(str_replace('_', ' ', $s['category']))); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo formatPrice($s['price']); ?></strong></td>
                                <td><small><?php echo e($s['pricing_unit']); ?></small></td>
                                <td>
                                    <span class="badge badge-<?php echo $s['status'] === 'active' ? 'confirmed' : 'cancelled'; ?>">
                                        <?php echo ucfirst(e($s['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <a href="services.php?edit=<?php echo (int)$s['id']; ?>" class="btn btn-sm btn-gold" style="padding: 4px 8px; font-size:10px;"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="services.php" style="margin:0;" onsubmit="return confirm('Delete this experience?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo (int)$s['id']; ?>">
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
