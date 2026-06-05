<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['housekeeping', 'admin', 'branch_manager']);

$page_title = 'Cleaned Rooms';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/housekeeping-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND r.branch_id = " . (int)$branch_id : "";

$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

if (isset($_GET['verify']) && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("UPDATE housekeeping_logs SET verified_by = ?, verified_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], (int)$_GET['id']]);
        set_flash('success', 'Room cleaning verified.');
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    header('Location: cleaned-rooms.php');
    exit;
}

$logs = [];
try {
    $sql = "SELECT hl.*, r.room_number, r.floor, rt.name as room_type_name, u.full_name as cleaner_name, vu.full_name as verifier_name FROM housekeeping_logs hl JOIN rooms r ON hl.room_id = r.id LEFT JOIN room_types rt ON r.room_type_id = rt.id LEFT JOIN users u ON hl.cleaned_by = u.id LEFT JOIN users vu ON hl.verified_by = vu.id WHERE DATE(hl.cleaned_at) >= ? AND DATE(hl.cleaned_at) <= ? $branch_filter ORDER BY hl.cleaned_at DESC LIMIT 200";
    $stmt = $db->prepare($sql);
    $stmt->execute([$from_date, $to_date]);
    $logs = $stmt->fetchAll();
} catch (Exception $e) {
}

$is_supervisor = in_array($_SESSION['role_slug'], ['admin', 'branch_manager']);
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Cleaned Rooms</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="bi bi-check2-all me-2"></i>Cleaned Rooms</h4>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-navy w-100"><i class="bi bi-filter"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="cleaned-rooms.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Room</th>
                            <th>Floor</th>
                            <th>Type</th>
                            <th>Cleaned By</th>
                            <th>Cleaned At</th>
                            <th>Notes</th>
                            <th>Verification</th>
                            <?php if ($is_supervisor): ?><th class="text-end">Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="<?php echo $is_supervisor ? 8 : 7; ?>" class="text-center py-5 text-muted">No cleaned room records found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($log['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($log['floor'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($log['room_type_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($log['cleaner_name'] ?? 'N/A'); ?></td>
                            <td><small class="text-muted"><?php echo formatDateTime($log['cleaned_at']); ?></small></td>
                            <td><small><?php echo htmlspecialchars(truncate($log['notes'] ?? '-', 40)); ?></small></td>
                            <td>
                                <?php if ($log['verified_at']): ?>
                                <span class="badge bg-success">Verified by <?php echo htmlspecialchars($log['verifier_name'] ?? 'N/A'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($is_supervisor): ?>
                            <td class="text-end">
                                <?php if (!$log['verified_at']): ?>
                                <a href="cleaned-rooms.php?verify=1&id=<?php echo $log['id']; ?>" class="btn btn-sm btn-outline-success confirm-link"><i class="bi bi-check-lg"></i> Verify</a>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.confirm-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Verify this cleaning record?')) window.location.href = this.href;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
