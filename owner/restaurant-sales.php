<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Restaurant Sales';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/owner-sidebar.php';

$db = getDB();
$period = $_GET['period'] ?? 'today';
switch ($period) {
    case 'today': $date_from = date('Y-m-d'); $date_to = date('Y-m-d'); break;
    case 'week': $date_from = date('Y-m-d', strtotime('monday this week')); $date_to = date('Y-m-d'); break;
    case 'month': $date_from = date('Y-m-01'); $date_to = date('Y-m-d'); break;
    case 'custom': $date_from = $_GET['date_from'] ?? date('Y-m-01'); $date_to = $_GET['date_to'] ?? date('Y-m-d'); break;
    default: $date_from = date('Y-m-d'); $date_to = date('Y-m-d');
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Restaurant Sales</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Food & Beverage Sales</h4>
        <div class="btn-group btn-group-sm">
            <a href="?period=today" class="btn btn-outline-primary <?php echo $period === 'today' ? 'active' : ''; ?>">Today</a>
            <a href="?period=week" class="btn btn-outline-primary <?php echo $period === 'week' ? 'active' : ''; ?>">This Week</a>
            <a href="?period=month" class="btn btn-outline-primary <?php echo $period === 'month' ? 'active' : ''; ?>">This Month</a>
            <a href="?period=custom" class="btn btn-outline-primary <?php echo $period === 'custom' ? 'active' : ''; ?>">Custom</a>
        </div>
    </div>

    <?php if ($period === 'custom'): ?>
    <form method="GET" class="row g-2 mb-4">
        <input type="hidden" name="period" value="custom">
        <div class="col-auto">
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
        </div>
        <div class="col-auto">
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </div>
    </form>
    <?php endif; ?>

    <?php
    $total_food_revenue = 0; $order_counts = []; $total_orders = 0;
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(payable_amount), 0) FROM food_orders WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'");
        $stmt->execute([$date_from, $date_to]);
        $total_food_revenue = (float) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM food_orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
        $stmt->execute([$date_from, $date_to]);
        while ($row = $stmt->fetch()) { $order_counts[$row['status']] = (int) $row['cnt']; $total_orders += (int) $row['cnt']; }
    } catch (Exception $e) {}

    $pending_orders = $order_counts['pending'] ?? 0;
    $preparing_orders = $order_counts['preparing'] ?? 0;
    $completed_orders = $order_counts['completed'] ?? 0;
    $cancelled_orders = $order_counts['cancelled'] ?? 0;
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Total Food Revenue</p>
                    <h3 class="stat-value mb-0"><?php echo formatMoney($total_food_revenue); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Total Orders</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($total_orders); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Pending / Preparing</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($pending_orders + $preparing_orders); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Completed</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($completed_orders); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Sales Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="foodSalesChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Orders by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="orderStatusChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Popular Menu Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Category</th><th>Quantity Sold</th><th>Revenue</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("
                                        SELECT fi.name, fc.name as category_name, SUM(foi.quantity) as qty, SUM(foi.total_price) as rev
                                        FROM food_order_items foi
                                        JOIN food_items fi ON foi.food_item_id = fi.id
                                        JOIN food_categories fc ON fi.category_id = fc.id
                                        JOIN food_orders fo ON foi.order_id = fo.id
                                        WHERE DATE(fo.created_at) BETWEEN ? AND ? AND fo.status != 'cancelled'
                                        GROUP BY fi.id, fi.name, fc.name
                                        ORDER BY qty DESC LIMIT 10
                                    ");
                                    $stmt->execute([$date_from, $date_to]);
                                    $items = $stmt->fetchAll();
                                    foreach ($items as $item):
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo (int) $item['qty']; ?></span></td>
                                    <td><?php echo formatMoney($item['rev']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($items)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No data for this period.</td></tr>
                                <?php } ?>
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
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Sales by Order Type</h5>
                </div>
                <div class="card-body">
                    <canvas id="orderTypeChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$sales_labels = []; $sales_data = [];
try {
    $stmt = $db->prepare("
        SELECT DATE(created_at) as day, COALESCE(SUM(payable_amount), 0) as total
        FROM food_orders WHERE status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at) ORDER BY day
    ");
    $stmt->execute([$date_from, $date_to]);
    while ($row = $stmt->fetch()) {
        $sales_labels[] = date('M j', strtotime($row['day']));
        $sales_data[] = (float) $row['total'];
    }
} catch (Exception $e) {}

$type_labels = []; $type_data = [];
try {
    $stmt = $db->prepare("
        SELECT order_type, COALESCE(SUM(payable_amount), 0) as total
        FROM food_orders WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'
        GROUP BY order_type
    ");
    $stmt->execute([$date_from, $date_to]);
    while ($row = $stmt->fetch()) {
        $type_labels[] = $row['order_type'];
        $type_data[] = (float) $row['total'];
    }
} catch (Exception $e) {}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('foodSalesChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($sales_labels); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($sales_data); ?>,
                borderColor: '#198754',
                backgroundColor: 'rgba(25,135,84,0.1)',
                fill: true, tension: 0.4, pointRadius: 3,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } } }
        }
    });

    new Chart(document.getElementById('orderStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending (<?php echo $pending_orders; ?>)', 'Preparing (<?php echo $preparing_orders; ?>)', 'Completed (<?php echo $completed_orders; ?>)', 'Cancelled (<?php echo $cancelled_orders; ?>)'],
            datasets: [{
                data: [<?php echo "$pending_orders, $preparing_orders, $completed_orders, $cancelled_orders"; ?>],
                backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#dc3545'],
                borderWidth: 2,
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '60%' }
    });

    new Chart(document.getElementById('orderTypeChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($type_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($type_data); ?>,
                backgroundColor: ['#0d6efd', '#198754', '#ffc107'],
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
