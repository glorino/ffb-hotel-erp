<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';

$page_title = $page_title ?? APP_NAME . ' Dashboard';
try {
    $current_user = getUser();
} catch (Exception $e) {
    error_log('getUser failed: ' . $e->getMessage());
    $current_user = ['full_name' => $_SESSION['full_name'] ?? 'User', 'role_slug' => $_SESSION['role_slug'] ?? '', 'role_name' => ''];
}
$base_url = $base_url ?? '../';

$unread_count = 0;
$notifications = [];
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($notifications as $n) {
        if (empty($n['is_read'])) $unread_count++;
    }
} catch (Exception $e) {
    $unread_count = 0;
}

$page_greeting = '';
$hour = (int) date('G');
if ($hour < 12) $page_greeting = 'Good morning';
elseif ($hour < 17) $page_greeting = 'Good afternoon';
else $page_greeting = 'Good evening';
$first_name = explode(' ', $current_user['full_name'] ?? 'User')[0];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo csrf_meta(); ?>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo $base_url; ?>favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/dashboard.css">
    <script src="https://checkout.flutterwave.com/v3/flare/checkout.js"></script>
    <meta name="flutterwave-key" content="<?php echo htmlspecialchars(getSetting('flutterwave_public_key', '')); ?>">
</head>
<body>

<div class="dashboard-wrapper">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <button type="button" class="sidebar-close d-lg-none" id="sidebarClose">
            <i class="bi bi-x-lg"></i>
        </button>
        <?php
        $role_slug = $_SESSION['role_slug'] ?? 'guest';
        $sidebar_map = [
            'business_owner'    => 'owner',
            'admin'             => 'admin',
            'branch_manager'    => 'branch-manager',
            'receptionist'      => 'reception',
            'kitchen_chef'      => 'kitchen',
            'waiter'            => 'waiter',
            'inventory_manager' => 'inventory',
            'housekeeping'      => 'housekeeping',
            'accountant'        => 'accountant',
            'customer'          => 'customer',
        ];
        $sidebar_key = $sidebar_map[$role_slug] ?? $role_slug;
        $sidebar_file = __DIR__ . "/../sidebars/{$sidebar_key}-sidebar.php";
        if (file_exists($sidebar_file)) {
            include $sidebar_file;
        } else {
            include __DIR__ . '/../sidebars/customer-sidebar.php';
        }
        ?>
    </aside>

    <div class="main-content">
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="sidebar-toggle d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="header-greeting d-none d-md-block">
                    <h6 class="mb-0 fw-semibold" style="font-size:0.95rem;color:var(--text-dark);"><?php echo $page_greeting; ?>, <?php echo htmlspecialchars($first_name); ?></h6>
                    <small class="text-muted" style="font-size:0.78rem;"><?php echo date('l, M j, Y'); ?></small>
                </div>
            </div>
            <div class="nav-controls">
                <div class="header-search d-none d-lg-block">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="form-control" placeholder="Search rooms, guests, bookings..." aria-label="Search">
                </div>
                <div class="notification-btn position-relative" id="notificationBtn">
                    <i class="bi bi-bell"></i>
                    <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
                    <?php endif; ?>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-dropdown-header">
                            <span>Notifications</span>
                            <a href="notifications.php">View All</a>
                        </div>
                        <?php if (empty($notifications)): ?>
                        <div class="text-center py-4 text-muted" style="font-size:0.85rem;">
                            <i class="bi bi-bell-slash d-block mb-2" style="font-size:1.5rem;opacity:0.4;"></i>
                            No notifications yet
                        </div>
                        <?php else: ?>
                        <?php foreach (array_slice($notifications, 0, 5) as $n): ?>
                        <div class="notification-item" onclick="window.location.href='notifications.php?id=<?php echo $n['id']; ?>'">
                            <div class="notif-icon info">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <div class="notif-content">
                                <div class="notif-title"><?php echo htmlspecialchars($n['title'] ?? 'Notification'); ?></div>
                                <div class="notif-text"><?php echo htmlspecialchars(truncate($n['message'] ?? '', 60)); ?></div>
                                <div class="notif-time"><?php echo timeAgo($n['created_at'] ?? ''); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-wrapper position-relative" id="profileWrapper">
                    <button class="profile-btn" type="button" id="profileBtn">
                        <div class="avatar">
                            <?php echo strtoupper(substr($current_user['full_name'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <span class="profile-name"><?php echo htmlspecialchars($current_user['full_name'] ?? 'User'); ?></span>
                            <span class="profile-role"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $current_user['role_slug'] ?? ''))); ?></span>
                        </div>
                        <i class="bi bi-chevron-down" style="font-size:0.7rem;color:var(--text-muted);"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-header">
                            <div class="avatar-lg">
                                <?php echo strtoupper(substr($current_user['full_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div class="profile-info">
                                <h4><?php echo htmlspecialchars($current_user['full_name'] ?? 'User'); ?></h4>
                                <span><?php echo htmlspecialchars($current_user['email'] ?? ''); ?></span>
                            </div>
                        </div>
                        <a href="settings.php" class="profile-menu-item">
                            <span class="menu-icon"><i class="bi bi-gear"></i></span> Settings
                        </a>
                        <a href="notifications.php" class="profile-menu-item">
                            <span class="menu-icon"><i class="bi bi-bell"></i></span> Notifications
                        </a>
                        <div style="border-top:1px solid var(--border);margin:4px 0;"></div>
                        <a href="../logout.php" class="profile-menu-item danger">
                            <span class="menu-icon"><i class="bi bi-box-arrow-right"></i></span> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <?php flash(); ?>
