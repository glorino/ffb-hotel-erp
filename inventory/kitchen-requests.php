<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager', 'kitchen_chef']);

$page_title = 'Kitchen Requests';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $action = $_GET['action'];
        $stmt = $db->prepare("SELECT kr.*, i.name as item_name, i.unit, i.quantity as available_qty FROM kitchen_requests kr JOIN inventory_items i ON kr.item_id = i.id WHERE kr.id = ?");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) throw new Exception('Request not found.');

        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE kitchen_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            set_flash('success', 'Request approved.');
        } elseif ($action === 'fulfill') {
            if ($req['available_qty'] < $req['quantity']) throw new Exception('Insufficient stock. Available: ' . number_format($req['available_qty']) . ' ' . $req['unit']);
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE inventory_items SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$req['quantity'], $req['item_id']]);
            $stmt = $db->prepare("INSERT INTO stock_movements (item_id, branch_id, type, quantity, reason, notes, created_by) VALUES (?, ?, 'out', ?, 'kitchen_use', ?, ?)");
            $stmt->execute([$req['item_id'], $branch_id ?: $req['branch_id'], $req['quantity'], 'Fulfilled kitchen request #' . $id, $_SESSION['user_id']]);
            $stmt = $db->prepare("UPDATE kitchen_requests SET status = 'fulfilled', fulfilled_by = ?, fulfilled_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            $db->commit();
            set_flash('success', 'Request fulfilled and stock deducted.');
        } elseif ($action === 'reject') {
            $stmt = $db->prepare("UPDATE kitchen_requests SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            set_flash('success', 'Request rejected.');
        }
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        set_flash('danger', $e->getMessage());
    }
    header('Location: kitchen-requests.php');
    exit;
}

$status_filter = $_GET['status'] ?? '';
$status_where = $status_filter ? "AND kr.status = " . $db->quote($status_filter) : "";

$requests = [];
try {
    $sql = "SELECT kr.*, i.name as item_name, i.unit, u.full_name as requested_by_name FROM kitchen_requests kr JOIN inventory_items i ON kr.item_id = i.id LEFT JOIN users u ON kr.requested_by = u.id WHERE 1=1 " . ($branch_id ? "AND kr.branch_id = " . (int)$branch_id : "") . " $status_where ORDER BY CASE kr.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'fulfilled' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END, kr.created_at DESC LIMIT 100";
    $stmt = $db->query($sql);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Kitchen Requests</li>
        </ol>
    </nav>

    <?php flash(); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="bi bi-utensils me-2"></i>Kitchen Supply Requests</h4>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" onchange="window.location.href='kitchen-requests.php?status='+this.value" style="width: auto;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="fulfilled" <?php echo $status_filter === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Urgency</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No kitchen requests found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td class="fw-medium"><?php echo htmlspecialchars($r['item_name']); ?></td>
                            <td><?php echo number_format($r['quantity']); ?> <?php echo htmlspecialchars($r['unit']); ?></td>
                            <td>
                                <?php if ($r['urgency'] === 'urgent'): ?><span class="badge bg-danger">Urgent</span>
                                <?php elseif ($r['urgency'] === 'high'): ?><span class="badge bg-warning text-dark">High</span>
                                <?php else: ?><span class="badge bg-info"><?php echo ucfirst($r['urgency'] ?? 'Normal'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo htmlspecialchars($r['requested_by_name'] ?? 'N/A'); ?></small></td>
                            <td><small class="text-muted"><?php echo formatDateTime($r['created_at']); ?></small></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?><span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif ($r['status'] === 'approved'): ?><span class="badge bg-info">Approved</span>
                                <?php elseif ($r['status'] === 'fulfilled'): ?><span class="badge bg-success">Fulfilled</span>
                                <?php else: ?><span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($r['status'] === 'pending'): ?>
                                <a href="kitchen-requests.php?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-info confirm-link" title="Approve"><i class="bi bi-check-lg"></i></a>
                                <a href="kitchen-requests.php?action=fulfill&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-success confirm-link" title="Fulfill"><i class="bi bi-check2-all"></i></a>
                                <a href="kitchen-requests.php?action=reject&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger confirm-link" title="Reject"><i class="bi bi-x-lg"></i></a>
                                <?php elseif ($r['status'] === 'approved'): ?>
                                <a href="kitchen-requests.php?action=fulfill&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-success confirm-link" title="Fulfill"><i class="bi bi-check2-all"></i></a>
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

<script>
document.querySelectorAll('.confirm-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var msg = 'Are you sure?';
        if (this.querySelector('.bi-check2-all')) msg = 'This will deduct stock and mark as fulfilled. Continue?';
        else if (this.querySelector('.bi-x-lg')) msg = 'Reject this request?';
        if (confirm(msg)) window.location.href = this.href;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
