<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot-password.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid security token. Please try again.');
    header('Location: ../forgot-password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please enter a valid email address.');
    header('Location: ../forgot-password.php');
    exit;
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = :email AND status = 'active' LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
     * ─────────────────────────────────────────────────────────────────────
     * password_resets table reference:
     *
     * CREATE TABLE password_resets (
     *     id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     *     user_id    INT UNSIGNED NOT NULL,
     *     token      VARCHAR(64)  NOT NULL,
     *     expires_at DATETIME     NOT NULL,
     *     used       TINYINT(1)   NOT NULL DEFAULT 0,
     *     created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
     *     INDEX idx_token (token),
     *     INDEX idx_user_id (user_id),
     *     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
     * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
     * ─────────────────────────────────────────────────────────────────────
     */

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("
            INSERT INTO password_resets (user_id, token, expires_at, created_at)
            VALUES (:user_id, :token, :expires_at, NOW())
        ");
        $stmt->execute([
            ':user_id'    => (int) $user['id'],
            ':token'      => $token,
            ':expires_at' => $expires,
        ]);

        // Log the request
        log_audit('password_reset_request', 'user', $user['id'], null, ['email' => $email]);

        // ─────────────────────────────────────────────────────────────
        // Send password reset email (placeholder — update with real
        // SMTP when mail credentials are configured)
        // ─────────────────────────────────────────────────────────────
        $resetLink = (defined('APP_URL') ? APP_URL : '') . '/reset-password.php?token=' . urlencode($token);

        $subject = 'Reset Your ' . APP_NAME . ' Password';
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
                    <h2 style='color: #1a1a2e; margin-top: 0;'>Password Reset Request</h2>
                    <p style='color: #666; line-height: 1.6;'>Hello " . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . ",</p>
                    <p style='color: #666; line-height: 1.6;'>We received a request to reset your password for your FFB Hotel account. Click the button below to set a new password:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}'
                           style='display: inline-block; background: linear-gradient(135deg, #d4af37, #f5d76e); color: #1a1a2e; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 15px;'>
                           Reset Password
                        </a>
                    </div>
                    <p style='color: #999; font-size: 13px; line-height: 1.6;'>This link will expire in <strong>1 hour</strong>. If you did not request a password reset, please ignore this email.</p>
                    <p style='color: #999; font-size: 13px;'>If the button doesn't work, copy and paste this URL into your browser:</p>
                    <p style='color: #d4af37; font-size: 12px; word-break: break-all; background: #f9f9f9; padding: 10px; border-radius: 6px;'>{$resetLink}</p>
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
            error_log("Password reset email not sent to {$email}: SMTP not yet configured.");
        }
    }

    // Always show the same message whether the email exists or not,
    // to prevent email enumeration
    set_flash('success', 'If that email address is registered, you will receive a password reset link shortly.');
    header('Location: ../forgot-password.php');
    exit;

} catch (PDOException $e) {
    error_log('Password reset request error: ' . $e->getMessage());
    set_flash('danger', 'An unexpected error occurred. Please try again.');
    header('Location: ../forgot-password.php');
    exit;
}
