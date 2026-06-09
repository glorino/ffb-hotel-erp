<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

$page_title = 'Branch Transfers';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) throw new Exception('Invalid security token.');
        $from_branch = (int)($_POST['from_branch'] ?? 0);
        $to_branch = (int)($_POST['to_branch'] ?? 0);
        $item_id = (int)($_POST['item_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$from_branch || !$to_branch || !$item_id || $quantity <= 0) throw new Exception('All fields are required.');
        if ($from_branch === $to_branch) throw new Exception('Cannot transfer to the same branch.');

        $stmt = $db->prepare("SELECT quantity, name FROM inventory_items WHERE id = ? AND branch_id = ?");
        $stmt->execute([$item_id, $from_branch]);
        $item = $stmt->fetch();
        if (!$item) throw new Exception('Item not found in source branch.');
        if ($item['quantity'] < $quantity) throw new Exception('Insufficient stock. Available: ' . number_format($item['quantity']));

        $stmt = $db->prepare("INSERT INTO branch_transfers (from_branch, to_branch, item_id, quantity, notes, requested_by, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$from_branch, $to_branch, $item_id, $quantity, $notes, $_SESSION['user_id']]);

        log_audit('transfer_request', 'inventory', $item_id, null, ['from' => $from_branch, 'to' => $to_branch, 'quantity' => $quantity]);
        set_flash('success', 'Transfer request submitted successfully.');
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    header('Location: branch-transfers.php');
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $action = $_GET['action'];
        $stmt = $db->prepare("SELECT * FROM branch_transfers WHERE id = ?");
        $stmt->execute([$id]);
        $transfer = $stmt->fetch();
        if (!$transfer) throw new Exception('Transfer not found.');

        if ($action === 'approve') {
            if ($transfer['to_branch'] != $branch_id && $_SESSION['role_slug'] !== 'admin') throw new Exception('You cannot approve this transfer.');
            $stmt = $db->prepare("UPDATE branch_transfers SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            set_flash('success', 'Transfer approved.');
        } elseif ($action === 'reject') {
            if ($transfer['to_branch'] != $branch_id && $_SESSION['role_slug'] !== 'admin') throw new Exception('You cannot reject this transfer.');
            $stmt = $db->prepare("UPDATE branch_transfers SET status = 'rejected', approved_at = NOW(), approved_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            set_flash('success', 'Transfer rejected.');
        } elseif ($action === 'complete') {
            if ($transfer['to_branch'] != $branch_id && $_SESSION['role_slug'] !== 'admin') throw new Exception('You cannot complete this transfer.');
            $stmt = $db->prepare("SELECT quantity FROM inventory_items WHERE id = ? AND branch_id = ?");
            $stmt->execute([$transfer['item_id'], $transfer['from_branch']]);
            $source = $stmt->fetch();
            if (!$source || $source['quantity'] < $transfer['quantity']) throw new Exception('Insufficient stock in source branch.');

            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE inventory_items SET quantity = quantity - ? WHERE id = ? AND branch_id = ?");
            $stmt->execute([$transfer['quantity'], $transfer['item_id'], $transfer['from_branch']]);
            $stmt = $db->prepare("INSERT INTO inventory_items (branch_id, name, category, unit, quantity, reorder_level, price_per_unit, supplier_id, status) SELECT ?, name, category, unit, 0, reorder_level, price_per_unit, supplier_id, 'active' FROM inventory_items WHERE id = ? ON CONFLICT (branch_id, name) DO UPDATE SET quantity = inventory_items.quantity + ?");
            $stmt->execute([$transfer['to_branch'], $transfer['item_id'], $transfer['quantity']]);
            $stmt = $db->prepare("UPDATE branch_transfers SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $stmt = $db->prepare("INSERT INTO stock_movements (item_id, branch_id, type, quantity, reference_type, reference_id, notes, created_by) VALUES (?, ?, 'transfer', ?, 'transfer', ?, ?, ?)");
            $stmt->execute([$transfer['item_id'], $transfer['from_branch'], $transfer['quantity'], $id, 'Transfer out: ' . $transfer['notes'], $_SESSION['user_id']]);
            $stmt = $db->prepare("INSERT INTO stock_movements (item_id, branch_id, type, quantity, reference_type, reference_id, notes, created_by) VALUES (?, ?, 'transfer', ?, 'transfer', ?, ?, ?)");
            $stmt->execute([$transfer['item_id'], $transfer['to_branch'], $transfer['quantity'], $id, 'Transfer in: ' . $transfer['notes'], $_SESSION['user_id']]);
            $db->commit();
            set_flash('success', 'Transfer completed and stock updated.');
        }
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        set_flash('danger', $e->getMessage());
    }
    header('Location: branch-transfers.php');
    exit;
}

$branches = [];
try {
    $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name");
    $branches = $stmt->fetchAll();
} catch (Exception $e) {
}

$items = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['from_branch']) && !empty($_GET['from_branch'])) {
    try {
        $stmt = $db->prepare("SELECT id, name, unit, quantity FROM inventory_items WHERE status = 'active' AND branch_id = ? AND quantity > 0 ORDER BY name");
        $stmt->execute([(int)$_GET['from_branch']]);
        $items = $stmt->fetchAll();
    } catch (Exception $e) {
    }
}

$status_filter = $_GET['status'] ?? '';
$status_where = $status_filter ? "AND bt.status = " . $db->quote($status_filter) : "";

$transfers = [];
try {
    $sql = "SELECT bt.*, fb.name as from_branch_name, tb.name as to_branch_name, i.name as item_name, i.unit, u.full_name as requester_name FROM branch_transfers bt JOIN branches fb ON bt.from_branch = fb.id JOIN branches tb ON bt.to_branch = tb.id JOIN inventory_items i ON bt.item_id = i.id LEFT JOIN users u ON bt.requested_by = u.id WHERE (bt.from_branch = ? OR bt.to_branch = ?) $status_where ORDER BY bt.created_at DESC LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute([$branch_id, $branch_id]);
    $transfers = $stmt->fetchAll();
} catch (Exception $e) {
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Branch Transfers</li>
        </ol>
    </nav>

    <?php flash(); ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-arrow-left-right me-2"></i>New Transfer Request</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">From Branch <span class="text-danger">*</span></label>
                            <select name="from_branch" class="form-select" required onchange="this.form.submit()">
                                <option value="">-- Select --</option>
                                <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php echo (isset($_GET['from_branch']) && (int)$_GET['from_branch'] === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">To Branch <span class="text-danger">*</span></label>
                            <select name="to_branch" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select" required>
                                <option value="">-- Select Item --</option>
                                <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?> (<?php echo number_format($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" required step="0.01" min="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Reason for transfer..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-navy w-100"><i class="bi bi-send"></i> Submit Transfer Request</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Transfer History</h5>
                    <div>
                        <select class="form-select form-select-sm" onchange="window.location.href='branch-transfers.php?status='+this.value">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Item</th><th>From</th><th>To</th><th>Qty</th><th>Status</th><th>Date</th><th>By</th><th class="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transfers)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No transfers found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($transfers as $t): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($t['item_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars($t['from_branch_name']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($t['to_branch_name']); ?></small></td>
                                    <td><?php echo number_format($t['quantity']); ?> <?php echo htmlspecialchars($t['unit']); ?></td>
                                    <td>
                                        <?php if ($t['status'] === 'pending'): ?><span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($t['status'] === 'approved'): ?><span class="badge bg-info">Approved</span>
                                        <?php elseif ($t['status'] === 'completed'): ?><span class="badge bg-success">Completed</span>
                                        <?php else: ?><span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo formatDateTime($t['created_at']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($t['requester_name'] ?? 'N/A'); ?></small></td>
                                    <td class="text-end">
                                        <?php if ($t['status'] === 'pending' && ($t['to_branch'] == $branch_id || $_SESSION['role_slug'] === 'admin')): ?>
                                        <a href="branch-transfers.php?action=approve&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-success confirm-link" title="Approve"><i class="bi bi-check-lg"></i></a>
                                        <a href="branch-transfers.php?action=reject&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger confirm-link" title="Reject"><i class="bi bi-x-lg"></i></a>
                                        <?php endif; ?>
                                        <?php if ($t['status'] === 'approved' && ($t['to_branch'] == $branch_id || $_SESSION['role_slug'] === 'admin')): ?>
                                        <a href="branch-transfers.php?action=complete&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary confirm-link" title="Complete Transfer"><i class="bi bi-check2-all"></i></a>
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
        var msg = this.querySelector('.bi-check2-all') ? 'Complete this transfer? Stock will be moved.' : 'Are you sure?';
        if (confirm(msg)) window.location.href = this.href;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
