<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

$page_title = 'Low Stock Alerts';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND i.branch_id = " . (int)$branch_id : "";

$items = [];
try {
    $stmt = $db->prepare("
        SELECT i.*, s.name as supplier_name,
        (SELECT MAX(sm.created_at) FROM stock_movements sm WHERE sm.item_id = i.id AND sm.type = 'in') as last_restocked
        FROM inventory_items i
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        WHERE i.quantity <= i.reorder_level AND i.status = 'active' $branch_filter
        ORDER BY (i.quantity / GREATEST(i.reorder_level, 1)) ASC
    ");
    $stmt->execute();
    $items = $stmt->fetchAll();
} catch (Exception $e) {
    $items = [];
}

$critical_count = 0;
$warning_count = 0;
foreach ($items as $item) {
    $ratio = $item['reorder_level'] > 0 ? $item['quantity'] / $item['reorder_level'] : 1;
    if ($ratio <= 0.5) $critical_count++;
    else $warning_count++;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Low Stock Alerts</li>
        </ol>
    </nav>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Low Stock Items</p>
                            <h3 class="stat-value mb-0"><?php echo count($items); ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3">
                            <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
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
                            <p class="stat-label text-muted mb-1">Critical</p>
                            <h3 class="stat-value mb-0 text-danger"><?php echo $critical_count; ?></h3>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-3">
                            <i class="bi bi-exclamation-octagon text-danger fs-4"></i>
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
                            <p class="stat-label text-muted mb-1">Warning</p>
                            <h3 class="stat-value mb-0 text-warning"><?php echo $warning_count; ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3">
                            <i class="bi bi-exclamation-circle text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($items)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
            <h5 class="mt-3 text-success">All Stock Levels Are Healthy</h5>
            <p class="text-muted mb-0">No items are below their reorder level.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($items as $item):
            $ratio = $item['reorder_level'] > 0 ? $item['quantity'] / $item['reorder_level'] : 1;
            $percent = min(100, max(0, round(($item['quantity'] / GREATEST($item['reorder_level'], 1)) * 100)));
            $is_critical = $ratio <= 0.5;
            $bar_color = $is_critical ? 'bg-danger' : 'bg-warning';
            $bg_class = $is_critical ? 'bg-danger-subtle border-danger' : 'bg-warning-subtle border-warning';
        ?>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 <?php echo $bg_class; ?>" style="border-left: 4px solid <?php echo $is_critical ? '#dc3545' : '#ffc107'; ?>;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <span class="badge bg-<?php echo $is_critical ? 'danger' : 'warning'; ?>"><?php echo $is_critical ? 'Critical' : 'Warning'; ?></span>
                        </div>
                        <a href="stock-in.php?item_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-navy" title="Reorder"><i class="bi bi-cart-plus"></i> Reorder</a>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Current Quantity</small>
                        <span class="fw-bold"><?php echo number_format($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Reorder Level</small>
                        <span><?php echo number_format($item['reorder_level']); ?> <?php echo htmlspecialchars($item['unit']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Supplier</small>
                        <span><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <small class="text-muted">Last Restocked</small>
                        <span><?php echo $item['last_restocked'] ? formatDate($item['last_restocked']) : 'Never'; ?></span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar <?php echo $bar_color; ?>" role="progressbar" style="width: <?php echo $percent; ?>%;" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $percent; ?>%</div>
                    </div>
                    <small class="text-muted mt-1 d-block">Stock level at <?php echo $percent; ?>% of reorder level</small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
