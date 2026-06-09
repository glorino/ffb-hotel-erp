<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

$page_title = 'Stock In';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) throw new Exception('Invalid security token.');
        $item_id = (int)($_POST['item_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $unit_price = (float)($_POST['unit_price'] ?? 0);
        $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $reference = sanitize($_POST['reference'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$item_id || $quantity <= 0) throw new Exception('Invalid item or quantity.');

        $sql = $branch_id ? "INSERT INTO stock_movements (item_id, branch_id, type, quantity, unit_price, reference, supplier_id, notes, created_by) VALUES (?, ?, 'in', ?, ?, ?, ?, ?, ?)" : "INSERT INTO stock_movements (item_id, branch_id, type, quantity, unit_price, reference, supplier_id, notes, created_by) VALUES (?, ?, 'in', ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$item_id, $branch_id ?: $_SESSION['branch_id'], $quantity, $unit_price, $reference, $supplier_id, $notes, $_SESSION['user_id']]);

        $stmt = $db->prepare("UPDATE inventory_items SET quantity = quantity + ?, price_per_unit = CASE WHEN ? > 0 THEN ? ELSE price_per_unit END WHERE id = ?");
        $stmt->execute([$quantity, $unit_price, $unit_price, $item_id]);

        log_audit('stock_in', 'inventory', $item_id, null, ['quantity' => $quantity, 'reference' => $reference]);
        set_flash('success', 'Stock in recorded successfully.');
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    header('Location: stock-in.php');
    exit;
}

$preselected_item = (int)($_GET['item_id'] ?? 0);

$items = [];
try {
    $sql = "SELECT id, name, unit, quantity, reorder_level FROM inventory_items WHERE status = 'active'" . ($branch_id ? " AND branch_id = " . (int)$branch_id : "") . " ORDER BY name";
    $stmt = $db->query($sql);
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}

$suppliers = [];
try {
    $sql = "SELECT id, name FROM suppliers WHERE status = 'active'" . ($branch_id ? " AND branch_id = " . (int)$branch_id : "") . " ORDER BY name";
    $stmt = $db->query($sql);
    $suppliers = $stmt->fetchAll();
} catch (Exception $e) {
}

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$movements = [];
try {
    $sql = "SELECT sm.*, i.name as item_name, i.unit, u.full_name as user_name FROM stock_movements sm JOIN inventory_items i ON sm.item_id = i.id LEFT JOIN users u ON sm.created_by = u.id WHERE sm.type = 'in'" . ($branch_id ? " AND sm.branch_id = " . (int)$branch_id : "") . " AND DATE(sm.created_at) >= ? AND DATE(sm.created_at) <= ? ORDER BY sm.created_at DESC LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute([$from_date, $to_date]);
    $movements = $stmt->fetchAll();
} catch (Exception $e) {
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Stock In</li>
        </ol>
    </nav>

    <?php flash(); ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-arrow-down-circle text-success me-2"></i>Record Stock In</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select" required>
                                <option value="">-- Select Item --</option>
                                <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>" <?php echo $preselected_item === (int)$item['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['name']); ?> (<?php echo number_format($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" required step="0.01" min="0.01">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Unit Price (<?php echo CURRENCY_SYMBOL; ?>)</label>
                                <input type="number" name="unit_price" class="form-control" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">-- Select Supplier --</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reference (PO Number)</label>
                            <input type="text" name="reference" class="form-control" placeholder="e.g. PO-2024-001">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-gold w-100"><i class="bi bi-check-lg"></i> Record Stock In</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Stock In History</h5>
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
                                <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Reference</th><th>Date</th><th>By</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movements)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No stock in records found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($movements as $m): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($m['item_name']); ?></td>
                                    <td><span class="badge bg-success">+<?php echo number_format($m['quantity']); ?> <?php echo htmlspecialchars($m['unit']); ?></span></td>
                                    <td><?php echo $m['unit_price'] > 0 ? formatMoney($m['unit_price']) : '-'; ?></td>
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

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
