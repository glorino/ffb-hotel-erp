<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['kitchen_chef']);

$page_title = 'Order History';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/kitchen-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? '';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Kitchen</a></li>
            <li class="breadcrumb-item active">Order History</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                        <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="order-history.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">Full Order History</h5>
            <div>
                <?php
                try {
                    $sql_count = "SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND DATE(created_at) BETWEEN ? AND ?";
                    $params_count = [$branch_id, $date_from, $date_to];
                    if (!empty($status_filter)) {
                        $sql_count .= " AND status = ?";
                        $params_count[] = $status_filter;
                    }
                    $stmt = $db->prepare($sql_count);
                    $stmt->execute($params_count);
                    echo '<span class="text-muted small">' . $stmt->fetchColumn() . ' orders found</span>';
                } catch (Exception $e) {}
                ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order Ref</th>
                            <th>Table</th>
                            <th>Waiter</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "
                                SELECT fo.*, u.full_name as waiter_name 
                                FROM food_orders fo 
                                LEFT JOIN users u ON fo.waiter_id = u.id 
                                WHERE fo.branch_id = ? AND DATE(fo.created_at) BETWEEN ? AND ?
                            ";
                            $params = [$branch_id, $date_from, $date_to];

                            if (!empty($status_filter)) {
                                $sql .= " AND fo.status = ?";
                                $params[] = $status_filter;
                            }
                            $sql .= " ORDER BY fo.created_at DESC LIMIT 200";

                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $orders = $stmt->fetchAll();

                            if (empty($orders)):
                        ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No orders found for the selected filters.</td>
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
                                $item_list = array_map(function($i) { return $i['quantity'] . 'x ' . $i['name']; }, $items);

                                $status_badges = [
                                    'pending' => 'warning', 'preparing' => 'info', 'ready' => 'success',
                                    'completed' => 'secondary', 'cancelled' => 'danger'
                                ];
                                $badge = $status_badges[$order['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['table_number'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($order['waiter_name'] ?? 'N/A'); ?></td>
                                <td><small><?php echo htmlspecialchars(implode(', ', $item_list)); ?></small></td>
                                <td><?php echo formatMoney($order['payable_amount'] ?? $order['total_amount']); ?></td>
                                <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $order['status']; ?></span></td>
                                <td><?php echo getPaymentStatusBadge($order['payment_status']); ?></td>
                                <td><small class="text-muted"><?php echo formatDateTime($order['created_at']); ?></small></td>
                                <td><small class="text-muted"><?php echo $order['updated_at'] ? formatDateTime($order['updated_at']) : '-'; ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        <?php } catch (Exception $e) {
                            error_log('Order history error: ' . $e->getMessage());
                            echo '<tr><td colspan="9" class="text-center py-4 text-danger">Error loading order history.</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
