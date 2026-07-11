<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../reset-password.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid security token. Please try again.');
    header('Location: ../reset-password.php');
    exit;
}

$token           = trim($_POST['token'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($token === '') {
    set_flash('danger', 'Invalid password reset link.');
    header('Location: ../login.php');
    exit;
}

if (strlen($password) < 8) {
    set_flash('danger', 'Password must be at least 8 characters.');
    header('Location: ../reset-password.php?token=' . urlencode($token));
    exit;
}

if ($password !== $confirmPassword) {
    set_flash('danger', 'Passwords do not match.');
    header('Location: ../reset-password.php?token=' . urlencode($token));
    exit;
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT pr.id, pr.user_id, pr.expires_at
        FROM password_resets pr
        WHERE pr.token = :token
          AND pr.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        set_flash('danger', 'This password reset link is invalid or has expired. Please request a new one.');
        header('Location: ../forgot-password.php');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':password' => $hashedPassword,
        ':id'       => (int) $reset['user_id'],
    ]);

    // Delete this token so it cannot be reused
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE id = :id");
    $stmt->execute([':id' => (int) $reset['id']]);

    // Invalidate all other password reset tokens for this user
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id AND id != :id");
    $stmt->execute([':user_id' => (int) $reset['user_id'], ':id' => (int) $reset['id']]);

    log_audit('password_reset', 'user', $reset['user_id'], null, ['password_reset_id' => $reset['id']]);

    set_flash('success', 'Your password has been reset successfully. Please sign in with your new password.');
    header('Location: ../login.php');
    exit;

} catch (PDOException $e) {
    error_log('Password reset confirm error: ' . $e->getMessage());
    set_flash('danger', 'An unexpected error occurred. Please try again.');
    header('Location: ../forgot-password.php');
    exit;
}
