<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'My Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/customer-sidebar.php';

$db = getDB();
$customer_id = $_SESSION['customer_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (!$customer_id) {
    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer_id = $stmt->fetchColumn();
    if ($customer_id) $_SESSION['customer_id'] = $customer_id;
}

$stats = [];
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $stats['total_bookings'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND booking_status IN ('confirmed','checked_in')");
    $stmt->execute([$customer_id]);
    $stats['active_bookings'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $stats['total_orders'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $stats['loyalty_points'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    error_log('Customer dashboard error: ' . $e->getMessage());
}
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Total Bookings</p>
                            <h3 class="stat-value mb-0 text-primary"><?php echo $stats['total_bookings'] ?? 0; ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-3"><i class="bi bi-calendar-check text-primary fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Active Bookings</p>
                            <h3 class="stat-value mb-0 text-success"><?php echo $stats['active_bookings'] ?? 0; ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-3"><i class="bi bi-house-check text-success fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Total Orders</p>
                            <h3 class="stat-value mb-0 text-warning"><?php echo $stats['total_orders'] ?? 0; ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3"><i class="bi bi-clipboard-list text-warning fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Loyalty Points</p>
                            <h3 class="stat-value mb-0 text-info"><?php echo number_format($stats['loyalty_points'] ?? 0); ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3"><i class="bi bi-gem text-info fs-4"></i></div>
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
                            <a href="<?php echo $base_url; ?>booking.php" class="btn btn-lg btn-outline-primary w-100 py-3">
                                <i class="bi bi-calendar-plus fs-4 d-block mb-1"></i><span>Book a Room</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="<?php echo $base_url; ?>order.php" class="btn btn-lg btn-outline-success w-100 py-3">
                                <i class="bi bi-utensils fs-4 d-block mb-1"></i><span>Order Food</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="<?php echo $base_url; ?>reservation.php" class="btn btn-lg btn-outline-warning w-100 py-3">
                                <i class="bi bi-clock fs-4 d-block mb-1"></i><span>Make Reservation</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="profile.php" class="btn btn-lg btn-outline-info w-100 py-3">
                                <i class="bi bi-person fs-4 d-block mb-1"></i><span>View Profile</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Upcoming Bookings</h5>
                    <a href="my-bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php
                    try {
                        $stmt = $db->prepare("SELECT b.*, rm.room_number, rt.name as room_type FROM bookings b LEFT JOIN rooms rm ON b.room_id = rm.id LEFT JOIN room_types rt ON rm.room_type_id = rt.id WHERE b.customer_id = ? AND b.booking_status IN ('confirmed','checked_in') ORDER BY b.check_in_date ASC LIMIT 5");
                        $stmt->execute([$customer_id]);
                        $bookings = $stmt->fetchAll();
                        foreach ($bookings as $b):
                    ?>
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($b['room_type'] ?? 'Room'); ?> <small class="text-muted">(<?php echo htmlspecialchars($b['room_number'] ?? '—'); ?>)</small></p>
                                <small class="text-muted"><?php echo formatDate($b['check_in_date']); ?> - <?php echo formatDate($b['check_out_date']); ?></small>
                            </div>
                            <?php echo getBookingStatusBadge($b['booking_status']); ?>
                        </div>
                    </div>
                    <?php endforeach; if (empty($bookings)): ?>
                    <div class="text-center py-4 text-muted"><p class="mb-0">No upcoming bookings</p><a href="<?php echo $base_url; ?>booking.php" class="btn btn-sm btn-primary mt-2">Book Now</a></div>
                    <?php } catch (Exception $e) { ?>
                    <div class="text-center py-4 text-danger">Error loading bookings</div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Recent Orders</h5>
                    <a href="my-orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php
                    try {
                        $stmt = $db->prepare("SELECT * FROM food_orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute([$customer_id]);
                        $orders = $stmt->fetchAll();
                        foreach ($orders as $o):
                    ?>
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($o['reference'] ?? 'ORD-' . $o['id']); ?></p>
                                <small class="text-muted"><?php echo formatMoney($o['total_amount'] ?? 0); ?> &middot; <?php echo timeAgo($o['created_at']); ?></small>
                            </div>
                            <?php
                            $badges = ['pending'=>'warning','preparing'=>'info','ready'=>'success','completed'=>'secondary','cancelled'=>'danger'];
                            $bc = $badges[$o['status']] ?? 'secondary';
                            echo "<span class='badge bg-{$bc}'>" . htmlspecialchars($o['status']) . "</span>";
                            ?>
                        </div>
                    </div>
                    <?php endforeach; if (empty($orders)): ?>
                    <div class="text-center py-4 text-muted"><p class="mb-0">No orders yet</p><a href="<?php echo $base_url; ?>order.php" class="btn btn-sm btn-success mt-2">Order Food</a></div>
                    <?php } catch (Exception $e) { ?>
                    <div class="text-center py-4 text-danger">Error loading orders</div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Recent Payments</h5>
                    <a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php
                    try {
                        $stmt = $db->prepare("SELECT * FROM payments WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute([$customer_id]);
                        $payments = $stmt->fetchAll();
                        foreach ($payments as $p):
                    ?>
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 fw-medium"><?php echo formatMoney($p['amount']); ?></p>
                                <small class="text-muted"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $p['payment_method'] ?? ''))); ?> &middot; <?php echo timeAgo($p['created_at']); ?></small>
                            </div>
                            <?php echo getPaymentStatusBadge($p['status']); ?>
                        </div>
                    </div>
                    <?php endforeach; if (empty($payments)): ?>
                    <div class="text-center py-4 text-muted"><p class="mb-0">No payments yet</p></div>
                    <?php } catch (Exception $e) { ?>
                    <div class="text-center py-4 text-danger">Error loading payments</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
