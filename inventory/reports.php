<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager', 'owner']);

$page_title = 'Inventory Reports';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND branch_id = " . (int)$branch_id : "";

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$total_items = 0;
$total_value = 0;
try {
    $stmt = $db->query("SELECT COUNT(*) FROM inventory_items WHERE status = 'active' $branch_filter");
    $total_items = (int)$stmt->fetchColumn();
    $stmt = $db->query("SELECT COALESCE(SUM(quantity * price_per_unit), 0) FROM inventory_items WHERE status = 'active' $branch_filter");
    $total_value = (float)$stmt->fetchColumn();
} catch (Exception $e) {
}

$low_stock_count = 0;
try {
    $stmt = $db->query("SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_level AND status = 'active' $branch_filter");
    $low_stock_count = (int)$stmt->fetchColumn();
} catch (Exception $e) {
}

$categories = [];
try {
    $stmt = $db->query("SELECT category, COUNT(*) as cnt, COALESCE(SUM(quantity * price_per_unit), 0) as val FROM inventory_items WHERE status = 'active' AND category IS NOT NULL $branch_filter GROUP BY category ORDER BY cnt DESC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
}

$movements = [];
try {
    $sql = "SELECT DATE(created_at) as d, SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as qty_in, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as qty_out FROM stock_movements WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?" . ($branch_id ? " AND branch_id = " . (int)$branch_id : "") . " GROUP BY DATE(created_at) ORDER BY d";
    $stmt = $db->prepare($sql);
    $stmt->execute([$from_date, $to_date]);
    $movements = $stmt->fetchAll();
} catch (Exception $e) {
}

$low_items = [];
try {
    $stmt = $db->query("SELECT name, quantity, reorder_level, unit FROM inventory_items WHERE quantity <= reorder_level AND status = 'active' $branch_filter ORDER BY (quantity / GREATEST(reorder_level, 1)) ASC LIMIT 20");
    $low_items = $stmt->fetchAll();
} catch (Exception $e) {
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Inventory Reports</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-text me-2"></i>Inventory Reports</h4>
        <div>
            <a href="reports.php?export=csv&type=valuation&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> Export CSV</a>
        </div>
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
                    <a href="reports.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Total Items</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($total_items); ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-3">
                            <i class="bi bi-cubes text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Stock Valuation</p>
                            <h3 class="stat-value mb-0"><?php echo formatMoney($total_value); ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-3">
                            <i class="bi bi-currency-dollar text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Low Stock Items</p>
                            <h3 class="stat-value mb-0 text-danger"><?php echo number_format($low_stock_count); ?></h3>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-3">
                            <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Stock Movement (<?php echo $from_date; ?> to <?php echo $to_date; ?>)</h5>
                </div>
                <div class="card-body">
                    <canvas id="movementChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Category Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Category-Wise Breakdown</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Category</th><th>Items</th><th>Total Value</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No data.</td></tr>
                                <?php else: ?>
                                <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($c['category']); ?></td>
                                    <td><?php echo number_format($c['cnt']); ?></td>
                                    <td><?php echo formatMoney($c['val']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Low Stock Report</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Item</th><th>Quantity</th><th>Reorder Level</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($low_items)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">All stock levels healthy.</td></tr>
                                <?php else: ?>
                                <?php foreach ($low_items as $li): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($li['name']); ?></td>
                                    <td class="text-danger fw-bold"><?php echo number_format($li['quantity']); ?></td>
                                    <td><?php echo number_format($li['reorder_level']); ?></td>
                                    <td><span class="badge bg-danger">Low Stock</span></td>
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

<?php
$mov_labels = []; $mov_in = []; $mov_out = [];
foreach ($movements as $m) {
    $mov_labels[] = $m['d'];
    $mov_in[] = (float)$m['qty_in'];
    $mov_out[] = (float)$m['qty_out'];
}

$cat_labels = []; $cat_data = [];
foreach ($categories as $c) {
    $cat_labels[] = $c['category'];
    $cat_data[] = (int)$c['cnt'];
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($mov_labels)): ?>
    new Chart(document.getElementById('movementChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($mov_labels); ?>,
            datasets: [
                { label: 'Stock In', data: <?php echo json_encode($mov_in); ?>, backgroundColor: 'rgba(25,135,84,0.7)', borderColor: '#198754', borderWidth: 1 },
                { label: 'Stock Out', data: <?php echo json_encode($mov_out); ?>, backgroundColor: 'rgba(255,193,7,0.7)', borderColor: '#ffc107', borderWidth: 1 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });
    <?php endif; ?>

    <?php if (!empty($cat_labels)): ?>
    const catColors = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#20c997','#d63384','#6610f2'];
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{ data: <?php echo json_encode($cat_data); ?>, backgroundColor: catColors.slice(0, <?php echo count($cat_labels); ?>), borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '65%' }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
