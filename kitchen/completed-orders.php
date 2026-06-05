<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['kitchen_chef']);

$page_title = 'Completed Orders';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/kitchen-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Kitchen</a></li>
            <li class="breadcrumb-item active">Completed Orders</li>
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
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="completed-orders.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">Completed Orders</h5>
            <span class="text-muted small">
                <?php
                try {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND status = 'completed' AND DATE(created_at) BETWEEN ? AND ?");
                    $stmt->execute([$branch_id, $date_from, $date_to]);
                    echo $stmt->fetchColumn() . ' orders';
                } catch (Exception $e) { echo '0 orders'; }
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
                            <th>Waiter</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Completed At</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->prepare("
                                SELECT fo.*, u.full_name as waiter_name 
                                FROM food_orders fo 
                                LEFT JOIN users u ON fo.waiter_id = u.id 
                                WHERE fo.branch_id = ? AND fo.status = 'completed' AND DATE(fo.updated_at) BETWEEN ? AND ?
                                ORDER BY fo.updated_at DESC
                                LIMIT 100
                            ");
                            $stmt->execute([$branch_id, $date_from, $date_to]);
                            $orders = $stmt->fetchAll();

                            if (empty($orders)):
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No completed orders found.</td>
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
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['table_number'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($order['waiter_name'] ?? 'N/A'); ?></td>
                                <td><small><?php echo htmlspecialchars(implode(', ', $item_list)); ?></small></td>
                                <td><?php echo formatMoney($order['payable_amount'] ?? $order['total_amount']); ?></td>
                                <td><small class="text-muted"><?php echo formatDateTime($order['updated_at']); ?></small></td>
                                <td><?php echo getPaymentStatusBadge($order['payment_status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        <?php } catch (Exception $e) {
                            error_log('Completed orders error: ' . $e->getMessage());
                            echo '<tr><td colspan="7" class="text-center py-4 text-danger">Error loading completed orders.</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
