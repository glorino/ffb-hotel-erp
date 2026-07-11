<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['waiter']);

$page_title = 'Customer Requests';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (isset($_GET['acknowledge']) && is_numeric($_GET['acknowledge'])) {
    $req_id = (int)$_GET['acknowledge'];
    try {
        $stmt = $db->prepare("UPDATE customer_requests SET status = 'acknowledged', acknowledged_by = ?, acknowledged_at = NOW() WHERE id = ? AND branch_id = ? AND status = 'pending'");
        $stmt->execute([$user_id, $req_id, $branch_id]);
        if ($stmt->rowCount()) {
            set_flash('success', 'Request acknowledged.');
        }
    } catch (Exception $e) {
        error_log('Acknowledge request error: ' . $e->getMessage());
        set_flash('danger', 'Failed to acknowledge request.');
    }
    header('Location: customer-requests.php');
    exit;
}

if (isset($_GET['resolve']) && is_numeric($_GET['resolve'])) {
    $req_id = (int)$_GET['resolve'];
    try {
        $stmt = $db->prepare("UPDATE customer_requests SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ? AND branch_id = ? AND status IN ('pending', 'acknowledged')");
        $stmt->execute([$user_id, $req_id, $branch_id]);
        if ($stmt->rowCount()) {
            set_flash('success', 'Request marked as resolved.');
        }
    } catch (Exception $e) {
        error_log('Resolve request error: ' . $e->getMessage());
        set_flash('danger', 'Failed to resolve request.');
    }
    header('Location: customer-requests.php');
    exit;
}

$status_filter = $_GET['status'] ?? 'pending';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Customer Requests</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="mb-0 fw-semibold">Customer Assistance Requests</h4>
        <div class="d-flex gap-1">
            <a href="?status=pending" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
            <a href="?status=acknowledged" class="btn btn-sm <?php echo $status_filter === 'acknowledged' ? 'btn-info' : 'btn-outline-info'; ?>">Acknowledged</a>
            <a href="?status=resolved" class="btn btn-sm <?php echo $status_filter === 'resolved' ? 'btn-success' : 'btn-outline-success'; ?>">Resolved</a>
            <a href="?status=all" class="btn btn-sm <?php echo $status_filter === 'all' ? 'btn-dark' : 'btn-outline-secondary'; ?>">All</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">
                <?php echo ucfirst($status_filter); ?> Requests
            </h5>
            <span class="text-muted small">
                <?php
                try {
                    $count_sql = "SELECT COUNT(*) FROM customer_requests WHERE branch_id = ?";
                    $count_params = [$branch_id];
                    if ($status_filter !== 'all') {
                        $count_sql .= " AND status = ?";
                        $count_params[] = $status_filter;
                    }
                    $stmt = $db->prepare($count_sql);
                    $stmt->execute($count_params);
                    echo $stmt->fetchColumn() . ' requests';
                } catch (Exception $e) {}
                ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Table</th>
                            <th>Request Type</th>
                            <th>Details</th>
                            <th>Requested At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT * FROM customer_requests WHERE branch_id = ?";
                            $params = [$branch_id];
                            if ($status_filter !== 'all') {
                                $sql .= " AND status = ?";
                                $params[] = $status_filter;
                            }
                            $sql .= " ORDER BY created_at DESC LIMIT 100";

                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $requests = $stmt->fetchAll();

                            if (empty($requests)):
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No customer requests found.</td>
                        </tr>
                            <?php else: ?>
                            <?php foreach ($requests as $i => $req):
                                $status_badges = [
                                    'pending' => 'warning',
                                    'acknowledged' => 'info',
                                    'resolved' => 'success'
                                ];
                                $sb = $status_badges[$req['status']] ?? 'secondary';

                                $request_types = [
                                    'waiter' => 'Call Waiter',
                                    'water' => 'Water',
                                    'menu' => 'Menu',
                                    'bill' => 'Bill',
                                    'complaint' => 'Complaint',
                                    'other' => 'Other'
                                ];
                                $rt = $request_types[$req['request_type']] ?? ucfirst($req['request_type']);
                            ?>
                            <tr class="<?php echo $req['status'] === 'pending' ? 'table-warning' : ($req['status'] === 'acknowledged' ? 'table-info' : ''); ?>">
                                <td><?php echo $i + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($req['table_number'] ?? 'N/A'); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($rt); ?></span></td>
                                <td><small><?php echo htmlspecialchars(truncate($req['description'] ?? '-', 80)); ?></small></td>
                                <td><small class="text-muted"><?php echo timeAgo($req['created_at']); ?></small></td>
                                <td><span class="badge bg-<?php echo $sb; ?>"><?php echo $req['status']; ?></span></td>
                                <td>
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <a href="?acknowledge=<?php echo $req['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-check2"></i> Acknowledge
                                        </a>
                                    <?php elseif ($req['status'] === 'acknowledged'): ?>
                                        <a href="?resolve=<?php echo $req['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-check2-all"></i> Resolve
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="bi bi-check-circle"></i> Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        <?php } catch (Exception $e) {
                            error_log('Customer requests error: ' . $e->getMessage());
                            echo '<tr><td colspan="7" class="text-center py-4 text-danger">Error loading requests. The customer_requests table may not exist.</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
