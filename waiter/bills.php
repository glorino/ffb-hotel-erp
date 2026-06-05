<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['waiter']);

$page_title = 'Bills';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/waiter-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

$selected_order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$selected_table = $_GET['table'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_discount'])) {
    $order_id = (int)$_POST['order_id'];
    $discount_type = $_POST['discount_type'] ?? 'fixed';
    $discount_val = (float)($_POST['discount_value'] ?? 0);

    try {
        $stmt = $db->prepare("SELECT total_amount FROM food_orders WHERE id = ? AND branch_id = ? AND waiter_id = ?");
        $stmt->execute([$order_id, $branch_id, $user_id]);
        $order = $stmt->fetch();

        if ($order) {
            $discount = $discount_type === 'percentage'
                ? $order['total_amount'] * ($discount_val / 100)
                : min($discount_val, $order['total_amount']);

            $payable = $order['total_amount'] - $discount;
            $stmt = $db->prepare("UPDATE food_orders SET discount_amount = ?, payable_amount = ? WHERE id = ?");
            $stmt->execute([$discount, $payable, $order_id]);
            set_flash('success', 'Discount applied successfully.');
        }
    } catch (Exception $e) {
        error_log('Apply discount error: ' . $e->getMessage());
        set_flash('danger', 'Failed to apply discount.');
    }
    header('Location: bills.php?order=' . $order_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_ready'])) {
    $order_id = (int)$_POST['order_id'];
    try {
        $stmt = $db->prepare("UPDATE food_orders SET notes = CONCAT(COALESCE(notes, ''), ' | Bill ready for payment') WHERE id = ? AND branch_id = ? AND waiter_id = ?");
        $stmt->execute([$order_id, $branch_id, $user_id]);
        set_flash('success', 'Bill marked as ready for payment.');
    } catch (Exception $e) {
        set_flash('danger', 'Failed to update bill.');
    }
    header('Location: bills.php?order=' . $order_id);
    exit;
}

$recent_orders = [];
try {
    $stmt = $db->prepare("SELECT id, order_reference, table_number, status, total_amount, payable_amount, payment_status FROM food_orders WHERE branch_id = ? AND waiter_id = ? AND status != 'cancelled' ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$branch_id, $user_id]);
    $recent_orders = $stmt->fetchAll();
} catch (Exception $e) {}

$selected_order = null;
$order_items = [];
if ($selected_order_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM food_orders WHERE id = ? AND branch_id = ?");
        $stmt->execute([$selected_order_id, $branch_id]);
        $selected_order = $stmt->fetch();

        if ($selected_order) {
            $stmt = $db->prepare("
                SELECT foi.*, fi.name 
                FROM food_order_items foi 
                JOIN food_items fi ON foi.food_item_id = fi.id 
                WHERE foi.order_id = ?
            ");
            $stmt->execute([$selected_order_id]);
            $order_items = $stmt->fetchAll();
        }
    } catch (Exception $e) {}
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Bills</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Select Order</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_orders as $ro): ?>
                        <a href="?order=<?php echo $ro['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $selected_order_id === (int)$ro['id'] ? 'active' : ''; ?>">
                            <div>
                                <strong><?php echo htmlspecialchars($ro['order_reference']); ?></strong>
                                <small class="d-block text-muted">Table <?php echo htmlspecialchars($ro['table_number'] ?? 'N/A'); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="d-block"><?php echo formatMoney($ro['payable_amount'] ?? $ro['total_amount']); ?></span>
                                <small><?php echo getPaymentStatusBadge($ro['payment_status']); ?></small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if ($selected_order): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Bill Summary</h5>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($selected_order['order_reference']); ?></span>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Order Type</small>
                            <strong><?php echo ucfirst(str_replace('_', ' ', $selected_order['order_type'])); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Table</small>
                            <strong><?php echo htmlspecialchars($selected_order['table_number'] ?? 'N/A'); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Status</small>
                            <strong><?php echo $selected_order['status']; ?></strong>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $i => $item): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo (int)$item['quantity']; ?></td>
                                    <td><?php echo formatMoney($item['unit_price']); ?></td>
                                    <td><?php echo formatMoney($item['total_price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Subtotal</td>
                                    <td><?php echo formatMoney($selected_order['total_amount']); ?></td>
                                </tr>
                                <?php if ($selected_order['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-success">Discount</td>
                                    <td class="text-success">-<?php echo formatMoney($selected_order['discount_amount']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="table-active">
                                    <td colspan="4" class="text-end fw-bold fs-5">Payable Amount</td>
                                    <td class="fw-bold fs-5"><?php echo formatMoney($selected_order['payable_amount']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-3">Apply Discount</h6>
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $selected_order['id']; ?>">
                                        <div class="mb-2">
                                            <select name="discount_type" class="form-select form-select-sm">
                                                <option value="fixed">Fixed Amount (<?php echo CURRENCY_SYMBOL; ?>)</option>
                                                <option value="percentage">Percentage (%)</option>
                                            </select>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <input type="number" name="discount_value" class="form-control" placeholder="Amount" step="0.01" min="0">
                                            <button type="submit" name="apply_discount" class="btn btn-success">Apply</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-3">Bill Actions</h6>
                                    <div class="d-grid gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $selected_order['id']; ?>">
                                            <button type="submit" name="mark_ready" class="btn btn-info w-100 btn-sm mb-2">
                                                <i class="bi bi-check-lg"></i> Mark Ready for Payment
                                            </button>
                                        </form>
                                        <a href="payments.php?order=<?php echo $selected_order['id']; ?>" class="btn btn-primary w-100 btn-sm">
                                            <i class="bi bi-credit-card"></i> Process Payment
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary w-100 btn-sm" onclick="window.print();">
                                            <i class="bi bi-printer"></i> Print Bill
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif (!empty($selected_table)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                    <p class="text-muted mt-2">Select an order from the list to view its bill.</p>
                </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                <p class="text-muted mt-2">Select an order from the list to generate a bill.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
