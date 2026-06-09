<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Owner Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>

    <?php
    $db = getDB();
    $today = date('Y-m-d');
    $stats = [];

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'");
        $stats['total_revenue'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND DATE(created_at) = ?");
        $stmt->execute([$today]);
        $stats['today_revenue'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status IN ('confirmed', 'checked_in')");
        $stats['active_bookings'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM rooms");
        $total_rooms = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'");
        $occupied_rooms = $stmt->fetchColumn();
        $stats['occupancy_rate'] = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100) : 0;

        $stmt = $db->query("SELECT COUNT(*) FROM branches WHERE status = 'active'");
        $stats['total_branches'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM customers");
        $stats['total_customers'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
        $stats['pending_payments'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_level AND status = 'active'");
        $stats['low_stock'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Dashboard stats error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Total Revenue</p>
                            <h3 class="stat-value mb-0"><?php echo formatMoney($stats['total_revenue'] ?? 0); ?></h3>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> Lifetime earnings</small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-3">
                            <i class="bi bi-currency-dollar text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Today's Revenue</p>
                            <h3 class="stat-value mb-0"><?php echo formatMoney($stats['today_revenue'] ?? 0); ?></h3>
                            <small class="text-info"><i class="bi bi-calendar"></i> <?php echo date('M j, Y'); ?></small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3">
                            <i class="bi bi-graph-up text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Active Bookings</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['active_bookings'] ?? 0); ?></h3>
                            <small class="text-warning"><i class="bi bi-person"></i> Confirmed & checked-in</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3">
                            <i class="bi bi-calendar-check text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Occupancy Rate</p>
                            <h3 class="stat-value mb-0"><?php echo $stats['occupancy_rate']; ?>%</h3>
                            <small class="text-secondary"><i class="bi bi-building"></i> Current occupancy</small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-3">
                            <i class="bi bi-door-open text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Total Branches</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['total_branches'] ?? 0); ?></h3>
                            <small class="text-primary"><i class="bi bi-geo-alt"></i> Active locations</small>
                        </div>
                        <div class="stat-icon bg-secondary-subtle rounded-3 p-3">
                            <i class="bi bi-building text-secondary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Total Customers</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['total_customers'] ?? 0); ?></h3>
                            <small class="text-success"><i class="bi bi-people"></i> Registered guests</small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-3">
                            <i class="bi bi-people text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Pending Payments</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['pending_payments'] ?? 0); ?></h3>
                            <small class="text-danger"><i class="bi bi-exclamation-circle"></i> Awaiting payment</small>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-3">
                            <i class="bi bi-credit-card text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Low Stock Alerts</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['low_stock'] ?? 0); ?></h3>
                            <small class="text-warning"><i class="bi bi-box"></i> Items below reorder level</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3">
                            <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0 fw-semibold">Revenue Trends</h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" data-period="7">7 Days</button>
                        <button type="button" class="btn btn-outline-primary" data-period="30">30 Days</button>
                        <button type="button" class="btn btn-outline-primary" data-period="90">90 Days</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Booking Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="bookingChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0 fw-semibold">Recent Bookings</h5>
                    <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Customer</th>
                                    <th>Branch</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("
                                        SELECT b.*, c.full_name as customer_name, br.name as branch_name
                                        FROM bookings b
                                        JOIN customers c ON b.customer_id = c.id
                                        JOIN branches br ON b.branch_id = br.id
                                        ORDER BY b.created_at DESC LIMIT 10
                                    ");
                                    $recent_bookings = $stmt->fetchAll();
                                    foreach ($recent_bookings as $bk):
                                ?>
                                <tr>
                                    <td><a href="bookings.php?view=<?php echo $bk['id']; ?>" class="text-decoration-none fw-medium"><?php echo htmlspecialchars($bk['booking_reference']); ?></a></td>
                                    <td><?php echo htmlspecialchars($bk['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($bk['branch_name']); ?></td>
                                    <td><?php echo formatMoney($bk['total_amount']); ?></td>
                                    <td><?php echo getBookingStatusBadge($bk['booking_status']); ?></td>
                                    <td><small class="text-muted"><?php echo formatDate($bk['created_at']); ?></small></td>
                                </tr>
                                <?php
                                    endforeach;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="6" class="text-danger">Error loading bookings</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0 fw-semibold">Recent Payments</h5>
                    <a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("
                                        SELECT p.*, c.full_name as customer_name
                                        FROM payments p
                                        LEFT JOIN customers c ON p.customer_id = c.id
                                        ORDER BY p.created_at DESC LIMIT 10
                                    ");
                                    $recent_payments = $stmt->fetchAll();
                                    foreach ($recent_payments as $pmt):
                                ?>
                                <tr>
                                    <td><small><?php echo htmlspecialchars($pmt['payment_reference']); ?></small></td>
                                    <td><?php echo htmlspecialchars($pmt['customer_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatMoney($pmt['amount']); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($pmt['method']); ?></span></td>
                                    <td><?php echo getPaymentStatusBadge($pmt['status']); ?></td>
                                    <td><small class="text-muted"><?php echo formatDate($pmt['created_at']); ?></small></td>
                                </tr>
                                <?php
                                    endforeach;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="6" class="text-danger">Error loading payments</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$revenue_labels = [];
$revenue_data = [];
try {
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $revenue_labels[] = date('D', strtotime($day));
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND DATE(created_at) = ?");
        $stmt->execute([$day]);
        $revenue_data[] = (float) $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $revenue_labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $revenue_data = [0,0,0,0,0,0,0];
}

$booking_status_counts = ['pending'=>0,'confirmed'=>0,'checked_in'=>0,'checked_out'=>0,'cancelled'=>0];
try {
    $stmt = $db->query("SELECT booking_status, COUNT(*) as cnt FROM bookings GROUP BY booking_status");
    while ($row = $stmt->fetch()) {
        $booking_status_counts[$row['booking_status']] = (int) $row['cnt'];
    }
} catch (Exception $e) {}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($revenue_labels); ?>,
            datasets: [{
                label: 'Revenue (<?php echo CURRENCY_SYMBOL; ?>)',
                data: <?php echo json_encode($revenue_data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#0d6efd',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString(); } } } }
        }
    });

    new Chart(document.getElementById('bookingChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled'],
            datasets: [{
                data: <?php echo json_encode(array_values($booking_status_counts)); ?>,
                backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#6c757d', '#dc3545'],
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } },
            cutout: '65%',
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
