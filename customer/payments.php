<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'My Payments';
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

$stmt = $db->prepare("SELECT p.*, b.reference as booking_ref, b.check_in_date, b.check_out_date, rm.room_number FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id LEFT JOIN rooms rm ON b.room_id = rm.id WHERE p.customer_id = ? ORDER BY p.created_at DESC");
$stmt->execute([$customer_id]);
$payments = $stmt->fetchAll();
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">My Payments</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <?php
                    $total_paid = 0;
                    try {
                        $st = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE customer_id = ? AND status = 'paid'");
                        $st->execute([$customer_id]);
                        $total_paid = $st->fetchColumn();
                    } catch (Exception $e) {}
                    ?>
                    <h5 class="mb-0">Total Paid: <span class="text-success"><?php echo formatMoney($total_paid); ?></span></h5>
                </div>
                <a href="<?php echo $base_url; ?>booking.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Make a Booking</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Reference</th><th>Description</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Receipt</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><small class="fw-medium"><?php echo htmlspecialchars($p['reference'] ?? 'PAY-' . $p['id']); ?></small></td>
                            <td>
                                <small>
                                    <?php if ($p['booking_ref']): ?>
                                    Booking: <?php echo htmlspecialchars($p['booking_ref']); ?>
                                    <?php if ($p['room_number']): ?><br>Room: <?php echo htmlspecialchars($p['room_number']); ?><?php endif; ?>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars(ucfirst($p['payment_category'] ?? 'Payment')); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td><strong><?php echo formatMoney($p['amount']); ?></strong></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $p['payment_method'] ?? '—'))); ?></td>
                            <td><?php echo getPaymentStatusBadge($p['status']); ?></td>
                            <td><small class="text-muted"><?php echo formatDate($p['created_at']); ?></small></td>
                            <td>
                                <a href="<?php echo $base_url; ?>receipt.php?payment=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-receipt"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; if (empty($payments)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No payments found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
