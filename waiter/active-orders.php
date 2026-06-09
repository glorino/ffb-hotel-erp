<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['waiter']);

$page_title = 'My Active Orders';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$table_filter = $_GET['table'] ?? '';

if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $order_id = (int)$_GET['cancel'];
    try {
        $stmt = $db->prepare("UPDATE food_orders SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND waiter_id = ? AND branch_id = ? AND status = 'pending'");
        $stmt->execute([$order_id, $user_id, $branch_id]);
        if ($stmt->rowCount()) {
            set_flash('success', 'Order cancelled successfully.');
        } else {
            set_flash('danger', 'Order could not be cancelled. Only pending orders can be cancelled.');
        }
    } catch (Exception $e) {
        error_log('Cancel order error: ' . $e->getMessage());
        set_flash('danger', 'Failed to cancel order.');
    }
    header('Location: active-orders.php' . ($table_filter ? '?table=' . urlencode($table_filter) : ''));
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">My Active Orders</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="mb-0 fw-semibold">
            My Active Orders
            <?php if ($table_filter): ?>
                <small class="text-muted">- Table <?php echo htmlspecialchars($table_filter); ?></small>
            <?php endif; ?>
        </h4>
        <?php if ($table_filter): ?>
            <a href="active-orders.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear Filter</a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">Orders</h5>
            <span class="text-muted small">
                <?php
                try {
                    $count_sql = "SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND waiter_id = ? AND status NOT IN ('completed', 'cancelled')";
                    $count_params = [$branch_id, $user_id];
                    if (!empty($table_filter)) {
                        $count_sql .= " AND table_number = ?";
                        $count_params[] = $table_filter;
                    }
                    $stmt = $db->prepare($count_sql);
                    $stmt->execute($count_params);
                    echo $stmt->fetchColumn() . ' active';
                } catch (Exception $e) {}
                ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order Ref</th>
                            <th>Table</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT * FROM food_orders WHERE branch_id = ? AND waiter_id = ? AND status NOT IN ('completed', 'cancelled')";
                            $params = [$branch_id, $user_id];
                            if (!empty($table_filter)) {
                                $sql .= " AND table_number = ?";
                                $params[] = $table_filter;
                            }
                            $sql .= " ORDER BY CASE status WHEN 'pending' THEN 0 WHEN 'preparing' THEN 1 WHEN 'ready' THEN 2 ELSE 3 END, created_at DESC";

                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $orders = $stmt->fetchAll();

                            if (empty($orders)):
                        ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No active orders.</td>
                        </tr>
                            <?php else: ?>
                            <?php foreach ($orders as $order):
                                $item_stmt = $db->prepare("
                                    SELECT foi.quantity, fi.name 
                                    FROM food_order_items foi 
                                    JOIN food_items fi ON foi.food_item_id = fi.id 
                                    WHERE foi.order_id = ?
                                ");
                                $item_stmt->execute([$order['id']]);
                                $items = $item_stmt->fetchAll();
                                $item_str = implode(', ', array_map(function($i) { return $i['quantity'] . 'x ' . $i['name']; }, $items));

                                $badge_map = ['pending'=>'warning', 'preparing'=>'info', 'ready'=>'success', 'completed'=>'secondary', 'cancelled'=>'danger'];
                                $badge = $badge_map[$order['status']] ?? 'secondary';
                            ?>
                            <tr class="<?php echo $order['status'] === 'ready' ? 'table-success' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($order['order_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['table_number'] ?? '-'); ?></td>
                                <td><small><?php echo htmlspecialchars(truncate($item_str, 50)); ?></small></td>
                                <td><?php echo formatMoney($order['payable_amount'] ?? $order['total_amount']); ?></td>
                                <td><?php echo getPaymentStatusBadge($order['payment_status']); ?></td>
                                <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $order['status']; ?></span></td>
                                <td><small class="text-muted"><?php echo timeAgo($order['created_at']); ?></small></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-gear"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php if ($order['status'] === 'ready'): ?>
                                                <li><a class="dropdown-item text-success" href="kitchen-status.php?collect=<?php echo $order['id']; ?>"><i class="bi bi-check-circle"></i> Collect Order</a></li>
                                            <?php endif; ?>
                                            <li><a class="dropdown-item" href="bills.php?order=<?php echo $order['id']; ?>"><i class="bi bi-file-earmark-text"></i> View Bill</a></li>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="?cancel=<?php echo $order['id']; ?>" onclick="return confirm('Cancel this order?')"><i class="bi bi-x-circle"></i> Cancel Order</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        <?php } catch (Exception $e) {
                            error_log('Active orders error: ' . $e->getMessage());
                            echo '<tr><td colspan="8" class="text-center py-4 text-danger">Error loading orders.</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
setTimeout(function() { location.reload(); }, 30000);
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
