<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Branch Orders';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$status_filter = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    try {
        $stmt = $db->prepare("UPDATE food_orders SET status = ? WHERE id = ? AND branch_id = ?");
        $stmt->execute([$new_status, $order_id, $branch_id]);
        log_audit('update_order_status', 'food_order', $order_id, null, ['status' => $new_status]);
        set_flash('success', 'Order status updated to ' . ucfirst($new_status));
    } catch (Exception $e) {
        set_flash('danger', 'Error updating order: ' . $e->getMessage());
    }
    header('Location: orders.php');
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Orders</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                        <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-auto">
                    <a href="orders.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Food Orders</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT fo.*, c.full_name
                                    FROM food_orders fo
                                    LEFT JOIN customers c ON fo.customer_id = c.id
                                    WHERE fo.branch_id = ?";
                            $params = [$branch_id];
                            if ($status_filter) {
                                $sql .= " AND fo.status = ?";
                                $params[] = $status_filter;
                            }
                            $sql .= " ORDER BY fo.created_at DESC LIMIT 100";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $orders = $stmt->fetchAll();
                            foreach ($orders as $o):
                                $items_stmt = $db->prepare("SELECT foi.*, fi.name FROM food_order_items foi JOIN food_items fi ON foi.food_item_id = fi.id WHERE foi.food_order_id = ?");
                                $items_stmt->execute([$o['id']]);
                                $items = $items_stmt->fetchAll();
                        ?>
                        <tr>
                            <td><?php echo $o['id']; ?></td>
                            <td><?php echo htmlspecialchars($o['full_name'] ?? 'Guest'); ?></td>
                            <td><?php echo htmlspecialchars($o['room_number'] ?? 'N/A'); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-link p-0" data-bs-toggle="popover" data-bs-placement="left" data-bs-html="true" title="Order Items" data-bs-content="
                                    <?php
                                    $item_list = '';
                                    foreach ($items as $it) {
                                        $item_list .= htmlspecialchars($it['name'] ?? 'Item') . ' x' . $it['quantity'] . ' - ' . formatMoney($it['total_price'] * $it['quantity']) . '<br>';
                                    }
                                    echo $item_list ?: 'No items';
                                    ?>
                                ">
                                    <?php echo count($items); ?> item(s)
                                </button>
                            </td>
                            <td><?php echo formatMoney($o['total_amount'] ?? 0); ?></td>
                            <td>
                                <?php
                                $status_badges = ['pending'=>'bg-warning','preparing'=>'bg-info','ready'=>'bg-success','completed'=>'bg-secondary','cancelled'=>'bg-danger'];
                                $badge_class = $status_badges[$o['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($o['status']); ?></span>
                            </td>
                            <td><small class="text-muted"><?php echo formatDateTime($o['created_at']); ?></small></td>
                            <td>
                                <?php if (!in_array($o['status'], ['completed','cancelled'])): ?>
                                <form method="POST" class="d-flex gap-1">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                    <select name="new_status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                        <option value="">Update</option>
                                        <?php if ($o['status'] === 'pending'): ?>
                                        <option value="preparing">Preparing</option>
                                        <?php endif; ?>
                                        <?php if ($o['status'] === 'preparing'): ?>
                                        <option value="ready">Ready</option>
                                        <?php endif; ?>
                                        <?php if ($o['status'] === 'ready'): ?>
                                        <option value="completed">Completed</option>
                                        <?php endif; ?>
                                        <option value="cancelled">Cancel</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                <?php else: ?>
                                <span class="text-muted small"><?php echo ucfirst($o['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($orders)):
                        ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No orders found</td></tr>
                        <?php
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="8" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(el) { return new bootstrap.Popover(el, { trigger: 'hover' }); });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
