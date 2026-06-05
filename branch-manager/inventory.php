<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Branch Inventory';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/branch-manager-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_stock'])) {
    $item_name = sanitize($_POST['item_name'] ?? '');
    $quantity = (float)($_POST['quantity'] ?? 0);
    $unit = sanitize($_POST['unit'] ?? 'pcs');
    if ($item_name && $quantity > 0) {
        try {
            $stmt = $db->prepare("INSERT INTO inventory_requests (branch_id, item_name, quantity, unit, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$branch_id, $item_name, $quantity, $unit]);
            log_audit('request_stock', 'inventory_request', $db->lastInsertId());
            set_flash('success', 'Stock request submitted for approval');
        } catch (Exception $e) {
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
    } else {
        set_flash('warning', 'Please provide item name and valid quantity');
    }
    header('Location: inventory.php');
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Inventory</li>
        </ol>
    </nav>

    <?php
    $low_stock_items = [];
    $total_items = 0;
    $low_stock_count = 0;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ?");
        $stmt->execute([$branch_id]);
        $total_items = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND quantity <= reorder_level");
        $stmt->execute([$branch_id]);
        $low_stock_count = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT * FROM inventory WHERE branch_id = ? AND quantity <= reorder_level ORDER BY quantity ASC LIMIT 10");
        $stmt->execute([$branch_id]);
        $low_stock_items = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Inventory error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Total Items</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($total_items); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Low Stock Alerts</p>
                    <h3 class="stat-value mb-0 text-<?php echo $low_stock_count > 0 ? 'danger' : 'success'; ?>"><?php echo number_format($low_stock_count); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Pending Requests</p>
                    <?php
                    $pending_req = 0;
                    try {
                        $stmt = $db->prepare("SELECT COUNT(*) FROM inventory_requests WHERE branch_id = ? AND status = 'pending'");
                        $stmt->execute([$branch_id]);
                        $pending_req = $stmt->fetchColumn();
                    } catch (Exception $e) {}
                    ?>
                    <h3 class="stat-value mb-0"><?php echo number_format($pending_req); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php if (!empty($low_stock_items)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-semibold text-danger"><i class="bi bi-exclamation-triangle me-2"></i> Low Stock Alerts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-danger">
                                <tr>
                                    <th>Item</th>
                                    <th>Current Qty</th>
                                    <th>Reorder Level</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td class="fw-bold text-danger"><?php echo $item['quantity']; ?></td>
                                    <td><?php echo $item['reorder_level']; ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?></td>
                                    <td><span class="badge bg-danger">Low Stock</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Stock Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT * FROM inventory WHERE branch_id = ? ORDER BY item_name");
                                    $stmt->execute([$branch_id]);
                                    $items = $stmt->fetchAll();
                                    foreach ($items as $item):
                                        $status = $item['quantity'] <= $item['reorder_level'] ? 'danger' : 'success';
                                        $status_text = $item['quantity'] <= $item['reorder_level'] ? 'Low Stock' : 'In Stock';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? 'General'); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?></td>
                                    <td><?php echo $item['reorder_level']; ?></td>
                                    <td><span class="badge bg-<?php echo $status; ?>"><?php echo $status_text; ?></span></td>
                                </tr>
                                <?php
                                    endforeach;
                                    if (empty($items)):
                                ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No inventory items found</td></tr>
                                <?php
                                    endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="6" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Request Stock</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label small">Item Name</label>
                            <input type="text" name="item_name" class="form-control" placeholder="e.g. Toilet paper" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="1" step="0.5" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Unit</label>
                            <select name="unit" class="form-select">
                                <option value="pcs">Pieces</option>
                                <option value="kg">Kilograms</option>
                                <option value="liters">Liters</option>
                                <option value="packs">Packs</option>
                                <option value="boxes">Boxes</option>
                            </select>
                        </div>
                        <button type="submit" name="request_stock" class="btn btn-primary w-100"><i class="bi bi-send me-2"></i>Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
