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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/responsive.css">
</head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar" id="sidebar">
        <button type="button" class="sidebar-close d-lg-none" id="sidebarClose" style="position:absolute;top:16px;right:16px;background:none;border:none;color:rgba(255,255,255,0.6);font-size:1.2rem;z-index:10;">
            <i class="bi bi-x-lg"></i>
        </button>
        <?php
        $role_slug = $_SESSION['role_slug'] ?? 'guest';
        $sidebar_file = __DIR__ . "/../sidebars/{$role_slug}-sidebar.php";
        if (file_exists($sidebar_file)) {
            include $sidebar_file;
        } else {
            include __DIR__ . '/../sidebars/owner-sidebar.php';
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
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search..." aria-label="Search">
                    </div>
                </div>
            </div>
            <div class="nav-controls">
                <div class="notification-bell dropdown me-3">
                    <button class="btn position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color:var(--text-muted);">
                        <i class="bi bi-bell" style="font-size:1.2rem;"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                            <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="width:320px;max-height:400px;overflow-y:auto;">
                        <li class="dropdown-header fw-bold">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (empty($notifications)): ?>
                        <li><span class="dropdown-item text-muted text-center py-3">No notifications</span></li>
                        <?php else: ?>
                        <?php foreach (array_slice($notifications, 0, 5) as $n): ?>
                        <li><a class="dropdown-item py-2" href="notifications.php?id=<?php echo $n['id']; ?>">
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-shrink-0 mt-1"><i class="bi bi-bell-fill text-primary" style="font-size:0.7rem;"></i></div>
                                <div>
                                    <div style="font-size:0.82rem;line-height:1.3;"><?php echo htmlspecialchars($n['message'] ?? $n['title'] ?? 'Notification'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($n['created_at'] ?? ''); ?></small>
                                </div>
                            </div>
                        </a></li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center py-2 fw-semibold" href="notifications.php" style="font-size:0.82rem;">View All Notifications</a></li>
                    </ul>
                </div>
                <div class="user-dropdown dropdown">
                    <button class="btn dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:var(--gold);color:var(--navy);font-size:14px;font-weight:600;">
                            <?php echo strtoupper(substr($current_user['full_name'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="user-info d-none d-md-block text-start ms-2">
                            <span class="user-name d-block" style="font-size:0.85rem;font-weight:600;"><?php echo htmlspecialchars($current_user['full_name'] ?? 'User'); ?></span>
                            <span class="user-role d-block text-muted" style="font-size:0.72rem;"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $current_user['role_slug'] ?? ''))); ?></span>
                        </div>
                    </button>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <?php flash(); ?>
