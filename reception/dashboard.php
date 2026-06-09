<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Reception Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_name = htmlspecialchars($_SESSION['full_name'] ?? 'Receptionist');
$today = date('Y-m-d');

$stats = [];
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ? AND status = 'available'");
    $stmt->execute([$branch_id]);
    $stats['available_rooms'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ? AND status = 'occupied'");
    $stmt->execute([$branch_id]);
    $stats['occupied_rooms'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ? AND status = 'reserved'");
    $stmt->execute([$branch_id]);
    $stats['reserved_rooms'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ?");
    $stmt->execute([$branch_id]);
    $stats['total_rooms'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_status = 'pending'");
    $stmt->execute([$branch_id]);
    $stats['pending_bookings'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_status = 'confirmed' AND check_in_date = ?");
    $stmt->execute([$branch_id, $today]);
    $stats['checkins_today'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_status = 'checked_in' AND check_out_date = ?");
    $stmt->execute([$branch_id, $today]);
    $stats['checkouts_today'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = ?");
    $stmt->execute([$branch_id, $today]);
    $stats['payments_today'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = DATE(?) - INTERVAL '1 day'");
    $stmt->execute([$branch_id, $today]);
    $stats['yesterday_revenue'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM chat_sessions WHERE branch_id = ? AND status = 'active'");
    $stmt->execute([$branch_id]);
    $stats['active_chats'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ? AND status IN ('cleaning', 'maintenance', 'out_of_service')");
    $stmt->execute([$branch_id]);
    $stats['unavailable_rooms'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log('Reception dashboard error: ' . $e->getMessage());
}

$occupancy_rate = $stats['total_rooms'] > 0 ? round(($stats['occupied_rooms'] / $stats['total_rooms']) * 100) : 0;
$revenue_change = ($stats['yesterday_revenue'] ?? 0) > 0 ? round((($stats['payments_today'] - $stats['yesterday_revenue']) / $stats['yesterday_revenue']) * 100) : 0;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold">Welcome back, <?php echo $user_name; ?></h4>
            <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?php echo date('l, F j, Y'); ?> &middot; <i class="bi bi-clock me-1"></i><span id="liveClock"><?php echo date('h:i A'); ?></span></small>
        </div>
        <div class="d-flex gap-2">
            <a href="check-in.php" class="btn btn-success"><i class="bi bi-box-arrow-in-right me-1"></i> Check-in</a>
            <a href="check-out.php" class="btn btn-warning"><i class="bi bi-box-arrow-right me-1"></i> Check-out</a>
            <a href="walk-in-booking.php" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Walk-in</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Today's Revenue</p>
                            <h3 class="stat-value mb-0 text-primary"><?php echo formatMoney($stats['payments_today'] ?? 0); ?></h3>
                            <small class="<?php echo $revenue_change >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <i class="bi bi-arrow-<?php echo $revenue_change >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($revenue_change); ?>% vs yesterday
                            </small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-2"><i class="bi bi-cash-stack text-primary"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Check-ins Today</p>
                            <h3 class="stat-value mb-0 text-success"><?php echo $stats['checkins_today'] ?? 0; ?></h3>
                            <small class="text-muted">Expected arrivals</small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-2"><i class="bi bi-box-arrow-in-right text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Check-outs Today</p>
                            <h3 class="stat-value mb-0 text-warning"><?php echo $stats['checkouts_today'] ?? 0; ?></h3>
                            <small class="text-muted">Expected departures</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-2"><i class="bi bi-box-arrow-right text-warning"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Pending Bookings</p>
                            <h3 class="stat-value mb-0 text-info"><?php echo $stats['pending_bookings'] ?? 0; ?></h3>
                            <small class="text-muted">Awaiting confirmation</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-2"><i class="bi bi-clock-history text-info"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0 fw-semibold">Room Occupancy</h6>
                        <span class="badge bg-<?php echo $occupancy_rate > 80 ? 'danger' : ($occupancy_rate > 50 ? 'warning' : 'success'); ?> rounded-pill"><?php echo $occupancy_rate; ?>%</span>
                    </div>
                    <div class="progress mb-3" style="height: 12px; border-radius: 6px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $stats['total_rooms'] > 0 ? round(($stats['available_rooms'] / $stats['total_rooms']) * 100) : 0; ?>%" title="Available"></div>
                        <div class="progress-bar bg-warning" style="width: <?php echo $stats['total_rooms'] > 0 ? round(($stats['reserved_rooms'] / $stats['total_rooms']) * 100) : 0; ?>%" title="Reserved"></div>
                        <div class="progress-bar bg-danger" style="width: <?php echo $stats['total_rooms'] > 0 ? round(($stats['occupied_rooms'] / $stats['total_rooms']) * 100) : 0; ?>%" title="Occupied"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-3" style="font-size:0.8rem;">
                        <span><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#198754;"></span> Available (<?php echo $stats['available_rooms'] ?? 0; ?>)</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#ffc107;"></span> Reserved (<?php echo $stats['reserved_rooms'] ?? 0; ?>)</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#dc3545;"></span> Occupied (<?php echo $stats['occupied_rooms'] ?? 0; ?>)</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#6c757d;"></span> Other (<?php echo $stats['unavailable_rooms'] ?? 0; ?>)</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title mb-3 fw-semibold">Today's Arrivals</h6>
                    <?php
                    try {
                        $stmt = $db->prepare("
                            SELECT b.*, c.first_name, c.last_name, rm.room_number, rt.name as room_type
                            FROM bookings b
                            LEFT JOIN customers c ON b.customer_id = c.id
                            LEFT JOIN rooms rm ON b.room_id = rm.id
                            LEFT JOIN room_types rt ON rm.room_type_id = rt.id
                            WHERE b.branch_id = ? AND b.booking_status = 'confirmed' AND b.check_in_date = ?
                            ORDER BY b.created_at ASC LIMIT 5
                        ");
                        $stmt->execute([$branch_id, $today]);
                        $arrivals = $stmt->fetchAll();
                        if ($arrivals):
                            foreach ($arrivals as $a):
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background:rgba(25,135,84,0.05);border-left:3px solid #198754;">
                        <div class="flex-grow-1">
                            <div class="fw-medium" style="font-size:0.85rem;"><?php echo htmlspecialchars(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? 'Guest')); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($a['room_type'] ?? 'Room'); ?> &middot; <?php echo htmlspecialchars($a['room_number'] ?? '—'); ?></small>
                        </div>
                        <a href="check-in.php?booking=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-success py-0">Check In</a>
                    </div>
                    <?php
                            endforeach;
                        else:
                    ?>
                    <div class="text-center py-3 text-muted" style="font-size:0.85rem;"><i class="bi bi-check-circle me-1"></i> No arrivals pending</div>
                    <?php endif; } catch (Exception $e) {} ?>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title mb-3 fw-semibold">Today's Departures</h6>
                    <?php
                    try {
                        $stmt = $db->prepare("
                            SELECT b.*, c.first_name, c.last_name, rm.room_number, rt.name as room_type
                            FROM bookings b
                            LEFT JOIN customers c ON b.customer_id = c.id
                            LEFT JOIN rooms rm ON b.room_id = rm.id
                            LEFT JOIN room_types rt ON rm.room_type_id = rt.id
                            WHERE b.branch_id = ? AND b.booking_status = 'checked_in' AND b.check_out_date = ?
                            ORDER BY b.created_at ASC LIMIT 5
                        ");
                        $stmt->execute([$branch_id, $today]);
                        $departures = $stmt->fetchAll();
                        if ($departures):
                            foreach ($departures as $d):
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background:rgba(255,193,7,0.05);border-left:3px solid #ffc107;">
                        <div class="flex-grow-1">
                            <div class="fw-medium" style="font-size:0.85rem;"><?php echo htmlspecialchars(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? 'Guest')); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($d['room_type'] ?? 'Room'); ?> &middot; <?php echo htmlspecialchars($d['room_number'] ?? '—'); ?></small>
                        </div>
                        <a href="check-out.php?booking=<?php echo $d['id']; ?>" class="btn btn-sm btn-outline-warning py-0">Check Out</a>
                    </div>
                    <?php
                            endforeach;
                        else:
                    ?>
                    <div class="text-center py-3 text-muted" style="font-size:0.85rem;"><i class="bi bi-check-circle me-1"></i> No departures pending</div>
                    <?php endif; } catch (Exception $e) {} ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Weekly Revenue</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Room Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="roomDonut" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Recent Bookings</h5>
                    <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT b.*, c.first_name, c.last_name, rm.room_number FROM bookings b LEFT JOIN customers c ON b.customer_id = c.id LEFT JOIN rooms rm ON b.room_id = rm.id WHERE b.branch_id = ? ORDER BY b.created_at DESC LIMIT 8");
                                    $stmt->execute([$branch_id]);
                                    while ($b = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? 'Guest')); ?></strong></td>
                                    <td><?php echo htmlspecialchars($b['room_number'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo formatDate($b['check_in_date']); ?></small></td>
                                    <td><small><?php echo formatDate($b['check_out_date']); ?></small></td>
                                    <td><?php echo getBookingStatusBadge($b['booking_status']); ?></td>
                                    <td><?php echo formatMoney($b['total_amount'] ?? 0); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Recent Guests</h5>
                    <a href="customers.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Name</th><th>Phone</th><th>Visits</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM bookings WHERE customer_id = c.id) as total_bookings FROM customers c WHERE c.branch_id = ? OR c.id IN (SELECT customer_id FROM bookings WHERE branch_id = ?) ORDER BY (SELECT MAX(created_at) FROM bookings WHERE customer_id = c.id) DESC LIMIT 8");
                                    $stmt->execute([$branch_id, $branch_id]);
                                    while ($c = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($c['phone'] ?? '-'); ?></small></td>
                                    <td><span class="badge bg-secondary"><?php echo $c['total_bookings'] ?? 0; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$rev_labels = []; $rev_data = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $rev_labels[] = date('D M j', strtotime($day));
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = ?");
        $stmt->execute([$branch_id, $day]);
        $rev_data[] = (float) $stmt->fetchColumn();
    } catch (Exception $e) { $rev_data[] = 0; }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateClock() {
        var now = new Date();
        var h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12; h = h ? h : 12;
        document.getElementById('liveClock').textContent = h + ':' + (m<10?'0':'') + m + ':' + (s<10?'0':'') + s + ' ' + ampm;
    }
    setInterval(updateClock, 1000);

    new Chart(document.getElementById('revenueChart'), {
        type: 'bar', data: {
            labels: <?php echo json_encode($rev_labels); ?>,
            datasets: [{ label: 'Revenue', data: <?php echo json_encode($rev_data); ?>, backgroundColor: 'rgba(15,26,46,0.85)', borderColor: '#c9a84c', borderWidth: 1, borderRadius: 6, barPercentage: 0.6 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('roomDonut'), {
        type: 'doughnut', data: {
            labels: ['Available','Reserved','Occupied','Other'],
            datasets: [{ data: [<?php echo $stats['available_rooms'] ?? 0; ?>, <?php echo $stats['reserved_rooms'] ?? 0; ?>, <?php echo $stats['occupied_rooms'] ?? 0; ?>, <?php echo $stats['unavailable_rooms'] ?? 0; ?>], backgroundColor: ['#198754','#ffc107','#dc3545','#6c757d'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 11 } } } }, cutout: '65%' }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
