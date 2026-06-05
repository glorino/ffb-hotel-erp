<?php
require_once __DIR__ . '/auth-check.php';

function checkRole($allowed_roles) {
    global $redirect_path;
    if (!isset($_SESSION['role_slug'])) {
        header('Location: ' . $redirect_path . 'login.php');
        exit;
    }
    if (!in_array($_SESSION['role_slug'], (array)$allowed_roles)) {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. You do not have permission to access this page.');
    }
}
