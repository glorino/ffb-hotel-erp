<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Revenue Analytics';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/owner-sidebar.php';

$db = getDB();
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Revenue Analytics</li>
        </ol>
    </nav>

    <form method="GET" class="row g-2 mb-4 align-items-end">
        <div class="col-auto">
            <label class="form-label small text-muted">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
        </div>
        <div class="col-auto">
            <label class="form-label small text-muted">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
        </div>
        <div class="col-auto">
            <label class="form-label small text-muted">&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
        </div>
        <div class="col-auto">
            <label class="form-label small text-muted">&nbsp;</label>
            <a href="revenue.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
    </form>

    <?php
    $total_revenue = 0;
    $room_revenue = 0;
    $food_revenue = 0;
    $service_revenue = 0;

    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND DATE(created_at) BETWEEN ? AND ?");
        $stmt->execute([$date_from, $date_to]);
        $total_revenue = (float) $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0) FROM payments p 
            WHERE p.status = 'paid' AND p.booking_id IS NOT NULL AND DATE(p.created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$date_from, $date_to]);
        $room_revenue = (float) $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0) FROM payments p 
            WHERE p.status = 'paid' AND p.order_id IS NOT NULL AND DATE(p.created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$date_from, $date_to]);
        $food_revenue = (float) $stmt->fetchColumn();

        $service_revenue = $total_revenue - $room_revenue - $food_revenue;
        if ($service_revenue < 0) $service_revenue = 0;
    } catch (Exception $e) {
        error_log('Revenue error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Total Revenue</p>
                    <h3 class="stat-value mb-0"><?php echo formatMoney($total_revenue); ?></h3>
                    <small class="text-muted"><?php echo htmlspecialchars($date_from); ?> to <?php echo htmlspecialchars($date_to); ?></small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Room Revenue</p>
                    <h3 class="stat-value mb-0"><?php echo formatMoney($room_revenue); ?></h3>
                    <div class="progress mt-2" style="height:5px;">
                        <div class="progress-bar bg-primary" style="width:<?php echo $total_revenue > 0 ? ($room_revenue/$total_revenue)*100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Food & Beverage Revenue</p>
                    <h3 class="stat-value mb-0"><?php echo formatMoney($food_revenue); ?></h3>
                    <div class="progress mt-2" style="height:5px;">
                        <div class="progress-bar bg-success" style="width:<?php echo $total_revenue > 0 ? ($food_revenue/$total_revenue)*100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Other Services Revenue</p>
                    <h3 class="stat-value mb-0"><?php echo formatMoney($service_revenue); ?></h3>
                    <div class="progress mt-2" style="height:5px;">
                        <div class="progress-bar bg-info" style="width:<?php echo $total_revenue > 0 ? ($service_revenue/$total_revenue)*100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Revenue Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueLineChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Revenue by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueCategoryChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Revenue by Branch</h5>
        </div>
        <div class="card-body">
            <canvas id="revenueBranchChart" height="250"></canvas>
        </div>
    </div>
</div>

<?php
$line_labels = [];
$line_data = [];
try {
    $period = new DatePeriod(
        new DateTime($date_from),
        new DateInterval('P1D'),
        (new DateTime($date_to))->modify('+1 day')
    );
    foreach ($period as $day) {
        $d = $day->format('Y-m-d');
        $line_labels[] = $day->format('M j');
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND DATE(created_at) = ?");
        $stmt->execute([$d]);
        $line_data[] = (float) $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $line_labels = [];
    $line_data = [];
}

$branch_labels = [];
$branch_data = [];
try {
    $stmt = $db->prepare("
        SELECT br.name, COALESCE(SUM(p.amount), 0) as total
        FROM branches br
        LEFT JOIN bookings b ON br.id = b.branch_id
        LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'paid' AND DATE(p.created_at) BETWEEN ? AND ?
        GROUP BY br.id, br.name
        ORDER BY total DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    while ($row = $stmt->fetch()) {
        $branch_labels[] = $row['name'];
        $branch_data[] = (float) $row['total'];
    }
} catch (Exception $e) {}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('revenueLineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($line_labels); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($line_data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } } }
        }
    });

    new Chart(document.getElementById('revenueCategoryChart'), {
        type: 'doughnut',
        data: {
            labels: ['Room Revenue', 'Food & Beverage', 'Other Services'],
            datasets: [{
                data: [<?php echo $room_revenue; ?>, <?php echo $food_revenue; ?>, <?php echo $service_revenue; ?>],
                backgroundColor: ['#0d6efd', '#198754', '#0dcaf0'],
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } },
            cutout: '60%',
        }
    });

    new Chart(document.getElementById('revenueBranchChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($branch_labels); ?>,
            datasets: [{
                label: 'Revenue by Branch',
                data: <?php echo json_encode($branch_data); ?>,
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6f42c1'],
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
