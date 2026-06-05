<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['waiter']);

$page_title = 'Payments';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/waiter-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $order_id = (int)$_POST['order_id'];
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $method = $_POST['payment_method'] ?? 'cash';
    $notes = trim($_POST['payment_notes'] ?? '');

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM food_orders WHERE id = ? AND branch_id = ?");
        $stmt->execute([$order_id, $branch_id]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception('Order not found.');
        }

        if ($order['payment_status'] === 'paid') {
            throw new Exception('Order is already fully paid.');
        }

        $payable = (float)$order['payable_amount'];
        $already_paid = 0;
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = ? AND status = 'paid'");
        $stmt->execute([$order_id]);
        $already_paid = (float)$stmt->fetchColumn();
        $remaining = $payable - $already_paid;

        $pay_ref = 'PAY-' . strtoupper(uniqid());
        $receipt_no = generateReceiptNumber();

        if ($method === 'split_payment') {
            $methods = $_POST['split_methods'] ?? [];
            $amounts = $_POST['split_amounts'] ?? [];
            $total_split = 0;

            foreach ($methods as $i => $m) {
                $amt = (float)($amounts[$i] ?? 0);
                if ($amt <= 0) continue;
                $total_split += $amt;

                $stmt = $db->prepare("INSERT INTO payments (payment_reference, order_id, amount, method, status, channel, receipt_number, notes, recorded_by, created_at) VALUES (?, ?, ?, ?, 'paid', 'offline', ?, ?, ?, NOW())");
                $sub_ref = $pay_ref . '-' . ($i + 1);
                $stmt->execute([$sub_ref, $order_id, $amt, $m, $receipt_no, $notes, $user_id]);
            }

            if ($total_split <= 0) throw new Exception('Split payment amounts must be greater than zero.');

            $stmt = $db->prepare("UPDATE food_orders SET payment_status = CASE WHEN ? >= ? THEN 'paid' ELSE 'partially_paid' END, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$total_split, $remaining, $order_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO payments (payment_reference, order_id, amount, method, status, channel, receipt_number, notes, recorded_by, created_at) VALUES (?, ?, ?, ?, 'paid', 'offline', ?, ?, ?, NOW())");
            $stmt->execute([$pay_ref, $order_id, $amount_paid, $method, $receipt_no, $notes, $user_id]);

            $new_total_paid = $already_paid + $amount_paid;
            $new_status = $new_total_paid >= $remaining ? 'paid' : 'partially_paid';
            $stmt = $db->prepare("UPDATE food_orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
        }

        if (!empty($order['table_number'])) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND table_number = ? AND status NOT IN ('completed', 'cancelled') AND payment_status != 'paid'");
                $stmt->execute([$branch_id, $order['table_number']]);
                $has_unpaid = $stmt->fetchColumn();
                if ($has_unpaid == 0) {
                    $stmt = $db->prepare("UPDATE restaurant_tables SET status = 'available' WHERE branch_id = ? AND table_number = ?");
                    $stmt->execute([$branch_id, $order['table_number']]);
                }
            } catch (Exception $e) {}
        }

        $db->commit();
        set_flash('success', 'Payment processed successfully. Receipt: ' . $receipt_no);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Payment error: ' . $e->getMessage());
        set_flash('danger', 'Payment failed: ' . $e->getMessage());
    }
    header('Location: payments.php?order=' . $order_id);
    exit;
}

$order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$selected_order = null;
$order_payments = [];
$remaining = 0;

if ($order_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM food_orders WHERE id = ? AND branch_id = ?");
        $stmt->execute([$order_id, $branch_id]);
        $selected_order = $stmt->fetch();

        if ($selected_order) {
            $stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? AND status = 'paid' ORDER BY created_at");
            $stmt->execute([$order_id]);
            $order_payments = $stmt->fetchAll();

            $total_paid = array_sum(array_column($order_payments, 'amount'));
            $remaining = (float)$selected_order['payable_amount'] - $total_paid;
        }
    } catch (Exception $e) {}
}

