<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'My Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$customer_id = $_SESSION['customer_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$first_name = htmlspecialchars($_SESSION['full_name'] ?? 'Guest');
if (strpos($first_name, ' ') !== false) $first_name = htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'Guest')[0]);

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

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE customer_id = ? AND status = 'paid'");
    $stmt->execute([$customer_id]);
    $stats['total_spent'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND booking_status = 'checked_out'");
    $stmt->execute([$customer_id]);
    $stats['completed_stays'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log('Customer dashboard error: ' . $e->getMessage());
}
?>
<div class="container-fluid">
    <div class="mb-4 p-4 rounded-4" style="background:linear-gradient(135deg, #0f1a2e 0%, #1a2744 50%, #0f1a2e 100%);border:1px solid rgba(201,168,76,0.2);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="text-white mb-1 fw-bold">Welcome back, <?php echo $first_name; ?>!</h4>
                <p class="text-white-50 mb-0"><i class="bi bi-calendar3 me-1"></i><?php echo date('l, F j, Y'); ?> &mdash; Here's your hospitality overview</p>
            </div>
            <a href="<?php echo $base_url; ?>booking.php" class="btn btn-gold"><i class="bi bi-calendar-plus me-1"></i> Book a Room</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Total Spent</p>
                            <h3 class="stat-value mb-0 text-primary"><?php echo formatMoney($stats['total_spent'] ?? 0); ?></h3>
                            <small class="text-muted">Lifetime spending</small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-3"><i class="bi bi-wallet2 text-primary fs-4"></i></div>
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
                            <small class="text-muted">Current & upcoming</small>
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
                            <p class="stat-label text-muted mb-1 small">Completed Stays</p>
                            <h3 class="stat-value mb-0 text-info"><?php echo $stats['completed_stays'] ?? 0; ?></h3>
                            <small class="text-muted">Past visits</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3"><i class="bi bi-check2-circle text-info fs-4"></i></div>
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
                            <h3 class="stat-value mb-0 text-warning"><?php echo number_format($stats['loyalty_points'] ?? 0); ?></h3>
                            <small class="text-muted">Reward points</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3"><i class="bi bi-gem text-warning fs-4"></i></div>
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

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Spending Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="spendingChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title fw-semibold mb-3">Loyalty Status</h6>
                    <?php
                    $points = $stats['loyalty_points'] ?? 0;
                    $tier = 'Bronze';
                    $tier_color = '#cd7f32';
                    $next_tier = 'Silver';
                    $next_points = 500;
                    if ($points >= 2000) { $tier = 'Platinum'; $tier_color = '#e5e4e2'; $next_tier = null; $next_points = $points; }
                    elseif ($points >= 1000) { $tier = 'Gold'; $tier_color = '#c9a84c'; $next_tier = 'Platinum'; $next_points = 2000; }
                    elseif ($points >= 500) { $tier = 'Silver'; $tier_color = '#c0c0c0'; $next_tier = 'Gold'; $next_points = 1000; }
                    $progress = $next_tier ? min(100, ($points / $next_points) * 100) : 100;
                    ?>
                    <div class="text-center mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width:60px;height:60px;background:<?php echo $tier_color; ?>20;border:2px solid <?php echo $tier_color; ?>;">
                            <i class="bi bi-trophy fs-4" style="color:<?php echo $tier_color; ?>;"></i>
                        </div>
                        <h5 class="mb-0 fw-bold" style="color:<?php echo $tier_color; ?>;"><?php echo $tier; ?></h5>
                        <small class="text-muted"><?php echo number_format($points); ?> points</small>
                    </div>
                    <?php if ($next_tier): ?>
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between mb-1" style="font-size:0.8rem;">
                            <span><?php echo $tier; ?></span>
                            <span><?php echo $next_tier; ?></span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px;">
                            <div class="progress-bar" style="width:<?php echo $progress; ?>%;background:<?php echo $tier_color; ?>;border-radius:4px;"></div>
                        </div>
                        <small class="text-muted mt-1 d-block"><?php echo number_format($next_points - $points); ?> points to <?php echo $next_tier; ?></small>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-muted mt-auto mb-0" style="font-size:0.85rem;"><i class="bi bi-star-fill text-warning me-1"></i> You've reached the highest tier!</p>
                    <?php endif; ?>
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

<?php
$spend_labels = []; $spend_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $spend_labels[] = date('M Y', strtotime($month . '-01'));
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE customer_id = ? AND status = 'paid' AND TO_CHAR(created_at, 'YYYY-MM') = ?");
        $stmt->execute([$customer_id, $month]);
        $spend_data[] = (float) $stmt->fetchColumn();
    } catch (Exception $e) { $spend_data[] = 0; }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('spendingChart'), {
        type: 'line', data: {
            labels: <?php echo json_encode($spend_labels); ?>,
            datasets: [{
                label: 'Spending (<?php echo CURRENCY_SYMBOL; ?>)', data: <?php echo json_encode($spend_data); ?>,
                borderColor: '#c9a84c', backgroundColor: 'rgba(201,168,76,0.1)', fill: true, tension: 0.4, pointRadius: 5, pointBackgroundColor: '#0f1a2e', pointBorderColor: '#c9a84c', pointBorderWidth: 2
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } }, x: { grid: { display: false } } } }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
