<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['waiter']);

$page_title = 'Kitchen Status';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (isset($_GET['collect']) && is_numeric($_GET['collect'])) {
    $order_id = (int)$_GET['collect'];
    try {
        $stmt = $db->prepare("UPDATE food_orders SET status = 'completed', updated_at = NOW() WHERE id = ? AND waiter_id = ? AND branch_id = ? AND status = 'ready'");
        $stmt->execute([$order_id, $user_id, $branch_id]);
        if ($stmt->rowCount()) {
            set_flash('success', 'Order collected and marked as completed.');
        }
    } catch (Exception $e) {
        error_log('Collect order error: ' . $e->getMessage());
        set_flash('danger', 'Failed to collect order.');
    }
    header('Location: kitchen-status.php');
    exit;
}
?>

<meta http-equiv="refresh" content="15">

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Kitchen Status</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-semibold">Orders in Kitchen</h4>
        <div>
            <a href="?filter=my" class="btn btn-sm <?php echo (!isset($_GET['filter']) || $_GET['filter'] === 'my') ? 'btn-dark' : 'btn-outline-secondary'; ?>">My Orders</a>
            <a href="?filter=all" class="btn btn-sm <?php echo ($_GET['filter'] ?? '') === 'all' ? 'btn-dark' : 'btn-outline-secondary'; ?>">All Orders</a>
        </div>
    </div>

    <div class="row g-3">
        <?php
        try {
            $filter = $_GET['filter'] ?? 'my';
            $sql = "SELECT fo.*, u.full_name as waiter_name,
                           EXTRACT(EPOCH FROM (NOW() - fo.created_at)) / 60 as elapsed
                    FROM food_orders fo 
                    LEFT JOIN users u ON fo.waiter_id = u.id 
                    WHERE fo.branch_id = ? AND fo.status IN ('pending', 'preparing', 'ready')";
            $params = [$branch_id];

            if ($filter === 'my') {
                $sql .= " AND fo.waiter_id = ?";
                $params[] = $user_id;
            }
            $sql .= " ORDER BY fo.status = 'pending' DESC, fo.status = 'ready' DESC, fo.updated_at ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();

            if (empty($orders)):
        ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-fire fs-1 text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No orders in the kitchen right now.</p>
                </div>
            </div>
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

                $badge_map = ['pending'=>'warning', 'preparing'=>'info', 'ready'=>'success'];
                $badge = $badge_map[$order['status']] ?? 'secondary';
                $border_class = $order['status'] === 'ready' ? 'border-success' : ($order['status'] === 'preparing' ? 'border-info' : 'border-warning');
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 <?php echo $border_class; ?>" style="border-left: 4px solid var(--bs-<?php echo $order['status'] === 'ready' ? 'success' : ($order['status'] === 'preparing' ? 'info' : 'warning'); ?>);">
                    <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold <?php echo $order['status'] === 'ready' ? 'text-success' : ''; ?>">
                                <?php echo htmlspecialchars($order['order_reference']); ?>
                                <?php if ($order['status'] === 'ready'): ?>
                                    <i class="bi bi-bell-fill text-success ms-1"></i>
                                <?php endif; ?>
                            </h6>
                            <small class="text-muted">
                                Table <?php echo htmlspecialchars($order['table_number'] ?? 'N/A'); ?> 
                                | <?php echo htmlspecialchars($order['waiter_name'] ?? 'N/A'); ?>
                            </small>
                        </div>
                        <span class="badge bg-<?php echo $badge; ?> fs-6"><?php echo $order['status']; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Items:</small>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($items as $item): ?>
                                <li><small><span class="badge bg-secondary me-1"><?php echo (int)$item['quantity']; ?></span> <?php echo htmlspecialchars($item['name']); ?></small></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> 
                                <?php echo $order['elapsed'] < 60 ? $order['elapsed'] . ' min' : floor($order['elapsed'] / 60) . 'h ' . ($order['elapsed'] % 60) . 'm'; ?>
                            </small>
                            <?php if ($order['status'] === 'ready'): ?>
                                <a href="?collect=<?php echo $order['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="bi bi-hand-index-thumb"></i> Collect Order
                                </a>
                            <?php elseif ($order['status'] === 'pending'): ?>
                                <span class="badge bg-warning">Waiting for kitchen</span>
                            <?php else: ?>
                                <span class="badge bg-info">Preparing...</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php } catch (Exception $e) {
            error_log('Kitchen status error: ' . $e->getMessage());
            echo '<div class="col-12"><div class="alert alert-danger">Error loading kitchen status.</div></div>';
        } ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