$unpaid_orders = [];
try {
    $stmt = $db->prepare("SELECT id, order_reference, table_number, payable_amount, payment_status FROM food_orders WHERE branch_id = ? AND waiter_id = ? AND payment_status IN ('pending', 'partially_paid') AND status != 'cancelled' ORDER BY created_at DESC");
    $stmt->execute([$branch_id, $user_id]);
    $unpaid_orders = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Payments</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Unpaid Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($unpaid_orders as $uo): ?>
                        <a href="?order=<?php echo $uo['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $order_id === (int)$uo['id'] ? 'active' : ''; ?>">
                            <div>
                                <strong><?php echo htmlspecialchars($uo['order_reference']); ?></strong>
                                <small class="d-block text-muted">Table <?php echo htmlspecialchars($uo['table_number'] ?? 'N/A'); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="d-block"><?php echo formatMoney($uo['payable_amount']); ?></span>
                                <small><?php echo getPaymentStatusBadge($uo['payment_status']); ?></small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php if (empty($unpaid_orders)): ?>
                        <div class="list-group-item text-center text-muted py-4">
                            No unpaid orders.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($selected_order && !empty($order_payments)): ?>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Payment History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Ref</th><th>Method</th><th>Amount</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_payments as $p): ?>
                                <tr>
                                    <td><small><?php echo htmlspecialchars($p['payment_reference']); ?></small></td>
                                    <td><?php echo ucwords(str_replace('_', ' ', $p['method'])); ?></td>
                                    <td><?php echo formatMoney($p['amount']); ?></td>
                                    <td><small class="text-muted"><?php echo formatDateTime($p['created_at']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <?php if ($selected_order): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Process Payment</h5>
                    <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($selected_order['order_reference']); ?></span>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Table</small>
                            <strong><?php echo htmlspecialchars($selected_order['table_number'] ?? 'N/A'); ?></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Payable Amount</small>
                            <strong class="fs-5"><?php echo formatMoney($selected_order['payable_amount']); ?></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Already Paid</small>
                            <strong class="text-success"><?php echo formatMoney($selected_order['payable_amount'] - $remaining); ?></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Remaining</small>
                            <strong class="text-danger fs-5"><?php echo formatMoney($remaining); ?></strong>
                        </div>
                    </div>

                    <?php if ($remaining > 0): ?>
                    <form method="POST" id="paymentForm">
                        <input type="hidden" name="order_id" value="<?php echo $selected_order['id']; ?>">
                        <input type="hidden" name="amount_paid" id="amountPaid" value="<?php echo $remaining; ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Payment Method</label>
                            <div class="d-flex flex-wrap gap-2" id="paymentMethods">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_method" value="cash" id="pmCash" checked>
                                    <label class="form-check-label" for="pmCash"><i class="bi bi-cash"></i> Cash</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_method" value="pos" id="pmPos">
                                    <label class="form-check-label" for="pmPos"><i class="bi bi-credit-card"></i> POS</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_method" value="bank_transfer" id="pmBank">
                                    <label class="form-check-label" for="pmBank"><i class="bi bi-bank"></i> Bank Transfer</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_method" value="split_payment" id="pmSplit">
                                    <label class="form-check-label" for="pmSplit"><i class="bi bi-pie-chart"></i> Split Payment</label>
                                </div>
                            </div>
                        </div>

                        <div id="singlePaymentSection">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Amount to Collect</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                                    <input type="number" name="single_amount" class="form-control" id="singleAmount" value="<?php echo $remaining; ?>" step="0.01" min="0.01" max="<?php echo $remaining; ?>">
                                </div>
                            </div>
                        </div>

                        <div id="splitPaymentSection" style="display:none;">
                            <div class="card border mb-3">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-3">Split Payment</h6>
                                    <p class="text-muted small">Total to split: <strong><?php echo formatMoney($remaining); ?></strong></p>
                                    <div id="splitRows">
                                        <div class="row g-2 mb-2 split-row">
                                            <div class="col-5">
                                                <select name="split_methods[]" class="form-select form-select-sm">
                                                    <option value="cash">Cash</option>
                                                    <option value="pos">POS</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                </select>
                                            </div>
                                            <div class="col-5">
                                                <input type="number" name="split_amounts[]" class="form-control form-control-sm split-amount" placeholder="Amount" step="0.01" min="0">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-split" disabled><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addSplitRow"><i class="bi bi-plus"></i> Add Split</button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Notes</label>
                            <textarea name="payment_notes" class="form-control" rows="2" placeholder="Payment notes..."></textarea>
                        </div>

                        <button type="submit" name="process_payment" class="btn btn-lg btn-success w-100" id="processPaymentBtn">
                            <i class="bi bi-check-circle"></i> Process Payment (<?php echo formatMoney($remaining); ?>)
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill"></i> This order is fully paid.
                        <hr>
                        <a href="payments.php" class="btn btn-sm btn-outline-success">Process Another Payment</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-credit-card fs-1 text-muted"></i>
                <p class="text-muted mt-2">Select an unpaid order to process payment.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>

<script>
$(document).ready(function() {
    $('#singleAmount').on('input', function() {
        let val = parseFloat($(this).val()) || 0;
        let maxVal = parseFloat('<?php echo $remaining; ?>');
        if (val > maxVal) {
            $(this).val(maxVal);
        }
        if (val > 0) {
            $('#amountPaid').val(val);
            $('#processPaymentBtn').html('<i class="bi bi-check-circle"></i> Process Payment (<?php echo CURRENCY_SYMBOL; ?>' + val.toFixed(2) + ')');
        }
    });

    $('input[name="payment_method"]').on('change', function() {
        if ($(this).val() === 'split_payment') {
            $('#singlePaymentSection').hide();
            $('#splitPaymentSection').show();
        } else {
            $('#singlePaymentSection').show();
            $('#splitPaymentSection').hide();
        }
    });

    $('#addSplitRow').on('click', function() {
        let row = $('.split-row:first').clone();
        row.find('input').val('');
        row.find('.remove-split').prop('disabled', false);
        $('#splitRows').append(row);
        updateSplitTotal();
    });

    $(document).on('click', '.remove-split:not([disabled])', function() {
        $(this).closest('.split-row').remove();
        updateSplitTotal();
    });

    $(document).on('input', '.split-amount', function() {
        updateSplitTotal();
    });

    function updateSplitTotal() {
        let total = 0;
        $('.split-amount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        let remaining = parseFloat('<?php echo $remaining; ?>');
        let btn = $('#processPaymentBtn');
        if (Math.abs(total - remaining) < 0.01) {
            btn.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Process Split Payment (<?php echo CURRENCY_SYMBOL; ?>' + total.toFixed(2) + ')');
        } else {
            btn.prop('disabled', true).html('Split total must equal <?php echo formatMoney($remaining); ?> (currently <?php echo CURRENCY_SYMBOL; ?>' + total.toFixed(2) + ')');
        }
    }
});
</script>
