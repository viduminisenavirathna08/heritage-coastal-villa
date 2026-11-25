<?php
/**
 * HERITAGE COASTAL VILLA - LOGIN (login.php)
 * Authenticates Guests & Administrators via PHP Sessions
 */
define('PAGE_TITLE', 'Account Login');
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: my_bookings.php");
    }
    exit;
}

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? ''); // not trimmed: spaces can be part of a password

    if ($email === '' || $password === '') {
        $errorMsg = 'Please enter both your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif (!empty($_SESSION['booking_draft'])) {
                // Guest was in the middle of booking: send them back to finish it
                header("Location: book.php");
            } else {
                header("Location: my_bookings.php");
            }
            exit;
        } else {
            $errorMsg = 'Invalid email or password. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 480px; margin: 60px auto; min-height: 480px;">
    <div class="booking-form-card" style="padding: 40px 30px;">
        <div style="text-align: center; margin-bottom: 25px;">
            <i class="fa-solid fa-lock" style="font-size: 32px; color: var(--color-accent-gold); margin-bottom: 10px;"></i>
            <h2 style="font-size: 22px;">Guest & Staff Login</h2>
            <div class="batik-divider" style="margin: 10px auto 15px;"></div>
            <p style="font-size: 13px; color: var(--color-text-muted);">Sign in to manage your villa reservations or administrative portal.</p>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo e($errorMsg); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
            <div class="alert alert-success">Registration successful! Please sign in with your credentials.</div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'login_required'): ?>
            <div class="alert alert-danger">Please log in to access this page.</div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'booking_login'): ?>
            <div class="alert alert-danger">Please sign in (or create a free guest account) to confirm your reservation. Your selections have been saved.</div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="you@example.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn btn-gold" style="width: 100%; padding: 12px;">Sign In</button>
            </div>
        </form>

        <div style="margin-top: 25px; text-align: center; font-size: 13px; color: var(--color-text-muted);">
            Don't have an account? <a href="register.php" style="color: var(--color-accent-gold); font-weight: 600;">Create Guest Account</a>
        </div>

        <?php if (DEMO_MODE): ?>
        
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
