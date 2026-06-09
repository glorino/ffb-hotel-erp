<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Room Occupancy Analytics';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Room Occupancy</li>
        </ol>
    </nav>

    <?php
    $total_rooms = 0; $occupied = 0; $available = 0; $reserved = 0; $maintenance = 0; $cleaning = 0;
    try {
        $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM rooms GROUP BY status");
        while ($row = $stmt->fetch()) {
            ${$row['status']} = (int) $row['cnt'];
            $total_rooms += (int) $row['cnt'];
        }
    } catch (Exception $e) {}

    $occupancy_rate = $total_rooms > 0 ? round(($occupied / $total_rooms) * 100) : 0;
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Total Rooms</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($total_rooms); ?></h3>
                    <small class="text-muted">Across all branches</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Occupied Rooms</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($occupied); ?></h3>
                    <small class="text-warning">Currently occupied</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Available Rooms</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($available); ?></h3>
                    <small class="text-success">Ready for booking</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Occupancy Rate</p>
                    <h3 class="stat-value mb-0"><?php echo $occupancy_rate; ?>%</h3>
                    <div class="progress mt-2" style="height:6px;">
                        <div class="progress-bar bg-success" style="width:<?php echo $occupancy_rate; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Room Status Distribution</h5>
                </div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="occupancyPieChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Occupancy by Room Type</h5>
                </div>
                <div class="card-body">
                    <canvas id="occupancyTypeChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Occupancy by Branch</h5>
                </div>
                <div class="card-body">
                    <canvas id="occupancyBranchChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Monthly Occupancy Trends</h5>
        </div>
        <div class="card-body">
            <canvas id="occupancyTrendChart" height="280"></canvas>
        </div>
    </div>
</div>

<?php
$type_labels = []; $type_occupied = []; $type_total = [];
try {
    $stmt = $db->query("
        SELECT rt.name as type_name, 
               COUNT(r.id) as total,
               SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied
        FROM room_types rt
        LEFT JOIN rooms r ON rt.id = r.room_type_id
        GROUP BY rt.id, rt.name
    ");
    while ($row = $stmt->fetch()) {
        $type_labels[] = $row['type_name'];
        $type_total[] = (int) $row['total'];
        $type_occupied[] = (int) $row['occupied'];
    }
} catch (Exception $e) {}

$branch_labels = []; $branch_occupied = []; $branch_total = [];
try {
    $stmt = $db->query("
        SELECT br.name, COUNT(r.id) as total,
               SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied
        FROM branches br
        LEFT JOIN rooms r ON br.id = r.branch_id
        GROUP BY br.id, br.name
    ");
    while ($row = $stmt->fetch()) {
        $branch_labels[] = $row['name'];
        $branch_total[] = (int) $row['total'];
        $branch_occupied[] = (int) $row['occupied'];
    }
} catch (Exception $e) {}

$trend_labels = []; $trend_data = [];
for ($m = 5; $m >= 0; $m--) {
    $month = date('Y-m', strtotime("-$m months"));
    $trend_labels[] = date('M Y', strtotime($month . '-01'));
    try {
        $stmt = $db->prepare("
            SELECT ROUND(AVG(daily_occ.total_rooms > 0 ? (daily_occ.occupied / daily_occ.total_rooms) * 100 : 0), 1)
            FROM (
                SELECT DATE(b.check_in_date) as day, COUNT(DISTINCT b.room_id) as occupied,
                       (SELECT COUNT(*) FROM rooms WHERE branch_id IN (SELECT id FROM branches)) as total_rooms
                FROM bookings b
                WHERE b.booking_status IN ('checked_in', 'confirmed')
                  AND DATE(b.check_in_date) LIKE ?
                GROUP BY DATE(b.check_in_date)
            ) daily_occ
        ");
        $stmt->execute(["$month%"]);
        $trend_data[] = (float) $stmt->fetchColumn();
    } catch (Exception $e) {
        $trend_data[] = 0;
    }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('occupancyPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Occupied (<?php echo $occupied; ?>)', 'Available (<?php echo $available; ?>)', 'Reserved (<?php echo $reserved; ?>)', 'Cleaning (<?php echo $cleaning; ?>)', 'Maintenance (<?php echo $maintenance; ?>)'],
            datasets: [{
                data: [<?php echo "$occupied, $available, $reserved, $cleaning, $maintenance"; ?>],
                backgroundColor: ['#ffc107', '#198754', '#0dcaf0', '#6c757d', '#dc3545'],
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } },
            cutout: '60%',
        }
    });

    new Chart(document.getElementById('occupancyTypeChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($type_labels); ?>,
            datasets: [
                { label: 'Occupied', data: <?php echo json_encode($type_occupied); ?>, backgroundColor: '#ffc107', borderRadius: 4 },
                { label: 'Total', data: <?php echo json_encode($type_total); ?>, backgroundColor: '#e9ecef', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    new Chart(document.getElementById('occupancyBranchChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($branch_labels); ?>,
            datasets: [
                { label: 'Occupied', data: <?php echo json_encode($branch_occupied); ?>, backgroundColor: '#0d6efd', borderRadius: 4 },
                { label: 'Total Rooms', data: <?php echo json_encode($branch_total); ?>, backgroundColor: '#e9ecef', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    new Chart(document.getElementById('occupancyTrendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trend_labels); ?>,
            datasets: [{
                label: 'Occupancy Rate (%)',
                data: <?php echo json_encode($trend_data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
