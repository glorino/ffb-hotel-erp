<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Branch Manager Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
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
        $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ? AND status = 'occupied'");
        $stmt->execute([$branch_id]);
        $stats['occupied_rooms'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ?");
        $stmt->execute([$branch_id]);
        $total_rooms = $stmt->fetchColumn();
        $stats['occupancy_pct'] = $total_rooms > 0 ? round(($stats['occupied_rooms'] / $total_rooms) * 100) : 0;

        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND DATE(created_at) = CURRENT_DATE AND booking_status != 'cancelled'");
        $stmt->execute([$branch_id]);
        $stats['today_bookings'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE b.branch_id = ? AND p.status = 'paid' AND DATE(p.created_at) = CURRENT_DATE");
        $stmt->execute([$branch_id]);
        $stats['today_revenue'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_status = 'confirmed' AND check_in_date = CURRENT_DATE");
        $stmt->execute([$branch_id]);
        $stats['pending_checkins'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND DATE(created_at) = CURRENT_DATE AND status NOT IN ('cancelled','completed')");
        $stmt->execute([$branch_id]);
        $stats['active_orders'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE u.branch_id = ? AND r.slug != 'customer' AND u.status = 'active'");
        $stmt->execute([$branch_id]);
        $stats['staff_on_duty'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Branch manager dashboard error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Occupancy</p>
                            <h3 class="stat-value mb-0"><?php echo $stats['occupancy_pct'] ?? 0; ?>%</h3>
                            <small class="text-muted small"><?php echo $stats['occupied_rooms'] ?? 0; ?>/<?php echo $total_rooms ?? 0; ?> rooms</small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-2">
                            <i class="bi bi-building text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Today's Bookings</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['today_bookings'] ?? 0); ?></h3>
                            <small class="text-info small">New reservations</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-2">
                            <i class="bi bi-calendar-check text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Today's Revenue</p>
                            <h3 class="stat-value mb-0"><?php echo formatMoney($stats['today_revenue'] ?? 0); ?></h3>
                            <small class="text-success small"><?php echo date('M j'); ?></small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-2">
                            <i class="bi bi-currency-dollar text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Pending Check-ins</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['pending_checkins'] ?? 0); ?></h3>
                            <small class="text-warning small">Arriving today</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-2">
                            <i class="bi bi-box-arrow-in-right text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Active Orders</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['active_orders'] ?? 0); ?></h3>
                            <small class="text-secondary small">Food &amp; service</small>
                        </div>
                        <div class="stat-icon bg-secondary-subtle rounded-3 p-2">
                            <i class="bi bi-utensils text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Staff on Duty</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($stats['staff_on_duty'] ?? 0); ?></h3>
                            <small class="text-info small">Active today</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-2">
                            <i class="bi bi-people text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Revenue (Last 7 Days)</h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" data-period="7">7D</button>
                        <button type="button" class="btn btn-outline-primary" data-period="30">30D</button>
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
                    <h5 class="card-title mb-0 fw-semibold">Room Occupancy</h5>
                </div>
                <div class="card-body">
                    <canvas id="occupancyChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Today's Activities</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Activity</th>
                                    <th>User</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("
                                        SELECT al.*, u.full_name
                                        FROM audit_logs al
                                        LEFT JOIN users u ON al.user_id = u.id
                                        WHERE al.branch_id = ? AND DATE(al.created_at) = CURRENT_DATE
                                        ORDER BY al.created_at DESC LIMIT 20
                                    ");
                                    $stmt->execute([$branch_id]);
                                    $activities = $stmt->fetchAll();
                                    foreach ($activities as $act):
                                ?>
                                <tr>
                                    <td><small class="text-muted"><?php echo date('h:i A', strtotime($act['created_at'])); ?></small></td>
                                    <td><?php echo htmlspecialchars(ucfirst($act['action']) . ' ' . $act['entity_type']); ?></td>
                                    <td><?php echo htmlspecialchars($act['full_name'] ?? 'System'); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($act['entity_id'] ?? ''); ?></small></td>
                                </tr>
                                <?php
                                    endforeach;
                                    if (empty($activities)):
                                ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No activities today</td></tr>
                                <?php
                                    endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="4" class="text-center py-4 text-danger">Error loading activities</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
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
        $stmt = $db->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE b.branch_id = ? AND p.status = 'paid' AND DATE(p.created_at) = ?");
        $stmt->execute([$branch_id, $day]);
        $rev_data[] = (float) $stmt->fetchColumn();
    } catch (Exception $e) { $rev_data[] = 0; }
}

$occ_labels = []; $occ_data = []; $occ_colors = [];
try {
    $stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM rooms WHERE branch_id = ? GROUP BY status");
    $stmt->execute([$branch_id]);
    while ($row = $stmt->fetch()) {
        $occ_labels[] = ucfirst($row['status']);
        $occ_data[] = (int) $row['cnt'];
    }
} catch (Exception $e) {}
$occ_colors_map = ['available'=>'#198754','reserved'=>'#0dcaf0','occupied'=>'#ffc107','cleaning'=>'#6c757d','maintenance'=>'#dc3545','out_of_service'=>'#212529'];
foreach ($occ_labels as $l) {
    $occ_colors[] = $occ_colors_map[strtolower($l)] ?? '#6c757d';
}
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

    new Chart(document.getElementById('occupancyChart'), {
        type: 'doughnut', data: {
            labels: <?php echo json_encode($occ_labels); ?>,
            datasets: [{ data: <?php echo json_encode($occ_data); ?>, backgroundColor: <?php echo json_encode($occ_colors); ?>, borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8 } } }, cutout: '65%' }
    });

    document.querySelectorAll('[data-period]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelector('[data-period].active')?.classList.remove('active');
            this.classList.add('active');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
