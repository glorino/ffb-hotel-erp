<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['waiter']);

$page_title = 'Waiter Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/waiter-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Waiter Dashboard</li>
        </ol>
    </nav>

    <?php
    $stats = [];
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM restaurant_tables WHERE branch_id = ? AND status = 'occupied'");
        $stmt->execute([$branch_id]);
        $stats['active_tables'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND waiter_id = ? AND status NOT IN ('completed', 'cancelled')");
        $stmt->execute([$branch_id, $user_id]);
        $stats['active_orders'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND waiter_id = ? AND payment_status = 'pending' AND status != 'cancelled'");
        $stmt->execute([$branch_id, $user_id]);
        $stats['pending_bills'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(DISTINCT fo.id) FROM food_orders fo WHERE fo.branch_id = ? AND fo.waiter_id = ? AND DATE(fo.created_at) = CURRENT_DATE");
        $stmt->execute([$branch_id, $user_id]);
        $stats['today_orders'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE branch_id = ? AND status = 'ready'");
        $stmt->execute([$branch_id]);
        $stats['ready_orders'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM customer_requests WHERE branch_id = ? AND status = 'pending'");
        $stmt->execute([$branch_id]);
        $stats['pending_requests'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Waiter dashboard stats error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Active Tables</p>
                            <h3 class="stat-value mb-0 text-primary"><?php echo $stats['active_tables'] ?? 0; ?></h3>
                            <small class="text-muted">Occupied now</small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-2"><i class="bi bi-table text-primary"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">My Active Orders</p>
                            <h3 class="stat-value mb-0 text-warning"><?php echo $stats['active_orders'] ?? 0; ?></h3>
                            <small class="text-muted">In progress</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-2"><i class="bi bi-receipt text-warning"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Pending Bills</p>
                            <h3 class="stat-value mb-0 text-info"><?php echo $stats['pending_bills'] ?? 0; ?></h3>
                            <small class="text-muted">Awaiting payment</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-2"><i class="bi bi-credit-card text-info"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Today's Orders</p>
                            <h3 class="stat-value mb-0 text-success"><?php echo $stats['today_orders'] ?? 0; ?></h3>
                            <small class="text-muted">Orders placed</small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-2"><i class="bi bi-clipboard-check text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Ready Orders</p>
                            <h3 class="stat-value mb-0 text-danger"><?php echo $stats['ready_orders'] ?? 0; ?></h3>
                            <small class="text-muted">To collect</small>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-2"><i class="bi bi-bell text-danger"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1 small">Requests</p>
                            <h3 class="stat-value mb-0 text-secondary"><?php echo $stats['pending_requests'] ?? 0; ?></h3>
                            <small class="text-muted">From customers</small>
                        </div>
                        <div class="stat-icon bg-secondary-subtle rounded-3 p-2"><i class="bi bi-chat-dots text-secondary"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3 col-6">
                            <a href="new-order.php" class="btn btn-lg btn-outline-primary w-100 py-3">
                                <i class="bi bi-plus-circle fs-4 d-block mb-1"></i>
                                <span>New Order</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="tables.php" class="btn btn-lg btn-outline-success w-100 py-3">
                                <i class="bi bi-grid-3x3-gap fs-4 d-block mb-1"></i>
                                <span>View Tables</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="bills.php" class="btn btn-lg btn-outline-warning w-100 py-3">
                                <i class="bi bi-file-earmark-text fs-4 d-block mb-1"></i>
                                <span>Check Bills</span>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="kitchen-status.php" class="btn btn-lg btn-outline-info w-100 py-3">
                                <i class="bi bi-fire fs-4 d-block mb-1"></i>
                                <span>Kitchen Status</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">My Recent Orders</h5>
                    <a href="active-orders.php" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Order Ref</th><th>Table</th><th>Items</th><th>Status</th><th>Time</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("
                                        SELECT fo.* FROM food_orders fo 
                                        WHERE fo.branch_id = ? AND fo.waiter_id = ? 
                                        ORDER BY fo.created_at DESC LIMIT 10
                                    ");
                                    $stmt->execute([$branch_id, $user_id]);
                                    while ($order = $stmt->fetch()):
                                        $item_stmt = $db->prepare("
                                            SELECT foi.quantity, fi.name 
                                            FROM food_order_items foi 
                                            JOIN food_items fi ON foi.food_item_id = fi.id 
                                            WHERE foi.order_id = ?
                                        ");
                                        $item_stmt->execute([$order['id']]);
                                        $items = $item_stmt->fetchAll();
                                        $item_str = implode(', ', array_map(function($i) { return $i['quantity'] . 'x ' . $i['name']; }, $items));

                                        $badge_map = ['pending'=>'warning','preparing'=>'info','ready'=>'success','completed'=>'secondary','cancelled'=>'danger'];
                                        $badge = $badge_map[$order['status']] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['order_reference']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['table_number'] ?? '-'); ?></td>
                                        <td><small><?php echo htmlspecialchars(truncate($item_str, 40)); ?></small></td>
                                        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $order['status']; ?></span></td>
                                        <td><small class="text-muted"><?php echo timeAgo($order['created_at']); ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php } catch (Exception $e) {
                            error_log('Recent orders error: ' . $e->getMessage());
                        } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Today's Activity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Time</th><th>Order</th><th>Table</th><th>Action</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("
                                        SELECT * FROM food_orders 
                                        WHERE branch_id = ? AND waiter_id = ? AND DATE(created_at) = CURRENT_DATE 
                                        ORDER BY created_at DESC LIMIT 10
                                    ");
                                    $stmt->execute([$branch_id, $user_id]);
                                    while ($order = $stmt->fetch()):
                                        $action = '';
                                        switch ($order['status']) {
                                            case 'pending': $action = 'Order placed'; break;
                                            case 'preparing': $action = 'Kitchen started'; break;
                                            case 'ready': $action = 'Ready to serve'; break;
                                            case 'completed': $action = 'Order completed'; break;
                                            case 'cancelled': $action = 'Order cancelled'; break;
                                        }
                                    ?>
                                    <tr>
                                        <td><small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($order['order_reference']); ?></small></td>
                                        <td><?php echo htmlspecialchars($order['table_number'] ?? '-'); ?></td>
                                        <td><small><?php echo $action; ?></small></td>
                                        <td><?php echo formatMoney($order['payable_amount'] ?? $order['total_amount']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php } catch (Exception $e) {
                            error_log('Today activity error: ' . $e->getMessage());
                        } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
