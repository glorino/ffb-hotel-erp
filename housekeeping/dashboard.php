<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['housekeeping', 'admin', 'branch_manager']);

$page_title = 'Housekeeping Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND r.branch_id = " . (int)$branch_id : "";

$stats = [];
try {
    $stats['to_clean'] = $db->query("SELECT COUNT(*) FROM rooms r WHERE r.status = 'cleaning' $branch_filter")->fetchColumn();
    $stats['cleaned_today'] = $db->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'room_cleaned' AND DATE(created_at) = CURRENT_DATE")->fetchColumn();
    $stats['occupied'] = $db->query("SELECT COUNT(*) FROM rooms r WHERE r.status = 'occupied' $branch_filter")->fetchColumn();
    $stats['maintenance'] = $db->query("SELECT COUNT(*) FROM rooms r WHERE r.status = 'maintenance' $branch_filter")->fetchColumn();
    $stats['available'] = $db->query("SELECT COUNT(*) FROM rooms r WHERE r.status = 'available' $branch_filter")->fetchColumn();
    $stats['total_rooms'] = $db->query("SELECT COUNT(*) FROM rooms r WHERE 1=1 $branch_filter")->fetchColumn();
} catch (Exception $e) {
    error_log('Housekeeping dashboard stats error: ' . $e->getMessage());
    $stats = array_fill_keys(['to_clean','cleaned_today','occupied','maintenance','available','total_rooms'], 0);
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Housekeeping Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a></li>
                    <li class="breadcrumb-item active">Housekeeping</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Rooms to Clean</p>
                            <h3 class="stat-value mb-0"><?php echo $stats['to_clean']; ?></h3>
                            <small class="text-danger"><i class="bi bi-exclamation-circle"></i> Needs attention</small>
                        </div>
                        <div class="stat-icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);border-radius:14px;padding:12px;">
                            <i class="bi bi-broom text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Cleaned Today</p>
                            <h3 class="stat-value mb-0"><?php echo $stats['cleaned_today']; ?></h3>
                            <small class="text-success"><i class="bi bi-check-circle"></i> Completed</small>
                        </div>
                        <div class="stat-icon" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:14px;padding:12px;">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Occupied Rooms</p>
                            <h3 class="stat-value mb-0"><?php echo $stats['occupied']; ?></h3>
                            <small class="text-info"><i class="bi bi-person"></i> In use</small>
                        </div>
                        <div class="stat-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);border-radius:14px;padding:12px;">
                            <i class="bi bi-bed text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Maintenance</p>
                            <h3 class="stat-value mb-0"><?php echo $stats['maintenance']; ?></h3>
                            <small class="text-warning"><i class="bi bi-wrench"></i> Repair needed</small>
                        </div>
                        <div class="stat-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:14px;padding:12px;">
                            <i class="bi bi-wrench text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Available</p>
                            <h3 class="stat-value mb-0"><?php echo $stats['available']; ?></h3>
                            <small class="text-success"><i class="bi bi-door-open"></i> Ready</small>
                        </div>
                        <div class="stat-icon" style="background:linear-gradient(135deg,#cffafe,#a5f3fc);border-radius:14px;padding:12px;">
                            <i class="bi bi-door-open text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Total Rooms</p>
                            <h3 class="stat-value mb-0"><?php echo $stats['total_rooms']; ?></h3>
                            <small class="text-secondary"><i class="bi bi-building"></i> All rooms</small>
                        </div>
                        <div class="stat-icon" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);border-radius:14px;padding:12px;">
                            <i class="bi bi-building text-secondary fs-4"></i>
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
                    <h5 class="card-title mb-0 fw-semibold">Cleaning Activity</h5>
                </div>
                <div class="card-body">
                    <canvas id="cleaningChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Room Status Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="roomStatusChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0 fw-semibold">Today's Cleaning List</h5>
                    <a href="rooms-to-clean.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Room</th>
                                    <th>Floor</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $rooms = $db->prepare("SELECT r.*, rt.name as type_name 
                                        FROM rooms r 
                                        JOIN room_types rt ON r.room_type_id = rt.id 
                                        WHERE r.status IN ('cleaning', 'occupied')
                                        " . ($branch_id ? "AND r.branch_id = " . (int)$branch_id : "") . "
                                        ORDER BY r.floor, r.room_number LIMIT 10");
                                    $rooms->execute();
                                    if ($rooms->rowCount() > 0):
                                        while ($room = $rooms->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($room['floor']); ?></td>
                                        <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                                        <td><?php echo getRoomStatusBadge($room['status']); ?></td>
                                        <td>
                                            <?php if ($room['status'] == 'cleaning'): ?>
                                                <span class="badge bg-warning">Normal</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Routine</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="rooms-to-clean.php?action=start&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-primary">Clean</a>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No rooms need cleaning</td></tr>
                                    <?php
                                    endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="6" class="text-danger">Error loading rooms</td></tr>';
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
$cleaning_labels = [];
$cleaning_data = [];
try {
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $cleaning_labels[] = date('D', strtotime($day));
        $stmt = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'room_cleaned' AND DATE(created_at) = ?");
        $stmt->execute([$day]);
        $cleaning_data[] = (int) $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $cleaning_labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $cleaning_data = [0,0,0,0,0,0,0];
}
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('cleaningChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($cleaning_labels); ?>,
            datasets: [{
                label: 'Rooms Cleaned',
                data: <?php echo json_encode($cleaning_data); ?>,
                borderColor: '#d4af37',
                backgroundColor: 'rgba(212, 175, 55, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#d4af37'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    new Chart(document.getElementById('roomStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Available', 'Occupied', 'Cleaning', 'Maintenance'],
            datasets: [{
                data: [<?php echo $stats['available'] . ', ' . $stats['occupied'] . ', ' . $stats['to_clean'] . ', ' . $stats['maintenance']; ?>],
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } },
            cutout: '65%'
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
