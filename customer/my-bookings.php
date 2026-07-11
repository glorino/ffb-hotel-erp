<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'My Bookings';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$customer_id = $_SESSION['customer_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
if (!$customer_id) {
    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer_id = $stmt->fetchColumn();
    if ($customer_id) $_SESSION['customer_id'] = $customer_id;
}

if (isset($_GET['cancel']) && (int)$_GET['cancel']) {
    try {
        $stmt = $db->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ? AND customer_id = ? AND booking_status IN ('pending','confirmed')");
        $stmt->execute([(int)$_GET['cancel'], $customer_id]);
        if ($stmt->rowCount()) {
            set_flash('success', 'Booking cancelled successfully');
        } else {
            set_flash('warning', 'Booking cannot be cancelled or not found');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: my-bookings.php'); exit;
}

$status_filter = $_GET['status'] ?? '';
$where = "b.customer_id = ?"; $params = [$customer_id];
if ($status_filter) { $where .= " AND b.booking_status = ?"; $params[] = $status_filter; }

$stmt = $db->prepare("SELECT b.*, rm.room_number, rt.name as room_type, rt.base_price, br.name as branch_name, p.status as payment_status, p.reference as payment_ref FROM bookings b LEFT JOIN rooms rm ON b.room_id = rm.id LEFT JOIN room_types rt ON rm.room_type_id = rt.id LEFT JOIN branches br ON b.branch_id = br.id LEFT JOIN payments p ON p.booking_id = b.id AND p.payment_category = 'room' WHERE $where ORDER BY b.created_at DESC");
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">My Bookings</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <a href="?status=" class="btn btn-sm <?php echo !$status_filter ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                    <a href="?status=pending" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Pending</a>
                    <a href="?status=confirmed" class="btn btn-sm <?php echo $status_filter === 'confirmed' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Confirmed</a>
                    <a href="?status=checked_in" class="btn btn-sm <?php echo $status_filter === 'checked_in' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Checked In</a>
                    <a href="?status=checked_out" class="btn btn-sm <?php echo $status_filter === 'checked_out' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Checked Out</a>
                    <a href="?status=cancelled" class="btn btn-sm <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Cancelled</a>
                </div>
                <a href="<?php echo $base_url; ?>booking.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Booking</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($bookings as $b): ?>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($b['room_type'] ?? 'Room Booking'); ?></h6>
                            <small class="text-muted">Ref: <?php echo htmlspecialchars($b['booking_reference'] ?? 'BKG-' . $b['id']); ?></small>
                        </div>
                        <?php echo getBookingStatusBadge($b['booking_status']); ?>
                    </div>
                    <div class="row g-2 mb-3 small">
                        <div class="col-6"><span class="text-muted">Room:</span> <?php echo htmlspecialchars($b['room_number'] ?? '—'); ?></div>
                        <div class="col-6"><span class="text-muted">Branch:</span> <?php echo htmlspecialchars($b['branch_name'] ?? '—'); ?></div>
                        <div class="col-6"><span class="text-muted">Check-in:</span> <?php echo formatDate($b['check_in_date']); ?></div>
                        <div class="col-6"><span class="text-muted">Check-out:</span> <?php echo formatDate($b['check_out_date']); ?></div>
                        <div class="col-6"><span class="text-muted">Amount:</span> <strong><?php echo formatMoney($b['total_amount'] ?? $b['base_price'] ?? 0); ?></strong></div>
                        <div class="col-6"><span class="text-muted">Payment:</span> <?php echo $b['payment_status'] ? getPaymentStatusBadge($b['payment_status']) : '<span class="badge bg-secondary">—</span>'; ?></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?php echo $base_url; ?>modules/payments/receipt-generator.php?booking=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-receipt"></i> Receipt</a>
                        <?php if (in_array($b['booking_status'], ['pending', 'confirmed'])): ?>
                        <a href="?cancel=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking?')"><i class="bi bi-x-circle"></i> Cancel</a>
                        <?php endif; ?>
                        <a href="<?php echo $base_url; ?>booking.php?room_id=<?php echo $b['room_id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Book Again</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; if (empty($bookings)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-calendar-x display-4 text-muted"></i>
                    <h5 class="mt-3">No Bookings Found</h5>
                    <p class="text-muted">You haven't made any bookings yet.</p>
                    <a href="<?php echo $base_url; ?>booking.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Book a Room</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
