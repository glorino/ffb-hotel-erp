<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Payments';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/reception-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_category = $_POST['payment_category'] ?? 'room';
    $reference = generateReference('PAY');
    try {
        $stmt = $db->prepare("INSERT INTO payments (branch_id, booking_id, customer_id, reference, amount, payment_method, payment_category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', NOW())");
        $stmt->execute([$branch_id, $booking_id ?: null, $customer_id ?: null, $reference, $amount, $payment_method, $payment_category]);
        log_audit('record_payment', 'payment', $db->lastInsertId());
        set_flash('success', "Payment of {$amount} recorded. Reference: {$reference}");
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: payments.php');
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Payments</li>
        </ol>
    </nav>

    <?php
    $today_payments = 0;
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = CURRENT_DATE");
        $stmt->execute([$branch_id]); $today_payments = $stmt->fetchColumn();
    } catch (Exception $e) {}
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Today's Collections</p>
                    <h3 class="stat-value mb-0 text-success"><?php echo formatMoney($today_payments); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold">Record Payment</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label small">Booking (optional)</label>
                            <select name="booking_id" class="form-select">
                                <option value="">Select booking</option>
                                <?php
                                $stmt = $db->prepare("SELECT b.id, b.reference, c.first_name, c.last_name FROM bookings b LEFT JOIN customers c ON b.customer_id = c.id WHERE b.branch_id = ? AND b.booking_status IN ('confirmed','checked_in') ORDER BY b.created_at DESC LIMIT 50");
                                $stmt->execute([$branch_id]);
                                while ($b = $stmt->fetch()):
                                ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['reference'] . ' - ' . ($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Customer (if no booking)</label>
                            <select name="customer_id" class="form-select">
                                <option value="">Select customer</option>
                                <?php
                                $custs = $db->prepare("SELECT id, first_name, last_name, email FROM customers WHERE branch_id = ? OR branch_id IS NULL ORDER BY first_name LIMIT 100");
                                $custs->execute([$branch_id]);
                                while ($c = $custs->fetch()):
                                ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . ($c['email'] ?? '') . ')'); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Select method</option>
                                <option value="cash">Cash</option>
                                <option value="pos">POS Terminal</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="paystack">Paystack</option>
                                <option value="split_payment">Split Payment</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Category</label>
                            <select name="payment_category" class="form-select">
                                <option value="room">Room</option>
                                <option value="food">Food</option>
                                <option value="service">Service</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <button type="submit" name="record_payment" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-2"></i>Record Payment</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Recent Payments</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Guest</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("
                                        SELECT p.*, c.first_name, c.last_name, b.reference as booking_ref
                                        FROM payments p
                                        LEFT JOIN customers c ON p.customer_id = c.id
                                        LEFT JOIN bookings b ON p.booking_id = b.id
                                        WHERE p.branch_id = ?
                                        ORDER BY p.created_at DESC LIMIT 50
                                    ");
                                    $stmt->execute([$branch_id]);
                                    $payments = $stmt->fetchAll();
                                    foreach ($payments as $p):
                                ?>
                                <tr>
                                    <td><small><?php echo htmlspecialchars($p['reference'] ?? $p['id']); ?></small></td>
                                    <td><?php echo htmlspecialchars(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '—')); ?></td>
                                    <td><strong><?php echo formatMoney($p['amount']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $p['payment_method'] ?? ''))); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($p['payment_category'] ?? '')); ?></span></td>
                                    <td><?php echo getPaymentStatusBadge($p['status']); ?></td>
                                    <td><small class="text-muted"><?php echo timeAgo($p['created_at']); ?></small></td>
                                </tr>
                                <?php
                                    endforeach;
                                    if (empty($payments)):
                                ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No payments recorded yet</td></tr>
                                <?php
                                    endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="7" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
