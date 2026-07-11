<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Admin Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
?>

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>

    <?php
    $stats = [];
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'");
        $stats['total_revenue'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM bookings");
        $stats['total_bookings'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM customers");
        $stats['active_customers'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'");
        $stats['occupied_rooms'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
        $stats['available_rooms'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed' AND check_in_date = CURRENT_DATE");
        $stats['pending_checkins'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM food_orders WHERE DATE(created_at) = CURRENT_DATE AND status != 'cancelled'");
        $stats['today_orders'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug != 'customer' AND u.status = 'active'");
        $stats['active_staff'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Admin dashboard error: ' . $e->getMessage());
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
                            <small class="text-success">All time earnings</small>
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
                            <p class="stat-label text-muted mb-1">Total Bookings</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['total_bookings'] ?? 0); ?></h3>
                            <small class="text-info">All bookings</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3">
                            <i class="bi bi-calendar-check text-info fs-4"></i>
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
                            <p class="stat-label text-muted mb-1">Active Customers</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['active_customers'] ?? 0); ?></h3>
                            <small class="text-success">Registered guests</small>
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
                            <p class="stat-label text-muted mb-1">Occupied Rooms</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['occupied_rooms'] ?? 0); ?></h3>
                            <small class="text-warning">Currently occupied</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3">
                            <i class="bi bi-door-open text-warning fs-4"></i>
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
                            <p class="stat-label text-muted mb-1">Available Rooms</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['available_rooms'] ?? 0); ?></h3>
                            <small class="text-success">Ready for booking</small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-3">
                            <i class="bi bi-house text-success fs-4"></i>
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
                            <p class="stat-label text-muted mb-1">Pending Check-ins</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['pending_checkins'] ?? 0); ?></h3>
                            <small class="text-warning">Today's arrivals</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3">
                            <i class="bi bi-box-arrow-in-right text-warning fs-4"></i>
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
                            <p class="stat-label text-muted mb-1">Today's Orders</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['today_orders'] ?? 0); ?></h3>
                            <small class="text-info">Food orders today</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3">
                            <i class="bi bi-utensils text-info fs-4"></i>
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
                            <p class="stat-label text-muted mb-1">Active Staff</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['active_staff'] ?? 0); ?></h3>
                            <small class="text-secondary">System users</small>
                        </div>
                        <div class="stat-icon bg-secondary-subtle rounded-3 p-3">
                            <i class="bi bi-person-badge text-secondary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Revenue Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Booking Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="bookingPieChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        try {
                            $stmt = $db->query("
                                SELECT al.*, u.full_name as user_name
                                FROM audit_logs al
                                LEFT JOIN users u ON al.user_id = u.id
                                ORDER BY al.created_at DESC LIMIT 15
                            ");
                            $activities = $stmt->fetchAll();
                            foreach ($activities as $act):
                        ?>
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="activity-icon rounded-circle p-2 bg-light">
                                    <i class="bi bi-arrow-right-circle text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0"><strong><?php echo htmlspecialchars($act['user_name'] ?? 'System'); ?></strong> <?php echo htmlspecialchars(ucfirst($act['action'])) . ' ' . htmlspecialchars($act['entity_type']); ?></p>
                                    <small class="text-muted"><?php echo timeAgo($act['created_at']); ?> &middot; IP: <?php echo htmlspecialchars($act['ip_address'] ?? 'N/A'); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($activities)): ?>
                        <div class="text-center py-4 text-muted">No recent activity.</div>
                        <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                        <div class="text-center py-4 text-danger">Error loading activity.</div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="bookings.php" class="btn btn-outline-primary text-start"><i class="bi bi-calendar-plus me-2"></i> Manage Bookings</a>
                        <a href="rooms.php" class="btn btn-outline-success text-start"><i class="bi bi-door-open me-2"></i> Manage Rooms</a>
                        <a href="staff.php" class="btn btn-outline-info text-start"><i class="bi bi-people me-2"></i> Manage Staff</a>
                        <a href="customers.php" class="btn btn-outline-warning text-start"><i class="bi bi-person-lines-fill me-2"></i> View Customers</a>
                        <a href="orders.php" class="btn btn-outline-secondary text-start"><i class="bi bi-clipboard-list me-2"></i> Food Orders</a>
                        <a href="reports.php" class="btn btn-outline-danger text-start"><i class="bi bi-file-text me-2"></i> Generate Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$rev_labels = []; $rev_data = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $rev_labels[] = date('D', strtotime($day));
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND DATE(created_at) = ?");
        $stmt->execute([$day]);
        $rev_data[] = (float) $stmt->fetchColumn();
    } catch (Exception $e) { $rev_data[] = 0; }
}

$bk_counts = ['pending'=>0,'confirmed'=>0,'checked_in'=>0,'checked_out'=>0,'cancelled'=>0];
try {
    $stmt = $db->query("SELECT booking_status, COUNT(*) as cnt FROM bookings GROUP BY booking_status");
    while ($row = $stmt->fetch()) { $bk_counts[$row['booking_status']] = (int) $row['cnt']; }
} catch (Exception $e) {}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('revenueChart'), {
        type: 'line', data: {
            labels: <?php echo json_encode($rev_labels); ?>,
            datasets: [{ label: 'Revenue', data: <?php echo json_encode($rev_data); ?>, borderColor: '#d4af37', backgroundColor: 'rgba(212,175,55,0.08)', fill: true, tension: 0.4, pointRadius: 4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } } } }
    });

    new Chart(document.getElementById('bookingPieChart'), {
        type: 'doughnut', data: {
            labels: ['Pending','Confirmed','Checked In','Checked Out','Cancelled'],
            datasets: [{ data: <?php echo json_encode(array_values($bk_counts)); ?>, backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#6b7280', '#ef4444'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '65%' }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
