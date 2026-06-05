<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['housekeeping', 'admin', 'branch_manager']);

$page_title = 'Housekeeping Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/housekeeping-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "WHERE branch_id = " . (int)$branch_id : "";
$branch_filter_rooms = $branch_id ? "WHERE r.branch_id = " . (int)$branch_id : "";

$to_clean = $db->query("SELECT COUNT(*) FROM rooms $branch_filter_rooms AND status = 'cleaning'")->fetchColumn();
$cleaned_today = $db->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'room_cleaned' AND DATE(created_at) = CURRENT_DATE")->fetchColumn();
$occupied = $db->query("SELECT COUNT(*) FROM rooms $branch_filter_rooms AND status = 'occupied'")->fetchColumn();
$maintenance = $db->query("SELECT COUNT(*) FROM rooms $branch_filter_rooms AND status = 'maintenance'")->fetchColumn();
$available = $db->query("SELECT COUNT(*) FROM rooms $branch_filter_rooms AND status = 'available'")->fetchColumn();
$total_rooms = $db->query("SELECT COUNT(*) FROM rooms $branch_filter_rooms")->fetchColumn();
?>
<main class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Housekeeping Dashboard</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Housekeeping</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-danger bg-opacity-10"><i class="fas fa-broom text-danger"></i></div>
                    <div class="stat-details">
                        <h3 class="stat-value"><?php echo $to_clean; ?></h3>
                        <p class="stat-label">Rooms to Clean</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10"><i class="fas fa-check-circle text-success"></i></div>
                    <div class="stat-details">
                        <h3 class="stat-value"><?php echo $cleaned_today; ?></h3>
                        <p class="stat-label">Cleaned Today</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10"><i class="fas fa-bed text-primary"></i></div>
                    <div class="stat-details">
                        <h3 class="stat-value"><?php echo $occupied; ?></h3>
                        <p class="stat-label">Occupied Rooms</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10"><i class="fas fa-wrench text-warning"></i></div>
                    <div class="stat-details">
                        <h3 class="stat-value"><?php echo $maintenance; ?></h3>
                        <p class="stat-label">Maintenance</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-info bg-opacity-10"><i class="fas fa-check-circle text-info"></i></div>
                    <div class="stat-details">
                        <h3 class="stat-value"><?php echo $available; ?></h3>
                        <p class="stat-label">Available</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-secondary bg-opacity-10"><i class="fas fa-door-open text-secondary"></i></div>
                    <div class="stat-details">
                        <h3 class="stat-value"><?php echo $total_rooms; ?></h3>
                        <p class="stat-label">Total Rooms</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Cleaning Activity</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="cleaningChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Room Status Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="roomStatusChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Today's Cleaning List</h5>
                        <a href="rooms-to-clean.php" class="btn btn-sm btn-gold">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
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
                                    $rooms = $db->query("SELECT r.*, rt.name as type_name 
                                        FROM rooms r 
                                        JOIN room_types rt ON r.room_type_id = rt.id 
                                        $branch_filter_rooms AND r.status IN ('cleaning', 'occupied')
                                        ORDER BY r.floor, r.room_number LIMIT 10");
                                    if ($rooms && $rooms->rowCount() > 0):
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
                                            <a href="rooms-to-clean.php?action=start&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-gold">Clean</a>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr><td colspan="6" class="text-center text-muted">No rooms need cleaning</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/dashboard-footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('cleaningChart'), {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Rooms Cleaned',
                data: [12, 15, 11, 18, 14, 20, 16],
                borderColor: '#d4af37',
                backgroundColor: 'rgba(212, 175, 55, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    new Chart(document.getElementById('roomStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Available', 'Occupied', 'Cleaning', 'Maintenance'],
            datasets: [{
                data: [<?php echo "$available, $occupied, $to_clean, $maintenance"; ?>],
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
