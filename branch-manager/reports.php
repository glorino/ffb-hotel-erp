<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Branch Reports';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'sales';
if (!validateDate($date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
if (!validateDate($date_to)) $date_to = date('Y-m-d');
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Reports</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small">Report Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                        <option value="bookings" <?php echo $report_type === 'bookings' ? 'selected' : ''; ?>>Bookings Report</option>
                        <option value="occupancy" <?php echo $report_type === 'occupancy' ? 'selected' : ''; ?>>Occupancy Report</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small">From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small">To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Generate</button>
                    <a href="reports.php?type=<?php echo $report_type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&export=csv" class="btn btn-success btn-sm"><i class="bi bi-download"></i> Export CSV</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($report_type === 'sales'): ?>
    <div class="row g-3 mb-4">
        <?php
        $total_revenue = 0; $total_bookings = 0; $avg_per_booking = 0;
        try {
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) BETWEEN ? AND ?");
            $stmt->execute([$branch_id, $date_from, $date_to]);
            $total_revenue = $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND DATE(created_at) BETWEEN ? AND ? AND booking_status != 'cancelled'");
            $stmt->execute([$branch_id, $date_from, $date_to]);
            $total_bookings = $stmt->fetchColumn();

            $avg_per_booking = $total_bookings > 0 ? $total_revenue / $total_bookings : 0;
        } catch (Exception $e) {}
        ?>
        <div class="col-md-4"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body"><p class="stat-label text-muted mb-1 small">Total Revenue</p><h3 class="stat-value mb-0"><?php echo formatMoney($total_revenue); ?></h3></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body"><p class="stat-label text-muted mb-1 small">Total Bookings</p><h3 class="stat-value mb-0"><?php echo number_format($total_bookings); ?></h3></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body"><p class="stat-label text-muted mb-1 small">Avg per Booking</p><h3 class="stat-value mb-0"><?php echo formatMoney($avg_per_booking); ?></h3></div></div></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold">Daily Sales</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Room Revenue</th><th>Food Revenue</th><th>Service Revenue</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php
                        $period = new DatePeriod(new DateTime($date_from), new DateInterval('P1D'), (new DateTime($date_to))->modify('+1 day'));
                        foreach ($period as $d):
                            $date = $d->format('Y-m-d');
                            $room = 0; $food = 0; $service = 0;
                            try {
                                $s = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = ? AND payment_category = 'room'");
                                $s->execute([$branch_id, $date]); $room = $s->fetchColumn();
                                $s = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = ? AND payment_category = 'food'");
                                $s->execute([$branch_id, $date]); $food = $s->fetchColumn();
                                $s = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = ? AND payment_category = 'service'");
                                $s->execute([$branch_id, $date]); $service = $s->fetchColumn();
                            } catch (Exception $e) {}
                        ?>
                        <tr><td><?php echo formatDate($date); ?></td><td><?php echo formatMoney($room); ?></td><td><?php echo formatMoney($food); ?></td><td><?php echo formatMoney($service); ?></td><td><strong><?php echo formatMoney($room + $food + $service); ?></strong></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($report_type === 'bookings'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold">Bookings</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Reference</th><th>Guest</th><th>Room</th><th>Check In</th><th>Check Out</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->prepare("SELECT b.*, c.first_name, c.last_name, rm.room_number FROM bookings b LEFT JOIN customers c ON b.customer_id = c.id LEFT JOIN rooms rm ON b.room_id = rm.id WHERE b.branch_id = ? AND DATE(b.created_at) BETWEEN ? AND ? ORDER BY b.created_at DESC");
                            $stmt->execute([$branch_id, $date_from, $date_to]);
                            while ($b = $stmt->fetch()):
                        ?>
                        <tr><td><?php echo htmlspecialchars($b['reference']); ?></td><td><?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')); ?></td><td><?php echo htmlspecialchars($b['room_number'] ?? 'N/A'); ?></td><td><?php echo formatDate($b['check_in_date']); ?></td><td><?php echo formatDate($b['check_out_date']); ?></td><td><?php echo formatMoney($b['total_amount'] ?? 0); ?></td><td><?php echo getBookingStatusBadge($b['booking_status']); ?></td><td><small><?php echo formatDate($b['created_at']); ?></small></td></tr>
                        <?php
                            endwhile;
                        } catch (Exception $e) { echo '<tr><td colspan="8" class="text-center py-4 text-danger">Error</td></tr>'; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($report_type === 'occupancy'): ?>
    <div class="row g-3 mb-4">
        <?php
        $avg_occupancy = 0; $total_rooms = 0;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ?");
            $stmt->execute([$branch_id]); $total_rooms = $stmt->fetchColumn();
            $stmt = $db->prepare("SELECT ROUND(AVG(cnt) * 100 / ?) FROM (SELECT DATE(check_in_date) as d, COUNT(*) as cnt FROM bookings WHERE branch_id = ? AND booking_status IN ('checked_in','checked_out') AND check_in_date BETWEEN ? AND ? GROUP BY DATE(check_in_date)) sub");
            $stmt->execute([$total_rooms, $branch_id, $date_from, $date_to]);
            $avg_occupancy = $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {}
        ?>
        <div class="col-md-6"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body"><p class="stat-label text-muted mb-1 small">Avg Occupancy Rate</p><h3 class="stat-value mb-0"><?php echo $avg_occupancy; ?>%</h3></div></div></div>
        <div class="col-md-6"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body"><p class="stat-label text-muted mb-1 small">Total Rooms</p><h3 class="stat-value mb-0"><?php echo $total_rooms; ?></h3></div></div></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold">Daily Occupancy</h5></div>
        <div class="card-body">
            <canvas id="occupancyChart" height="300"></canvas>
        </div>
    </div>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white border-bottom-0 py-3"><h5 class="card-title mb-0 fw-semibold">Occupancy by Room Type</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Room Type</th><th>Total</th><th>Occupied</th><th>Available</th><th>Occupancy %</th></tr></thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->prepare("SELECT rt.name, COUNT(*) as total, SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied, SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.branch_id = ? GROUP BY rt.name");
                            $stmt->execute([$branch_id]);
                            while ($row = $stmt->fetch()):
                                $occ_pct = $row['total'] > 0 ? round(($row['occupied'] / $row['total']) * 100) : 0;
                        ?>
                        <tr><td><?php echo htmlspecialchars($row['name']); ?></td><td><?php echo $row['total']; ?></td><td><?php echo $row['occupied']; ?></td><td><?php echo $row['available']; ?></td><td><div class="progress" style="height:20px;"><div class="progress-bar" style="width:<?php echo $occ_pct; ?>%"><?php echo $occ_pct; ?>%</div></div></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                        <?php } catch (Exception $e) {} ?>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($report_type === 'occupancy'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php
    $dates = []; $data = [];
    $period = new DatePeriod(new DateTime($date_from), new DateInterval('P1D'), (new DateTime($date_to))->modify('+1 day'));
    foreach ($period as $d) {
        $date = $d->format('Y-m-d');
        $dates[] = $d->format('M j');
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_status IN ('checked_in','checked_out') AND check_in_date <= ? AND check_out_date >= ?");
            $stmt->execute([$branch_id, $date, $date]);
            $occ = $stmt->fetchColumn();
            $pct = $total_rooms > 0 ? round(($occ / $total_rooms) * 100) : 0;
            $data[] = $pct;
        } catch (Exception $e) { $data[] = 0; }
    }
    ?>
    new Chart(document.getElementById('occupancyChart'), {
        type: 'line', data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{ label: 'Occupancy %', data: <?php echo json_encode($data); ?>, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', fill: true, tension: 0.4 }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } } }
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
