<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/flash.php';

if (!empty($_SESSION['user_id'])) {
    log_audit('logout', 'user', $_SESSION['user_id'], null, ['email' => $_SESSION['email'] ?? '']);
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'] ?? true,
            'httponly' => $params['httponly'] ?? true,
            'samesite' => 'Lax',
        ]
    );
}

if (isset($_COOKIE['remember_token'])) {
    setcookie(
        'remember_token',
        '',
        [
            'expires'  => time() - 42000,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

session_destroy();

set_flash('success', 'You have been logged out successfully.');
header('Location: ../login.php');
exit;
