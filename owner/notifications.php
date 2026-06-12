<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Notifications';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    try {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log('Mark notification read failed: ' . $e->getMessage());
    }
    header('Location: notifications.php');
    exit;
}

if (isset($_POST['mark_all_read'])) {
    try {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log('Mark all read failed: ' . $e->getMessage());
    }
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log('Delete notification failed: ' . $e->getMessage());
    }
    header('Location: notifications.php');
    exit;
}

$notifications = [];
$unread_count = 0;
try {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($notifications as $n) {
        if (empty($n['is_read'])) $unread_count++;
    }
} catch (Exception $e) {
    error_log('Fetch notifications failed: ' . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Notifications</h4>
        <?php if ($unread_count > 0): ?>
        <form method="POST" class="d-inline">
            <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-check-all me-1"></i> Mark All as Read (<?php echo $unread_count; ?>)
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-bell-slash"></i></div>
                <h4>No Notifications</h4>
                <p>You have no notifications at this time.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $n): ?>
            <div class="list-group-item d-flex align-items-start gap-3 <?php echo empty($n['is_read']) ? 'bg-light' : ''; ?>">
                <div class="flex-shrink-0 mt-1">
                    <i class="bi bi-bell-fill <?php echo empty($n['is_read']) ? 'text-primary' : 'text-muted'; ?>" style="font-size:0.9rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <div style="font-size:0.9rem;line-height:1.4;"><?php echo htmlspecialchars($n['message'] ?? $n['title'] ?? 'Notification'); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars($n['created_at'] ?? ''); ?></small>
                </div>
                <div class="flex-shrink-0 d-flex gap-2">
                    <?php if (empty($n['is_read'])): ?>
                    <a href="notifications.php?id=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-success" title="Mark as Read">
                        <i class="bi bi-check"></i>
                    </a>
                    <?php endif; ?>
                    <a href="notifications.php?delete=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this notification?');">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
