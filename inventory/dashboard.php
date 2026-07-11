<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager']);

$page_title = 'Inventory Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = (int)($_SESSION['branch_id'] ?? 0);
?>

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Inventory Dashboard</li>
        </ol>
    </nav>

    <?php
    $stats = [];
    try {
        if ($branch_id) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM inventory_items WHERE status='active' AND branch_id=?");
            $stmt->execute([$branch_id]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) FROM inventory_items WHERE status='active'");
        }
        $stats['total_items'] = $stmt->fetchColumn();

        if ($branch_id) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_level AND status='active' AND branch_id=?");
            $stmt->execute([$branch_id]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_level AND status='active'");
        }
        $stats['low_stock'] = $stmt->fetchColumn();

        if ($branch_id) {
            $stmt = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='in' AND DATE(created_at)=CURRENT_DATE AND branch_id=?");
            $stmt->execute([$branch_id]);
        } else {
            $stmt = $db->query("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='in' AND DATE(created_at)=CURRENT_DATE");
        }
        $stats['stock_in_today'] = $stmt->fetchColumn();

        if ($branch_id) {
            $stmt = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='out' AND DATE(created_at)=CURRENT_DATE AND branch_id=?");
            $stmt->execute([$branch_id]);
        } else {
            $stmt = $db->query("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='out' AND DATE(created_at)=CURRENT_DATE");
        }
        $stats['stock_out_today'] = $stmt->fetchColumn();

        if ($branch_id) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE status='active' AND branch_id=?");
            $stmt->execute([$branch_id]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) FROM suppliers WHERE status='active'");
        }
        $stats['suppliers'] = $stmt->fetchColumn();

        if ($branch_id) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM kitchen_requests WHERE status='pending' AND branch_id=?");
            $stmt->execute([$branch_id]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) FROM kitchen_requests WHERE status='pending'");
        }
        $stats['pending_requests'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Inventory dashboard error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Total Items</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['total_items'] ?? 0); ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-3">
                            <i class="bi bi-cubes text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Low Stock</p>
                            <h3 class="stat-value mb-0 text-danger"><?php echo number_format($stats['low_stock'] ?? 0); ?></h3>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-3">
                            <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Stock In Today</p>
                            <h3 class="stat-value mb-0 text-success"><?php echo number_format($stats['stock_in_today'] ?? 0); ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-3">
                            <i class="bi bi-arrow-down-circle text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Stock Out Today</p>
                            <h3 class="stat-value mb-0 text-warning"><?php echo number_format($stats['stock_out_today'] ?? 0); ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3">
                            <i class="bi bi-arrow-up-circle text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Suppliers</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['suppliers'] ?? 0); ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3">
                            <i class="bi bi-truck text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Kitchen Requests</p>
                            <h3 class="stat-value mb-0 text-info"><?php echo number_format($stats['pending_requests'] ?? 0); ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3">
                            <i class="bi bi-utensils text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    <h5 class="card-title mb-0 fw-semibold">Stock Movement (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="movementChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Low Stock Alerts</h5>
                    <a href="low-stock-alerts.php" class="btn btn-sm btn-outline-danger">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Qty</th><th>Reorder Level</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT i.*, s.name as supplier_name FROM inventory_items i LEFT JOIN suppliers s ON i.supplier_id=s.id WHERE i.quantity <= i.reorder_level AND i.status='active'" . ($branch_id ? " AND i.branch_id=?" : "") . " ORDER BY (i.quantity / i.reorder_level) ASC LIMIT 10");
                                    $params = $branch_id ? [$branch_id] : [];
                                    $stmt->execute($params);
                                    $low_items = $stmt->fetchAll();
                                    foreach ($low_items as $item):
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo number_format($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><?php echo number_format($item['reorder_level']); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><span class="badge bg-danger">Low Stock</span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($low_items)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No low stock items.</td></tr>
                                <?php endif; ?>
                                <?php } catch (Exception $e) { ?>
                                <tr><td colspan="4" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Recent Stock Movements</h5>
                    <a href="stock-in.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Type</th><th>Qty</th><th>Date</th><th>By</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT sm.*, i.name as item_name, u.full_name as user_name FROM stock_movements sm JOIN inventory_items i ON sm.item_id=i.id LEFT JOIN users u ON sm.created_by=u.id" . ($branch_id ? " WHERE sm.branch_id=?" : "") . " ORDER BY sm.created_at DESC LIMIT 15");
                                    $params = $branch_id ? [$branch_id] : [];
                                    $stmt->execute($params);
                                    $movements = $stmt->fetchAll();
                                    foreach ($movements as $m):
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($m['item_name']); ?></td>
                                    <td>
                                        <?php if ($m['type'] === 'in'): ?>
                                        <span class="badge bg-success">IN</span>
                                        <?php elseif ($m['type'] === 'out'): ?>
                                        <span class="badge bg-warning text-dark">OUT</span>
                                        <?php else: ?>
                                        <span class="badge bg-info">TRANSFER</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($m['quantity']); ?></td>
                                    <td><small class="text-muted"><?php echo formatDateTime($m['created_at']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($m['user_name'] ?? 'N/A'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($movements)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No movements yet.</td></tr>
                                <?php endif; ?>
                                <?php } catch (Exception $e) { ?>
                                <tr><td colspan="5" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$cat_labels = []; $cat_data = [];
try {
    if ($branch_id) {
        $stmt = $db->prepare("SELECT category, COUNT(*) as cnt FROM inventory_items WHERE category IS NOT NULL AND status='active' AND branch_id=? GROUP BY category ORDER BY cnt DESC");
        $stmt->execute([$branch_id]);
    } else {
        $stmt = $db->query("SELECT category, COUNT(*) as cnt FROM inventory_items WHERE category IS NOT NULL AND status='active' GROUP BY category ORDER BY cnt DESC");
    }
    while ($r = $stmt->fetch()) { $cat_labels[] = $r['category']; $cat_data[] = (int)$r['cnt']; }
} catch (Exception $e) {}

$mov_dates = []; $mov_in = []; $mov_out = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $mov_dates[] = date('D', strtotime($d));
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN type='in' THEN quantity ELSE 0 END),0) as qty_in, COALESCE(SUM(CASE WHEN type='out' THEN quantity ELSE 0 END),0) as qty_out FROM stock_movements WHERE DATE(created_at)=?" . ($branch_id ? " AND branch_id=?" : ""));
        $params = $branch_id ? [$d, $branch_id] : [$d];
        $stmt->execute($params);
        $row = $stmt->fetch();
        $mov_in[] = (float)$row['qty_in'];
        $mov_out[] = (float)$row['qty_out'];
    } catch (Exception $e) { $mov_in[] = 0; $mov_out[] = 0; }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const catColors = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#20c997','#d63384','#6610f2'];
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{ data: <?php echo json_encode($cat_data); ?>, backgroundColor: catColors.slice(0, <?php echo count($cat_labels); ?>), borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '65%' }
    });

    new Chart(document.getElementById('movementChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($mov_dates); ?>,
            datasets: [
                { label: 'Stock In', data: <?php echo json_encode($mov_in); ?>, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', fill: true, tension: 0.4 },
                { label: 'Stock Out', data: <?php echo json_encode($mov_out); ?>, borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,0.1)', fill: true, tension: 0.4 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
