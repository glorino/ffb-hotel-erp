<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Check-out';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_checkout'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $extra_charges = (float)($_POST['extra_charges'] ?? 0);
    $extra_description = sanitize($_POST['extra_description'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $thank_you_sms = isset($_POST['thank_you_sms']) ? 1 : 0;

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? AND branch_id = ? AND booking_status = 'checked_in'");
        $stmt->execute([$booking_id, $branch_id]);
        $booking = $stmt->fetch();
        if (!$booking) throw new Exception('Booking not found or not checked in');

        $total_due = (float)($booking['total_amount'] ?? 0) + $extra_charges;

        $stmt = $db->prepare("UPDATE bookings SET booking_status = 'checked_out', check_out_time = NOW(), extra_charges = ?, extra_description = ?, final_amount = ? WHERE id = ?");
        $stmt->execute([$extra_charges, $extra_description, $total_due, $booking_id]);

        if ($booking['room_id']) {
            $stmt = $db->prepare("UPDATE rooms SET status = 'cleaning' WHERE id = ?");
            $stmt->execute([$booking['room_id']]);
        }

        if ($amount_paid > 0 && $payment_method) {
            $pay_ref = generateReference('CO');
            $stmt = $db->prepare("INSERT INTO payments (branch_id, booking_id, customer_id, reference, amount, payment_method, payment_category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'room', 'paid', NOW())");
            $stmt->execute([$branch_id, $booking_id, $booking['customer_id'], $pay_ref, $amount_paid, $payment_method]);
        }

        $receipt_no = generateReceiptNumber();
        $stmt = $db->prepare("INSERT INTO receipts (booking_id, receipt_number, amount, generated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$booking_id, $receipt_no, $total_due]);

        log_audit('check_out', 'booking', $booking_id, null, ['final_amount' => $total_due, 'extra_charges' => $extra_charges]);
        $db->commit();

        $guest_name = trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''));
        if (empty($guest_name)) $guest_name = 'valued guest';
        set_flash('success', "Thank you for staying with us, {$guest_name}! It was a pleasure hosting you. We look forward to welcoming you back to FFB Hotel whenever you're in town. Safe travels!");
        header("Location: receipts.php?ref={$receipt_no}");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        set_flash('danger', 'Check-out failed: ' . $e->getMessage());
    }
    header('Location: check-out.php');
    exit;
}

$booking_id = $_GET['booking_id'] ?? 0;
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Check-out</li>
        </ol>
    </nav>

    <?php if ($booking_id): ?>
    <?php
    try {
        $stmt = $db->prepare("
            SELECT b.*, c.first_name, c.last_name, c.email, c.phone, rm.room_number, rt.name as room_type
            FROM bookings b
            LEFT JOIN customers c ON b.customer_id = c.id
            LEFT JOIN rooms rm ON b.room_id = rm.id
            LEFT JOIN room_types rt ON rm.room_type_id = rt.id
            WHERE b.id = ? AND b.branch_id = ? AND b.booking_status = 'checked_in'
        ");
        $stmt->execute([$booking_id, $branch_id]);
        $booking = $stmt->fetch();
        if (!$booking) { throw new Exception('Checked-in booking not found'); }

        $extra_charges = (float)($_POST['extra_charges'] ?? 0);
        $base_total = (float)($booking['total_amount'] ?? 0);
        $total_due = $base_total + $extra_charges;
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Check-out: <?php echo htmlspecialchars($booking['reference']); ?></h5>
        </div>
        <div class="card-body">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Guest</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '')); ?></strong></p>
                        <p class="mb-1">Email: <?php echo htmlspecialchars($booking['email'] ?? 'N/A'); ?></p>
                        <p class="mb-0">Phone: <?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Stay Summary</h6>
                        <p class="mb-1">Room: <?php echo htmlspecialchars($booking['room_number'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($booking['room_type'] ?? 'N/A'); ?>)</p>
                        <p class="mb-1">Check In: <?php echo formatDate($booking['check_in_date']); ?></p>
                        <p class="mb-1">Check Out: <?php echo formatDate($booking['check_out_date']); ?></p>
                        <p class="mb-0">Nights: <?php echo calculateNights($booking['check_in_date'], $booking['check_out_date']); ?></p>
                    </div>
                </div>
            </div>

            <form method="POST" class="row g-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">

                <div class="col-12">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Bill Summary</h6>
                        <div class="d-flex justify-content-between"><span>Room Charges</span><span><?php echo formatMoney($base_total); ?></span></div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label small">Extra Charges</label>
                                <input type="number" name="extra_charges" id="extraCharges" class="form-control" step="0.01" min="0" value="0" onchange="updateTotal()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Description</label>
                                <input type="text" name="extra_description" class="form-control" placeholder="Mini bar, damage, etc.">
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total Due</span>
                            <span id="totalDue"><?php echo formatMoney($total_due); ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">Select method</option>
                        <option value="cash">Cash</option>
                        <option value="pos">POS Terminal</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="flutterwave">Flutterwave</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Amount Paid</label>
                    <input type="number" name="amount_paid" class="form-control" step="0.01" min="0" value="<?php echo $total_due; ?>" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="thank_you_sms" id="thankYouSms" value="1" checked>
                        <label class="form-check-label" for="thankYouSms">Send Thank You SMS</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" name="do_checkout" class="btn btn-warning btn-lg px-5" onclick="return confirm('Finalize check-out for this guest?')">
                        <i class="bi bi-box-arrow-right me-2"></i>Confirm Check-out
                    </button>
                    <a href="check-out.php" class="btn btn-outline-secondary btn-lg">Back</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function updateTotal() {
        const base = <?php echo $base_total; ?>;
        const extra = parseFloat(document.getElementById('extraCharges').value) || 0;
        const total = base + extra;
        document.getElementById('totalDue').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    </script>

    <?php
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <?php else: ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Checked-in Guests</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->prepare("
                                SELECT b.*, c.first_name, c.last_name, rm.room_number
                                FROM bookings b
                                LEFT JOIN customers c ON b.customer_id = c.id
                                LEFT JOIN rooms rm ON b.room_id = rm.id
                                WHERE b.branch_id = ? AND b.booking_status = 'checked_in'
                                ORDER BY b.check_out_date ASC
                            ");
                            $stmt->execute([$branch_id]);
                            $bookings = $stmt->fetchAll();
                            foreach ($bookings as $b):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? 'Guest')); ?></td>
                            <td><?php echo htmlspecialchars($b['room_number'] ?? '—'); ?></td>
                            <td><?php echo formatDate($b['check_in_date']); ?></td>
                            <td><?php echo formatDate($b['check_out_date']); ?></td>
                            <td><?php echo formatMoney($b['total_amount'] ?? 0); ?></td>
                            <td>
                                <a href="?booking_id=<?php echo $b['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-box-arrow-right"></i> Check-out
                                </a>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($bookings)):
                        ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No checked-in guests at the moment</td></tr>
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

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
