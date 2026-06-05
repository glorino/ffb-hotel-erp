<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$redirect_path = $redirect_path ?? '../../';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $redirect_path . 'login.php');
    exit;
}
