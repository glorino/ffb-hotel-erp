<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['kitchen_chef']);

$page_title = 'Incoming Orders';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (isset($_GET['accept']) && is_numeric($_GET['accept'])) {
    $order_id = (int)$_GET['accept'];
    try {
        $stmt = $db->prepare("UPDATE food_orders SET status = 'preparing', updated_at = NOW() WHERE id = ? AND branch_id = ? AND status = 'pending'");
        $stmt->execute([$order_id, $branch_id]);
        if ($stmt->rowCount()) {
            set_flash('success', 'Order accepted and moved to preparing.');
        }
    } catch (Exception $e) {
        error_log('Accept order error: ' . $e->getMessage());
        set_flash('danger', 'Failed to accept order.');
    }
    header('Location: incoming-orders.php');
    exit;
}
?>

<meta http-equiv="refresh" content="15">

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Kitchen</a></li>
            <li class="breadcrumb-item active">Incoming Orders</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-semibold">Incoming Orders</h4>
        <span class="badge bg-warning fs-6" id="orderCount">
            <?php
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND status = 'pending'");
                $stmt->execute([$branch_id]);
                echo $stmt->fetchColumn() . ' waiting';
            } catch (Exception $e) {
                echo '0 waiting';
            }
            ?>
        </span>
    </div>

    <?php
    try {
        $stmt = $db->prepare("
            SELECT fo.*, u.full_name as waiter_name 
            FROM food_orders fo 
            LEFT JOIN users u ON fo.waiter_id = u.id 
            WHERE fo.branch_id = ? AND fo.status = 'pending' 
            ORDER BY fo.created_at ASC
        ");
        $stmt->execute([$branch_id]);
        $orders = $stmt->fetchAll();

        if (empty($orders)):
    ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-2 mb-0">No incoming orders at the moment.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($orders as $order):
                $item_stmt = $db->prepare("
                    SELECT foi.quantity, foi.unit_price, foi.total_price, foi.notes as item_note, fi.name, fi.image 
                    FROM food_order_items foi 
                    JOIN food_items fi ON foi.food_item_id = fi.id 
                    WHERE foi.order_id = ?
                ");
                $item_stmt->execute([$order['id']]);
                $items = $item_stmt->fetchAll();
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 order-card incoming">
                    <div class="card-header bg-warning bg-opacity-10 border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($order['order_reference']); ?></h6>
                            <small class="text-muted"><?php echo timeAgo($order['created_at']); ?></small>
                        </div>
                        <span class="badge bg-warning">Pending</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex gap-3 mb-2">
                                <div><i class="bi bi-table text-muted"></i> Table: <strong><?php echo htmlspecialchars($order['table_number'] ?? 'N/A'); ?></strong></div>
                                <div><i class="bi bi-person text-muted"></i> Waiter: <strong><?php echo htmlspecialchars($order['waiter_name'] ?? 'N/A'); ?></strong></div>
                            </div>
                            <div class="mb-2">
                                <i class="bi bi-tag text-muted"></i> Type: <strong><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></strong>
                            </div>
                        </div>
                        <div class="order-items mb-3">
                            <h6 class="text-muted small text-uppercase mb-2">Items</h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($items as $item): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span><span class="badge bg-secondary me-2"><?php echo (int)$item['quantity']; ?></span><?php echo htmlspecialchars($item['name']); ?></span>
                                    <small class="text-muted"><?php echo formatMoney($item['total_price']); ?></small>
                                </li>
                                <?php if (!empty($item['item_note'])): ?>
                                <li class="list-group-item px-0 pt-0 border-0">
                                    <small class="text-warning"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($item['item_note']); ?></small>
                                </li>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php if (!empty($order['notes'])): ?>
                        <div class="mb-3 p-2 bg-light rounded">
                            <small class="text-muted"><i class="bi bi-chat-quote"></i> <strong>Order Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></small>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <strong>Total: <?php echo formatMoney($order['payable_amount'] ?? $order['total_amount']); ?></strong>
                            <a href="?accept=<?php echo $order['id']; ?>" class="btn btn-success btn-sm">
                                <i class="bi bi-check-lg"></i> Start Preparing
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php } catch (Exception $e) {
        error_log('Incoming orders error: ' . $e->getMessage());
        echo '<div class="alert alert-danger">Unable to load incoming orders.</div>';
    } ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
