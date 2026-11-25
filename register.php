<?php
/**
 * HERITAGE COASTAL VILLA - REGISTRATION (register.php)
 * Creates a new Guest account with bcrypt password hashing
 */
define('PAGE_TITLE', 'Create Guest Account');
require_once __DIR__ . '/config/db.php';

if (isLoggedIn()) {
    header("Location: my_bookings.php");
    exit;
}

$errorMsg = '';
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    $old = ['full_name' => $fullName, 'email' => $email, 'phone' => $phone];

    if ($fullName === '' || $email === '' || $phone === '' || $password === '') {
        $errorMsg = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9+()\-\s]{7,25}$/', $phone)) {
        $errorMsg = 'Please enter a valid phone number (digits, +, spaces and dashes only).';
    } elseif ($password !== $confirm) {
        $errorMsg = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $errorMsg = 'Password must be at least 6 characters long.';
    } elseif (mb_strlen($fullName) > 100 || mb_strlen($email) > 100) {
        $errorMsg = 'Name or email is too long.';
    } else {
        // Check if email already registered
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            $errorMsg = 'An account with this email address already exists.';
        } else {
            // Hash password and insert
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("
                INSERT INTO users (full_name, email, phone, password, role)
                VALUES (:full_name, :email, :phone, :password, 'guest')
            ");
            $stmtInsert->execute([
                ':full_name' => $fullName,
                ':email'     => $email,
                ':phone'     => $phone,
                ':password'  => $hashedPassword
            ]);

            header("Location: login.php?msg=registered");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 520px; margin: 50px auto; min-height: 520px;">
    <div class="booking-form-card" style="padding: 40px 30px;">
        <div style="text-align: center; margin-bottom: 25px;">
            <i class="fa-solid fa-user-plus" style="font-size: 32px; color: var(--color-accent-gold); margin-bottom: 10px;"></i>
            <h2 style="font-size: 22px;">Create Guest Account</h2>
            <div class="batik-divider" style="margin: 10px auto 15px;"></div>
            <p style="font-size: 13px; color: var(--color-text-muted);">Register to reserve luxury suites and experience our signature wellness packages.</p>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo e($errorMsg); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required maxlength="100" value="<?php echo e($old['full_name']); ?>" placeholder="John Doe">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" required maxlength="100" value="<?php echo e($old['email']); ?>" placeholder="john@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone / WhatsApp *</label>
                    <input type="tel" id="phone" name="phone" class="form-control" required maxlength="25" value="<?php echo e($old['phone']); ?>" placeholder="+94 77 123 4567">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="6" placeholder="At least 6 chars">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6" placeholder="Repeat password">
                </div>
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn btn-gold" style="width: 100%; padding: 12px;">Create Account</button>
            </div>
        </form>

        <div style="margin-top: 25px; text-align: center; font-size: 13px; color: var(--color-text-muted);">
            Already have an account? <a href="login.php" style="color: var(--color-accent-gold); font-weight: 600;">Sign In here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
