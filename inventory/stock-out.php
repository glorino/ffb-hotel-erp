<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

$page_title = 'Stock Out';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/inventory-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) throw new Exception('Invalid security token.');
        $item_id = (int)($_POST['item_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $reason = sanitize($_POST['reason'] ?? '');
        $reference = sanitize($_POST['reference'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$item_id || $quantity <= 0) throw new Exception('Invalid item or quantity.');

        $stmt = $db->prepare("SELECT id, name, quantity, unit FROM inventory_items WHERE id = ?" . ($branch_id ? " AND branch_id = " . (int)$branch_id : ""));
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        if (!$item) throw new Exception('Item not found.');
        if ($item['quantity'] < $quantity) throw new Exception('Insufficient stock. Available: ' . number_format($item['quantity']) . ' ' . $item['unit']);

        $stmt = $db->prepare("INSERT INTO stock_movements (item_id, branch_id, type, quantity, reason, reference, notes, created_by) VALUES (?, ?, 'out', ?, ?, ?, ?, ?)");
        $stmt->execute([$item_id, $branch_id ?: $_SESSION['branch_id'], $quantity, $reason, $reference, $notes, $_SESSION['user_id']]);

        $stmt = $db->prepare("UPDATE inventory_items SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $item_id]);

        log_audit('stock_out', 'inventory', $item_id, null, ['quantity' => $quantity, 'reason' => $reason]);
        set_flash('success', 'Stock out recorded successfully.');
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    header('Location: stock-out.php');
    exit;
}

$items = [];
try {
    $sql = "SELECT id, name, unit, quantity FROM inventory_items WHERE status = 'active'" . ($branch_id ? " AND branch_id = " . (int)$branch_id : "") . " AND quantity > 0 ORDER BY name";
    $stmt = $db->query($sql);
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$movements = [];
try {
    $sql = "SELECT sm.*, i.name as item_name, i.unit, u.full_name as user_name FROM stock_movements sm JOIN inventory_items i ON sm.item_id = i.id LEFT JOIN users u ON sm.created_by = u.id WHERE sm.type = 'out'" . ($branch_id ? " AND sm.branch_id = " . (int)$branch_id : "") . " AND DATE(sm.created_at) >= ? AND DATE(sm.created_at) <= ? ORDER BY sm.created_at DESC LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute([$from_date, $to_date]);
    $movements = $stmt->fetchAll();
} catch (Exception $e) {
}

$reasons = ['kitchen_use', 'room_supply', 'waste', 'breakage', 'transfer', 'other'];
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Stock Out</li>
        </ol>
    </nav>

    <?php flash(); ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-arrow-up-circle text-warning me-2"></i>Record Stock Out</h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return checkStock()">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
                            <select name="item_id" id="item_id" class="form-select" required onchange="updateStockInfo()">
                                <option value="">-- Select Item --</option>
                                <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>" data-qty="<?php echo $item['quantity']; ?>" data-unit="<?php echo htmlspecialchars($item['unit']); ?>">
                                    <?php echo htmlspecialchars($item['name']); ?> (<?php echo number_format($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="stockInfo" class="form-text mt-1"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control" required step="0.01" min="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                            <select name="reason" class="form-select" required>
                                <option value="">-- Select Reason --</option>
                                <?php foreach ($reasons as $r): ?>
                                <option value="<?php echo $r; ?>"><?php echo ucwords(str_replace('_', ' ', $r)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reference</label>
                            <input type="text" name="reference" class="form-control" placeholder="e.g. KIT-001, RML-005">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100"><i class="bi bi-check-lg"></i> Record Stock Out</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Stock Out History</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">From</label>
                            <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">To</label>
                            <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-navy w-100"><i class="bi bi-filter"></i></button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Item</th><th>Qty</th><th>Reason</th><th>Reference</th><th>Date</th><th>By</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movements)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No stock out records found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($movements as $m): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($m['item_name']); ?></td>
                                    <td><span class="badge bg-warning text-dark">-<?php echo number_format($m['quantity']); ?> <?php echo htmlspecialchars($m['unit']); ?></span></td>
                                    <td><span class="badge bg-light text-dark"><?php echo ucwords(str_replace('_', ' ', $m['reason'] ?? 'N/A')); ?></span></td>
                                    <td><?php echo htmlspecialchars($m['reference'] ?? '-'); ?></td>
                                    <td><small class="text-muted"><?php echo formatDateTime($m['created_at']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($m['user_name'] ?? 'N/A'); ?></small></td>
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
function updateStockInfo() {
    var sel = document.getElementById('item_id');
    var opt = sel.options[sel.selectedIndex];
    var info = document.getElementById('stockInfo');
    if (opt && opt.value) {
        info.innerHTML = '<span class="text-muted">Available: <strong>' + parseFloat(opt.dataset.qty).toLocaleString() + '</strong> ' + opt.dataset.unit + '</span>';
    } else {
        info.innerHTML = '';
    }
}

function checkStock() {
    var sel = document.getElementById('item_id');
    var opt = sel.options[sel.selectedIndex];
    var qty = parseFloat(document.getElementById('quantity').value);
    if (opt && opt.dataset.qty !== undefined && qty > parseFloat(opt.dataset.qty)) {
        alert('Insufficient stock! Available: ' + parseFloat(opt.dataset.qty).toLocaleString() + ' ' + opt.dataset.unit);
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
