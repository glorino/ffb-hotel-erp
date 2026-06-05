<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['housekeeping', 'admin', 'branch_manager']);

$page_title = 'Room Supplies - Housekeeping';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/housekeeping-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND branch_id = " . (int)$branch_id : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) throw new Exception('Invalid security token.');
        $item_id = (int)($_POST['item_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$item_id || $quantity <= 0) throw new Exception('Invalid parameters.');

        $stmt = $db->prepare("SELECT quantity FROM inventory_items WHERE id = ? AND status = 'active' $branch_filter");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        if (!$item) throw new Exception('Item not found.');
        if ($item['quantity'] < $quantity) throw new Exception('Insufficient stock.');

        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE inventory_items SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $item_id]);
        $stmt = $db->prepare("INSERT INTO stock_movements (item_id, branch_id, type, quantity, reason, notes, created_by) VALUES (?, ?, 'out', ?, 'room_supply', ?, ?)");
        $stmt->execute([$item_id, $branch_id ?: $_SESSION['branch_id'], $quantity, $notes, $_SESSION['user_id']]);
        $stmt = $db->prepare("INSERT INTO housekeeping_supply_usage (item_id, branch_id, quantity, notes, used_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$item_id, $branch_id ?: $_SESSION['branch_id'], $quantity, $notes, $_SESSION['user_id']]);
        $db->commit();
        set_flash('success', 'Supplies taken successfully.');
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        set_flash('danger', $e->getMessage());
    }
    header('Location: room-supplies.php');
    exit;
}

$items = [];
try {
    $sql = "SELECT id, name, unit, quantity, reorder_level, category FROM inventory_items WHERE status = 'active' $branch_filter AND (category IN ('cleaning', 'room_supplies', 'amenities', 'linen', 'toiletries') OR name LIKE '%soap%' OR name LIKE '%towel%' OR name LIKE '%sheet%' OR name LIKE '%shampoo%' OR name LIKE '%clean%' OR name LIKE '%detergent%' OR name LIKE '%glove%' OR name LIKE '%bag%') ORDER BY name";
    $stmt = $db->query($sql);
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}

$usage_log = [];
try {
    $sql = "SELECT hsu.*, i.name as item_name, i.unit, u.full_name as user_name FROM housekeeping_supply_usage hsu JOIN inventory_items i ON hsu.item_id = i.id LEFT JOIN users u ON hsu.used_by = u.id WHERE 1=1 " . ($branch_id ? "AND hsu.branch_id = " . (int)$branch_id : "") . " ORDER BY hsu.created_at DESC LIMIT 50";
    $stmt = $db->query($sql);
    $usage_log = $stmt->fetchAll();
} catch (Exception $e) {
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Room Supplies</li>
        </ol>
    </nav>

    <?php flash(); ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Take Supplies</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
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
                            <input type="text" name="notes" class="form-control" placeholder="e.g. For floor 3 rooms">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Take Supplies</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Available Supplies</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Item</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Reorder Level</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No supplies found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <?php $is_low = $item['quantity'] <= $item['reorder_level']; ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></span></td>
                                    <td class="<?php echo $is_low ? 'text-danger fw-bold' : ''; ?>"><?php echo number_format($item['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($item['reorder_level']); ?></td>
                                    <td><?php echo $is_low ? '<span class="badge bg-danger">Reorder</span>' : '<span class="badge bg-success">OK</span>'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (!empty($usage_log)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Usage History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Quantity</th><th>Notes</th><th>Date</th><th>By</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usage_log as $u): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($u['item_name']); ?></td>
                                    <td><?php echo number_format($u['quantity']); ?> <?php echo htmlspecialchars($u['unit']); ?></td>
                                    <td><small><?php echo htmlspecialchars($u['notes'] ?? '-'); ?></small></td>
                                    <td><small class="text-muted"><?php echo formatDateTime($u['created_at']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($u['user_name'] ?? 'N/A'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
