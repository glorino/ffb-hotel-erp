<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Send Notification';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$roles = [];
$branches = [];
try {
    $stmt = $db->query("SELECT id, name, slug FROM roles ORDER BY name");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
try {
    $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name");
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $target_type = $_POST['target_type'] ?? 'all';
    $target_role = $_POST['target_role'] ?? '';
    $target_branch = $_POST['target_branch'] ?? '';

    if (empty($title) || empty($message)) {
        $error_msg = 'Title and message are required.';
    } else {
        try {
            $sql = "SELECT id FROM users WHERE 1=1";
            $params = [];
            if ($target_type === 'role' && !empty($target_role)) {
                $sql .= " AND role_id = ?";
                $params[] = $target_role;
            }
            if ($target_type === 'branch' && !empty($target_branch)) {
                $sql .= " AND branch_id = ?";
                $params[] = $target_branch;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $ins = $db->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'system', CURRENT_TIMESTAMP)");
            foreach ($users as $u) {
                $ins->execute([$u['id'], $title, $message]);
                $count++;
            }

            log_audit('send_notification', 'notification', 0, null, ['title' => $title, 'recipients' => $count]);
            $success_msg = "Notification sent to {$count} recipient(s).";
        } catch (Exception $e) {
            error_log('Send notification error: ' . $e->getMessage());
            $error_msg = 'Failed to send notification. Please try again.';
        }
    }
}
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
        <li class="breadcrumb-item active">Send Notification</li>
    </ol>
</nav>

<?php if ($success_msg): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle-fill alert-icon"></i>
    <span><?php echo $success_msg; ?></span>
</div>
<?php endif; ?>
<?php if ($error_msg): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-circle-fill alert-icon"></i>
    <span><?php echo $error_msg; ?></span>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-megaphone me-2" style="color:var(--gold);"></i>Compose Notification</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notification Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Important Staff Meeting" required maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Write your notification message here..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Send To</label>
                        <select name="target_type" class="form-select" id="targetType">
                            <option value="all">All Users</option>
                            <option value="role">Specific Role</option>
                            <option value="branch">Specific Branch</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="roleSelect">
                        <label class="form-label fw-semibold">Select Role</label>
                        <select name="target_role" class="form-select">
                            <option value="">Choose a role...</option>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="branchSelect">
                        <label class="form-label fw-semibold">Select Branch</label>
                        <select name="target_branch" class="form-select">
                            <option value="">Choose a branch...</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send me-1"></i> Send Notification</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-info-circle me-2" style="color:var(--info);"></i>Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0" style="font-size:0.88rem;">
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Keep titles short and clear</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Use specific roles for targeted messages</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Branch notifications reach only that location</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>"All Users" sends to everyone in the system</li>
                    <li><i class="bi bi-check2 text-success me-2"></i>Notifications appear in the bell icon</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('targetType')?.addEventListener('change', function() {
    document.getElementById('roleSelect').classList.toggle('d-none', this.value !== 'role');
    document.getElementById('branchSelect').classList.toggle('d-none', this.value !== 'branch');
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
