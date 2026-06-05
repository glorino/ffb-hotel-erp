<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Reception Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/reception-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Reception Dashboard</li>
        </ol>
    </nav>

    <?php
    $stats = [];
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ? AND status = 'available'");
        $stmt->execute([$branch_id]);
        $stats['available_rooms'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE branch_id = ? AND status = 'occupied'");
        $stmt->execute([$branch_id]);
        $stats['occupied_rooms'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_status = 'pending'");
        $stmt->execute([$branch_id]);
        $stats['pending_bookings'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_status = 'confirmed' AND check_in_date = CURRENT_DATE");
        $stmt->execute([$branch_id]);
        $stats['checkins_today'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_status = 'checked_in' AND check_out_date = CURRENT_DATE");
        $stmt->execute([$branch_id]);
        $stats['checkouts_today'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = CURRENT_DATE");
        $stmt->execute([$branch_id]);
        $stats['payments_today'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM chat_sessions WHERE branch_id = ? AND status = 'active'");
        $stmt->execute([$branch_id]);
        $stats['active_chats'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Reception dashboard error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Available</p>
                            <h3 class="stat-value mb-0 text-success"><?php echo $stats['available_rooms'] ?? 0; ?></h3>
                            <small class="text-muted small">Rooms ready</small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-2"><i class="bi bi-house-check text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Occupied</p>
                            <h3 class="stat-value mb-0 text-warning"><?php echo $stats['occupied_rooms'] ?? 0; ?></h3>
                            <small class="text-muted small">In use</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-2"><i class="bi bi-door-open text-warning"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Pending Bookings</p>
                            <h3 class="stat-value mb-0 text-info"><?php echo $stats['pending_bookings'] ?? 0; ?></h3>
                            <small class="text-muted small">Awaiting action</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-2"><i class="bi bi-clock-history text-info"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Check-ins Today</p>
                            <h3 class="stat-value mb-0 text-primary"><?php echo $stats['checkins_today'] ?? 0; ?></h3>
                            <small class="text-muted small">Arrivals</small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-2"><i class="bi bi-box-arrow-in-right text-primary"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Check-outs Today</p>
                            <h3 class="stat-value mb-0 text-secondary"><?php echo $stats['checkouts_today'] ?? 0; ?></h3>
                            <small class="text-muted small">Departures</small>
                        </div>
                        <div class="stat-icon bg-secondary-subtle rounded-3 p-2"><i class="bi bi-box-arrow-right text-secondary"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Active Chats</p>
                            <h3 class="stat-value mb-0 text-danger"><?php echo $stats['active_chats'] ?? 0; ?></h3>
                            <small class="text-muted small">Live conversations</small>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-2"><i class="bi bi-chat-dots text-danger"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3 col-6">
                            <a href="walk-in-booking.php" class="btn btn-lg btn-outline-primary w-100 py-3">
                                <i class="bi bi-person-plus fs-4 d-block mb-1"></i>
                                <span>Walk-in Booking</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="check-in.php" class="btn btn-lg btn-outline-success w-100 py-3">
                                <i class="bi bi-box-arrow-in-right fs-4 d-block mb-1"></i>
                                <span>Check-in</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="check-out.php" class="btn btn-lg btn-outline-warning w-100 py-3">
                                <i class="bi bi-box-arrow-right fs-4 d-block mb-1"></i>
                                <span>Check-out</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="room-availability.php" class="btn btn-lg btn-outline-info w-100 py-3">
                                <i class="bi bi-bed fs-4 d-block mb-1"></i>
                                <span>Room Availability</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Recent Bookings</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Guest</th><th>Room</th><th>Dates</th><th>Status</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT b.*, c.first_name, c.last_name, rm.room_number FROM bookings b LEFT JOIN customers c ON b.customer_id = c.id LEFT JOIN rooms rm ON b.room_id = rm.id WHERE b.branch_id = ? ORDER BY b.created_at DESC LIMIT 10");
                                    $stmt->execute([$branch_id]);
                                    while ($b = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? 'Guest')); ?></td>
                                    <td><?php echo htmlspecialchars($b['room_number'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo formatDate($b['check_in_date']); ?> - <?php echo formatDate($b['check_out_date']); ?></small></td>
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
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Recent Guests</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Name</th><th>Email</th><th>Phone</th><th>Last Visit</th><th>Bookings</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM bookings WHERE customer_id = c.id) as total_bookings, (SELECT MAX(created_at) FROM bookings WHERE customer_id = c.id) as last_visit FROM customers c WHERE c.branch_id = ? OR c.id IN (SELECT customer_id FROM bookings WHERE branch_id = ?) ORDER BY last_visit DESC LIMIT 10");
                                    $stmt->execute([$branch_id, $branch_id]);
                                    while ($c = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($c['email'] ?? '-'); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($c['phone'] ?? '-'); ?></small></td>
                                    <td><small class="text-muted"><?php echo $c['last_visit'] ? timeAgo($c['last_visit']) : '-'; ?></small></td>
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

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
