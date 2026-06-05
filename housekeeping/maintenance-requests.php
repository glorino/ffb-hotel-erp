<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['housekeeping', 'admin', 'branch_manager']);

$page_title = 'Maintenance Requests';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/housekeeping-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) throw new Exception('Invalid security token.');
        $room_id = (int)($_POST['room_id'] ?? 0);
        $issue_type = sanitize($_POST['issue_type'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'medium');

        if (!$room_id || !$issue_type || !$description) throw new Exception('Please fill all required fields.');

        $stmt = $db->prepare("INSERT INTO maintenance_requests (room_id, branch_id, issue_type, description, priority, reported_by, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$room_id, $branch_id ?: $_SESSION['branch_id'], $issue_type, $description, $priority, $_SESSION['user_id']]);

        log_audit('maintenance_request', 'room', $room_id, null, ['issue' => $issue_type, 'priority' => $priority]);
        set_flash('success', 'Maintenance request submitted.');
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    header('Location: maintenance-requests.php');
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        if ($_GET['action'] === 'resolve') {
            $stmt = $db->prepare("UPDATE maintenance_requests SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            set_flash('success', 'Request marked as resolved.');
        } elseif ($_GET['action'] === 'in_progress') {
            $stmt = $db->prepare("UPDATE maintenance_requests SET status = 'in_progress', assigned_to = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            set_flash('success', 'Request marked as in progress.');
        }
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    header('Location: maintenance-requests.php');
    exit;
}

$rooms = [];
try {
    $stmt = $db->query("SELECT id, room_number, floor FROM rooms WHERE status != 'out_of_service'" . ($branch_id ? " AND branch_id = " . (int)$branch_id : "") . " ORDER BY room_number");
    $rooms = $stmt->fetchAll();
} catch (Exception $e) {
}

$status_filter = $_GET['status'] ?? '';
$status_where = $status_filter ? "AND mr.status = " . $db->quote($status_filter) : "";

$requests = [];
try {
    $sql = "SELECT mr.*, r.room_number, r.floor, u.full_name as reporter_name FROM maintenance_requests mr JOIN rooms r ON mr.room_id = r.id LEFT JOIN users u ON mr.reported_by = u.id WHERE 1=1 " . ($branch_id ? "AND mr.branch_id = " . (int)$branch_id : "") . " $status_where ORDER BY CASE mr.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END, mr.created_at DESC LIMIT 100";
    $stmt = $db->query($sql);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
}

$issue_types = ['plumbing', 'electrical', 'ac', 'furniture', 'painting', 'pest_control', 'other'];
$priorities = ['low', 'medium', 'high', 'urgent'];
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Maintenance Requests</li>
        </ol>
    </nav>

    <?php flash(); ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-tools me-2"></i>Report Issue</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Room <span class="text-danger">*</span></label>
                            <select name="room_id" class="form-select" required>
                                <option value="">-- Select Room --</option>
                                <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">Room <?php echo htmlspecialchars($room['room_number']); ?> (Floor <?php echo htmlspecialchars($room['floor'] ?? 'N/A'); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Issue Type <span class="text-danger">*</span></label>
                            <select name="issue_type" class="form-select" required>
                                <option value="">-- Select Issue --</option>
                                <?php foreach ($issue_types as $t): ?>
                                <option value="<?php echo $t; ?>"><?php echo ucwords(str_replace('_', ' ', $t)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="Describe the issue in detail..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <?php foreach ($priorities as $p): ?>
                                <option value="<?php echo $p; ?>" <?php echo $p === 'medium' ? 'selected' : ''; ?>><?php echo ucfirst($p); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-navy w-100"><i class="bi bi-send"></i> Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Request List</h5>
                    <div>
                        <select class="form-select form-select-sm" onchange="window.location.href='maintenance-requests.php?status='+this.value" style="width: auto;">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Room</th><th>Floor</th><th>Issue</th><th>Type</th><th>Priority</th><th>Reported</th><th>Status</th><th class="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No maintenance requests.</td></tr>
                                <?php else: ?>
                                <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($r['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($r['floor'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo htmlspecialchars(truncate($r['description'], 50)); ?></small></td>
                                    <td><span class="badge bg-light text-dark"><?php echo ucwords(str_replace('_', ' ', $r['issue_type'])); ?></span></td>
                                    <td>
                                        <?php if ($r['priority'] === 'urgent'): ?><span class="badge bg-danger">Urgent</span>
                                        <?php elseif ($r['priority'] === 'high'): ?><span class="badge bg-warning text-dark">High</span>
                                        <?php elseif ($r['priority'] === 'medium'): ?><span class="badge bg-info">Medium</span>
                                        <?php else: ?><span class="badge bg-secondary">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo formatDateTime($r['created_at']); ?></small></td>
                                    <td>
                                        <?php if ($r['status'] === 'pending'): ?><span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($r['status'] === 'in_progress'): ?><span class="badge bg-info">In Progress</span>
                                        <?php else: ?><span class="badge bg-success">Resolved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($r['status'] === 'pending'): ?>
                                        <a href="maintenance-requests.php?action=in_progress&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-info confirm-link" title="Start"><i class="bi bi-play-fill"></i></a>
                                        <a href="maintenance-requests.php?action=resolve&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-success confirm-link" title="Resolve"><i class="bi bi-check-lg"></i></a>
                                        <?php elseif ($r['status'] === 'in_progress'): ?>
                                        <a href="maintenance-requests.php?action=resolve&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-success confirm-link" title="Resolve"><i class="bi bi-check-lg"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.confirm-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var msg = 'Are you sure?';
        if (this.querySelector('.bi-play-fill')) msg = 'Start working on this request?';
        else if (this.querySelector('.bi-check-lg')) msg = 'Mark this request as resolved?';
        if (confirm(msg)) window.location.href = this.href;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
