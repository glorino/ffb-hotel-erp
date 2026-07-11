<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid security token. Please try again.');
    header('Location: ../register.php');
    exit;
}

$full_name       = trim($_POST['full_name'] ?? '');
$email           = trim($_POST['email'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$password        = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$terms           = !empty($_POST['terms']);

$errors = [];

if ($full_name === '') {
    $errors[] = 'Full name is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

if (!$terms) {
    $errors[] = 'You must agree to the Terms of Service and Privacy Policy.';
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    header('Location: ../register.php');
    exit;
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        set_flash('danger', 'An account with this email address already exists. <a href="login.php" class="auth-link">Sign in instead?</a>');
        header('Location: ../register.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM roles WHERE slug = 'customer' LIMIT 1");
    $stmt->execute();
    $role = $stmt->fetch();

    if (!$role) {
        error_log('Register error: customer role not found in roles table.');
        set_flash('danger', 'Registration is currently unavailable. Please try again later.');
        header('Location: ../register.php');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, phone, password, role_id, status, created_at, updated_at)
        VALUES (:full_name, :email, :phone, :password, :role_id, 'active', NOW(), NOW())
    ");
    $stmt->execute([
        ':full_name' => $full_name,
        ':email'     => $email,
        ':phone'     => $phone ?: null,
        ':password'  => $hashedPassword,
        ':role_id'   => (int) $role['id'],
    ]);

    $userId = (int) $pdo->lastInsertId();

    session_regenerate_id(true);

    $_SESSION['user_id']   = $userId;
    $_SESSION['role_id']   = (int) $role['id'];
    $_SESSION['role_slug'] = 'customer';
    $_SESSION['branch_id'] = null;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email']     = $email;

    log_audit('register', 'user', $userId, null, [
        'email' => $email,
        'role'  => 'customer',
    ]);

    // ─────────────────────────────────────────────────────────
    // Welcome email (placeholder — update with real SMTP when
    // mail credentials are configured in config/mail.php)
    // ─────────────────────────────────────────────────────────
    $subject = 'Welcome to ' . APP_NAME;
    $message = "
    <!DOCTYPE html>
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; background: #f4f4f4; padding: 40px;'>
        <div style='max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
            <div style='background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 30px; text-align: center;'>
                <h1 style='color: #d4af37; font-family: Georgia, serif; margin: 0; letter-spacing: 3px;'>FFB HOTEL</h1>
                <p style='color: rgba(255,255,255,0.6); margin: 5px 0 0; font-size: 12px; letter-spacing: 4px;'>HOTEL</p>
            </div>
            <div style='padding: 40px;'>
                <h2 style='color: #1a1a2e; margin-top: 0;'>Welcome, {$full_name}!</h2>
                <p style='color: #666; line-height: 1.6;'>Thank you for creating an account with FFB Hotel. You now have access to:</p>
                <ul style='color: #666; line-height: 1.8;'>
                    <li>Online room booking &amp; reservations</li>
                    <li>Order food &amp; beverages from your room</li>
                    <li>Request housekeeping &amp; concierge services</li>
                    <li>View your booking history &amp; invoices</li>
                </ul>
                <a href='" . (defined('APP_URL') ? APP_URL : '#') . "/customer/dashboard.php'
                   style='display: inline-block; background: linear-gradient(135deg, #d4af37, #f5d76e); color: #1a1a2e; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; margin-top: 20px;'>
                   Go to Your Dashboard
                </a>
                <p style='color: #999; font-size: 13px; margin-top: 30px;'>
                    If you have any questions, feel free to <a href='" . (defined('APP_URL') ? APP_URL : '#') . "/contact.php' style='color: #d4af37;'>contact our support team</a>.
                </p>
            </div>
            <div style='background: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;'>
                <p style='color: #999; font-size: 12px; margin: 0;'>&copy; " . date('Y') . " FFB Hotel. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";

    if (defined('SMTP_HOST') && SMTP_HOST !== 'smtp.hostinger.com') {
        mail($email, $subject, $message, $headers);
    } else {
        error_log("Welcome email not sent to {$email}: SMTP not yet configured.");
    }

    set_flash('success', 'Account created successfully! Welcome to ' . APP_NAME . '.');
    header('Location: ../customer/dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());
    set_flash('danger', 'An unexpected error occurred. Please try again.');
    header('Location: ../register.php');
    exit;
}
