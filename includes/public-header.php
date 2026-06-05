<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/flash.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

$page_title = $page_title ?? APP_NAME;
$current_page = $current_page ?? basename($_SERVER['SCRIPT_NAME']);

$announcement = getSetting('announcement_text', '');
$show_announcement = getSetting('show_announcement', '0') === '1';
$has_announcement = $show_announcement && $announcement;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(APP_NAME); ?> – Where Luxury Meets Comfort. Experience world-class hospitality and premium accommodations.">
    <?php echo csrf_meta(); ?>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Montserrat:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/public.css?v=2.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive.css?v=2.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/live-chat.css?v=2.0">
</head>
<body<?php echo $has_announcement ? ' class="has-announcement"' : ''; ?>>

<?php if ($has_announcement): ?>
<div class="announcement-bar" id="announcementBar">
    <span class="announcement-text"><?php echo htmlspecialchars($announcement); ?></span>
    <button type="button" class="announcement-close" id="announcementClose" aria-label="Close announcement">&times;</button>
</div>
<?php endif; ?>

<header class="site-header" id="siteHeader">
    <div class="header-container">
        <a href="<?php echo BASE_URL; ?>index.php" class="header-logo">
            <span class="logo-main">FFB HOTEL</span>
            <span class="logo-separator">&#10022;</span>
            <span class="logo-sub">Hotel</span>
        </a>

        <nav class="header-nav" id="headerNav">
            <a href="<?php echo BASE_URL; ?>index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Home</a>
            <a href="<?php echo BASE_URL; ?>about.php" class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">About</a>
            <a href="<?php echo BASE_URL; ?>services.php" class="nav-link <?php echo $current_page === 'services.php' ? 'active' : ''; ?>">Services</a>
            <a href="<?php echo BASE_URL; ?>rooms.php" class="nav-link <?php echo in_array($current_page, ['rooms.php', 'room-detail.php']) ? 'active' : ''; ?>">Rooms &amp; Suites</a>
            <a href="<?php echo BASE_URL; ?>gallery.php" class="nav-link <?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>">Gallery</a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">Contact</a>
        </nav>

        <div class="header-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo BASE_URL; ?>dashboard/index.php" class="btn-login">
                    <i class="bi bi-person"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php" class="btn-login">Login</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn-login">Register</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-sm">Book Now</a>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <div class="mobile-overlay-backdrop" id="mobileOverlayBackdrop"></div>
    <div class="mobile-overlay" id="mobileOverlay">
        <a href="<?php echo BASE_URL; ?>index.php" class="mobile-nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Home</a>
        <a href="<?php echo BASE_URL; ?>about.php" class="mobile-nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">About</a>
        <a href="<?php echo BASE_URL; ?>services.php" class="mobile-nav-link <?php echo $current_page === 'services.php' ? 'active' : ''; ?>">Services</a>
        <a href="<?php echo BASE_URL; ?>rooms.php" class="mobile-nav-link <?php echo in_array($current_page, ['rooms.php', 'room-detail.php']) ? 'active' : ''; ?>">Rooms &amp; Suites</a>
        <a href="<?php echo BASE_URL; ?>gallery.php" class="mobile-nav-link <?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>">Gallery</a>
        <a href="<?php echo BASE_URL; ?>contact.php" class="mobile-nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">Contact</a>
        <div class="mobile-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo BASE_URL; ?>dashboard/index.php" class="btn btn-outline-gold"><i class="bi bi-person"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-outline-gold">Login</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-outline-gold">Register</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold">Book Now</a>
        </div>
    </div>
</header>

<main class="site-main">

<button class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="bi bi-chevron-up"></i>
</button>
