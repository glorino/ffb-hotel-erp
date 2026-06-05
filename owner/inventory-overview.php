<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Inventory Overview';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/owner-sidebar.php';

$db = getDB();
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Inventory Overview</li>
        </ol>
    </nav>

    <?php
    $total_items = 0; $total_stock_value = 0; $low_stock_items = []; $total_branches_inv = 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM inventory_items WHERE status = 'active'");
        $total_items = (int) $stmt->fetchColumn();

        $stmt = $db->query("SELECT COALESCE(SUM(quantity * price_per_unit), 0) FROM inventory_items WHERE status = 'active'");
        $total_stock_value = (float) $stmt->fetchColumn();

        $stmt = $db->query("
            SELECT i.*, b.name as branch_name 
            FROM inventory_items i 
            JOIN branches b ON i.branch_id = b.id 
            WHERE i.quantity <= i.reorder_level AND i.status = 'active'
            ORDER BY (i.reorder_level - i.quantity) DESC
        ");
        $low_stock_items = $stmt->fetchAll();

        $stmt = $db->query("SELECT COUNT(DISTINCT branch_id) FROM inventory_items WHERE status = 'active'");
        $total_branches_inv = (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Inventory error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Total Items</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($total_items); ?></h3>
                    <small class="text-muted">Active inventory items</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Total Stock Value</p>
                    <h3 class="stat-value mb-0"><?php echo formatMoney($total_stock_value); ?></h3>
                    <small class="text-muted">Current valuation</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Low Stock Alerts</p>
                    <h3 class="stat-value mb-0 <?php echo count($low_stock_items) > 0 ? 'text-danger' : ''; ?>"><?php echo count($low_stock_items); ?></h3>
                    <small class="text-warning">Items below reorder level</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Branches with Inventory</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($total_branches_inv); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($low_stock_items)): ?>
    <div class="card border-0 shadow-sm mb-4 border-danger">
        <div class="card-header bg-danger text-white py-3">
            <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Item</th><th>Branch</th><th>Category</th><th>Current Qty</th><th>Reorder Level</th><th>Unit</th><th>Action Needed</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_items as $item): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-danger"><?php echo (float) $item['quantity']; ?></span></td>
                            <td><?php echo (float) $item['reorder_level']; ?></td>
                            <td><?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?></td>
                            <td><a href="../inventory/?action=restock&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">Restock</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Stock by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Stock Movement Summary (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="movementChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">All Inventory Items</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Item Name</th><th>Branch</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Price/Unit</th><th>Stock Value</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->query("
                                SELECT i.*, b.name as branch_name
                                FROM inventory_items i
                                JOIN branches b ON i.branch_id = b.id
                                WHERE i.status = 'active'
                                ORDER BY i.name LIMIT 100
                            ");
                            $items = $stmt->fetchAll();
                            foreach ($items as $item):
                                $is_low = $item['quantity'] <= $item['reorder_level'];
                        ?>
                        <tr class="<?php echo $is_low ? 'table-danger' : ''; ?>">
                            <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                            <td><?php echo (float) $item['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?></td>
                            <td><?php echo formatMoney($item['price_per_unit']); ?></td>
                            <td><?php echo formatMoney($item['quantity'] * $item['price_per_unit']); ?></td>
                            <td>
                                <?php if ($is_low): ?>
                                    <span class="badge bg-danger">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-success">In Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr><td colspan="8" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$cat_labels = []; $cat_data = [];
try {
    $stmt = $db->query("SELECT category, SUM(quantity * price_per_unit) as value FROM inventory_items WHERE status = 'active' GROUP BY category ORDER BY value DESC");
    while ($row = $stmt->fetch()) {
        $cat_labels[] = $row['category'] ?: 'Uncategorized';
        $cat_data[] = (float) $row['value'];
    }
} catch (Exception $e) {}

$movement_labels = ['Stock In', 'Stock Out', 'Transfers'];
$movement_data = [0, 0, 0];
try {
    $stmt = $db->query("SELECT type, COALESCE(SUM(quantity), 0) as total FROM stock_movements WHERE created_at >= CURRENT_DATE - INTERVAL '30 days' GROUP BY type");
    while ($row = $stmt->fetch()) {
        if ($row['type'] === 'in') $movement_data[0] = (float) $row['total'];
        if ($row['type'] === 'out') $movement_data[1] = (float) $row['total'];
        if ($row['type'] === 'transfer') $movement_data[2] = (float) $row['total'];
    }
} catch (Exception $e) {}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{ data: <?php echo json_encode($cat_data); ?>, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#20c997'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '60%' }
    });

    new Chart(document.getElementById('movementChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($movement_labels); ?>,
            datasets: [{
                label: 'Quantity',
                data: <?php echo json_encode($movement_data); ?>,
                backgroundColor: ['#198754', '#dc3545', '#0dcaf0'],
                borderRadius: 4,
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
