<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';

$page_title = $page_title ?? APP_NAME . ' Dashboard';
$current_user = getUser();

$base_url = $base_url ?? '../';

$notifications = [];
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? OR (user_id IS NULL AND branch_id = ?) ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['branch_id'] ?? 0]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unread_count = 0;
    foreach ($notifications as $n) {
        if (empty($n['is_read'])) $unread_count++;
    }
} catch (Exception $e) {
    $unread_count = 0;
}

$branches = [];
try {
    $branches = getBranches();
} catch (Exception $e) {
    $branches = [];
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FFB Hotel Dashboard">
    <?php echo csrf_meta(); ?>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo $base_url; ?>favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/responsive.css">
</head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo $base_url; ?>dashboard/index.php" class="sidebar-brand">
                <span class="brand-icon">GP</span>
                <span class="brand-text">FFB Hotel</span>
            </a>
            <button type="button" class="sidebar-close d-lg-none" id="sidebarClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <?php
        $role_slug = $_SESSION['role_slug'] ?? 'guest';
        $sidebar_file = __DIR__ . "/../dashboard/sidebars/{$role_slug}.php";
        if (file_exists($sidebar_file)) {
            include $sidebar_file;
        } else {
            include __DIR__ . '/../dashboard/sidebars/default.php';
        }
        ?>
    </aside>

    <div class="main-content">
        <header class="top-navbar">
            <div class="d-flex align-items-center">
                <button type="button" class="sidebar-toggle d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>

                <div class="search-bar d-none d-md-block">
                    <form action="<?php echo $base_url; ?>dashboard/search.php" method="GET">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="Search bookings, guests, invoices..." aria-label="Search">
                        </div>
                    </form>
                </div>
            </div>

            <div class="nav-controls">
                <?php if (count($branches) > 1): ?>
                <div class="branch-switcher dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-building"></i>
                        <span id="currentBranch"><?php echo htmlspecialchars($current_user['branch_name'] ?? 'All Branches'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item branch-option" href="#" data-branch-id="">All Branches</a></li>
                        <?php foreach ($branches as $branch): ?>
                            <li><a class="dropdown-item branch-option <?php echo ($branch['id'] == ($_SESSION['branch_id'] ?? '')) ? 'active' : ''; ?>" href="#" data-branch-id="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="notification-dropdown dropdown">
                    <button class="btn btn-outline-secondary btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-menu">
                        <div class="notification-header">
                            <h6 class="mb-0">Notifications</h6>
                            <a href="<?php echo $base_url; ?>dashboard/notifications.php" class="btn btn-sm btn-link">View All</a>
                        </div>
                        <div class="notification-body">
                            <?php if (empty($notifications)): ?>
                                <p class="text-muted text-center py-3 mb-0">No notifications</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <a class="dropdown-item notification-item <?php echo empty($notif['is_read']) ? 'unread' : ''; ?>" href="<?php echo $base_url . ltrim($notif['link'] ?? '#'); ?>">
                                        <div class="notification-content">
                                            <p class="mb-0"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <small class="text-muted"><?php echo timeAgo($notif['created_at']); ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="user-dropdown dropdown">
                    <button class="btn dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar me-2">
                            <?php if (!empty($current_user['avatar'])): ?>
                                <img src="<?php echo $base_url . htmlspecialchars($current_user['avatar']); ?>" alt="Avatar" class="rounded-circle" width="36" height="36">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#2d3436;color:#fff;font-size:14px;font-weight:600;">
                                    <?php echo strtoupper(substr($current_user['first_name'] ?? 'U', 0, 1) . substr($current_user['last_name'] ?? 'S', 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="user-info d-none d-md-block text-start">
                            <span class="user-name d-block"><?php echo htmlspecialchars(($current_user['first_name'] ?? 'User') . ' ' . ($current_user['last_name'] ?? '')); ?></span>
                            <span class="user-role d-block text-muted small"><?php echo htmlspecialchars($current_user['role_name'] ?? ''); ?></span>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>dashboard/profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>dashboard/settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <?php if (in_array($_SESSION['role_slug'] ?? '', ['admin', 'manager'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>dashboard/branches.php"><i class="bi bi-building"></i> Branches</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>dashboard/users.php"><i class="bi bi-people"></i> Users</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo $base_url; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <?php flash(); ?>
