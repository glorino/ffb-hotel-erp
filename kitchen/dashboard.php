<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['kitchen_chef']);

$page_title = 'Kitchen Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
?>

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Kitchen Dashboard</li>
        </ol>
    </nav>

    <?php
    $stats = [];
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND status = 'pending'");
        $stmt->execute([$branch_id]);
        $stats['incoming'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND status = 'preparing'");
        $stmt->execute([$branch_id]);
        $stats['preparing'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND status = 'ready'");
        $stmt->execute([$branch_id]);
        $stats['ready'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND status = 'completed' AND DATE(created_at) = CURRENT_DATE");
        $stmt->execute([$branch_id]);
        $stats['completed_today'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_items WHERE branch_id = ? AND is_available = FALSE AND status = 'active'");
        $stmt->execute([$branch_id]);
        $stats['unavailable'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND DATE(created_at) = CURRENT_DATE");
        $stmt->execute([$branch_id]);
        $stats['total_today'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Kitchen dashboard stats error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Incoming</p>
                            <h3 class="stat-value mb-0 text-warning"><?php echo $stats['incoming'] ?? 0; ?></h3>
                            <small class="text-muted">Awaiting action</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-2"><i class="bi bi-arrow-down-circle text-warning"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Preparing</p>
                            <h3 class="stat-value mb-0 text-info"><?php echo $stats['preparing'] ?? 0; ?></h3>
                            <small class="text-muted">In progress</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-2"><i class="bi bi-fire text-info"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Ready</p>
                            <h3 class="stat-value mb-0 text-success"><?php echo $stats['ready'] ?? 0; ?></h3>
                            <small class="text-muted">To be served</small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-2"><i class="bi bi-check-circle text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Completed Today</p>
                            <h3 class="stat-value mb-0 text-primary"><?php echo $stats['completed_today'] ?? 0; ?></h3>
                            <small class="text-muted">Orders done</small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-2"><i class="bi bi-clipboard-check text-primary"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Unavailable</p>
                            <h3 class="stat-value mb-0 text-danger"><?php echo $stats['unavailable'] ?? 0; ?></h3>
                            <small class="text-muted">Out of stock</small>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-2"><i class="bi bi-ban text-danger"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Total Today</p>
                            <h3 class="stat-value mb-0 text-secondary"><?php echo $stats['total_today'] ?? 0; ?></h3>
                            <small class="text-muted">All orders</small>
                        </div>
                        <div class="stat-icon bg-secondary-subtle rounded-3 p-2"><i class="bi bi-receipt text-secondary"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Live Order Feed</h5>
                    <a href="incoming-orders.php" class="btn btn-sm btn-outline-warning">View All <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order Ref</th>
                                    <th>Table</th>
                                    <th>Items</th>
                                    <th>Waiter</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("
                                        SELECT fo.*, u.full_name as waiter_name 
                                        FROM food_orders fo 
                                        LEFT JOIN users u ON fo.waiter_id = u.id 
                                        WHERE fo.branch_id = ? AND fo.status IN ('pending', 'preparing') 
                                        ORDER BY fo.status = 'pending' DESC, fo.created_at ASC 
                                        LIMIT 15
                                    ");
                                    $stmt->execute([$branch_id]);
                                    while ($order = $stmt->fetch()):
                                        $item_stmt = $db->prepare("
                                            SELECT foi.quantity, fi.name 
                                            FROM food_order_items foi 
                                            JOIN food_items fi ON foi.food_item_id = fi.id 
                                            WHERE foi.order_id = ?
                                        ");
                                        $item_stmt->execute([$order['id']]);
                                        $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        $item_names = array_map(function($i) { return $i['quantity'] . 'x ' . $i['name']; }, $items);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['order_reference']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['table_number'] ?? '-'); ?></td>
                                    <td><small><?php echo htmlspecialchars(implode(', ', $item_names)); ?></small></td>
                                    <td><?php echo htmlspecialchars($order['waiter_name'] ?? 'N/A'); ?></td>
                                    <td><small class="text-muted"><?php echo timeAgo($order['created_at']); ?></small></td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'pending' => 'warning',
                                            'preparing' => 'info',
                                            'ready' => 'success',
                                            'completed' => 'secondary',
                                            'cancelled' => 'danger'
                                        ];
                                        $badge = $status_badges[$order['status']] ?? 'secondary';
                                        echo "<span class='badge bg-{$badge}'>{$order['status']}</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <a href="incoming-orders.php?accept=<?php echo $order['id']; ?>" class="btn btn-sm btn-success">Accept</a>
                                        <?php elseif ($order['status'] === 'preparing'): ?>
                                            <a href="preparing-orders.php?ready=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">Ready</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php } catch (Exception $e) {} ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Orders by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusDonutChart" height="220"></canvas>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Orders Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersLineChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

<?php
$chart_statuses = [];
$chart_counts = [];
$chart_dates = [];
$chart_date_counts = [];
try {
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM food_orders WHERE branch_id = ? AND DATE(created_at) = CURRENT_DATE GROUP BY status");
    $stmt->execute([$branch_id]);
    $status_data = $stmt->fetchAll();
    foreach ($status_data as $row) {
        $chart_statuses[] = $row['status'];
        $chart_counts[] = (int)$row['count'];
    }

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_dates[] = date('D', strtotime($date));
        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND DATE(created_at) = ?");
        $stmt->execute([$branch_id, $date]);
        $chart_date_counts[] = (int)$stmt->fetchColumn();
    }
} catch (Exception $e) {
    error_log('Chart data error: ' . $e->getMessage());
}
?>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var donutCtx = document.getElementById('statusDonutChart');
    if (donutCtx) {
        new Chart(donutCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($chart_statuses); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_counts); ?>,
                    backgroundColor: ['#f59e0b', '#0dcaf0', '#198754', '#6c757d', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
                },
                cutout: '65%'
            }
        });
    }

    var lineCtx = document.getElementById('ordersLineChart');
    if (lineCtx) {
        new Chart(lineCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($chart_date_counts); ?>,
                    borderColor: '#0dcaf0',
                    backgroundColor: 'rgba(13, 202, 240, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#0dcaf0',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
