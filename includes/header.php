<?php
/**
 * Header Template - Navigation & Meta Tags
 */
require_once __DIR__ . '/../config/db.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('PAGE_TITLE') ? PAGE_TITLE . ' | ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons for Luxury Badges -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Relaxed Tropical Luxury Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Main Navigation -->
<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="brand-logo" style="display: flex; flex-direction: row; align-items: center; gap: 12px; text-decoration: none;">
            <img src="assets/images/logo.jpg" alt="<?php echo SITE_NAME; ?>" style="height: 48px; width: auto; object-fit: contain; border-radius: 4px;">
            <div style="display: flex; flex-direction: column;">
                <span class="logo-title"><?php echo SITE_NAME; ?></span>
                <span class="logo-subtitle">Mirissa &bull; Sri Lanka</span>
            </div>
        </a>

        <ul class="nav-links">
            <li><a href="index.php" class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="javascript:void(0)" id="open_packing_guide" onclick="document.getElementById('packing_guide_modal').classList.add('active');"><i class="fa-solid fa-suitcase-rolling"></i> Packing Guide</a></li>
            <li><a href="book.php" class="btn btn-gold btn-sm"><i class="fa-solid fa-calendar-check"></i> Book Now</a></li>
            
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <li><a href="admin/dashboard.php" class="btn btn-olive btn-sm"><i class="fa-solid fa-shield"></i> Admin</a></li>
                <?php else: ?>
                    <li><a href="my_bookings.php" class="<?php echo $currentPage == 'my_bookings.php' ? 'active' : ''; ?>">My Bookings</a></li>
                <?php endif; ?>
                <li><a href="logout.php" style="color: var(--color-danger);"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            <?php else: ?>
                <li><a href="login.php"><i class="fa-regular fa-user"></i> Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
