<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'My Orders';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$customer_id = $_SESSION['customer_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
if (!$customer_id) {
    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer_id = $stmt->fetchColumn();
    if ($customer_id) $_SESSION['customer_id'] = $customer_id;
}

$status_filter = $_GET['status'] ?? '';
$where = "fo.customer_id = ?"; $params = [$customer_id];
if ($status_filter) { $where .= " AND fo.status = ?"; $params[] = $status_filter; }

$stmt = $db->prepare("SELECT fo.*, br.name as branch_name FROM food_orders fo LEFT JOIN branches br ON fo.branch_id = br.id WHERE $where ORDER BY fo.created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">My Orders</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <a href="?status=" class="btn btn-sm <?php echo !$status_filter ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                    <a href="?status=pending" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Pending</a>
                    <a href="?status=preparing" class="btn btn-sm <?php echo $status_filter === 'preparing' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Preparing</a>
                    <a href="?status=ready" class="btn btn-sm <?php echo $status_filter === 'ready' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Ready</a>
                    <a href="?status=completed" class="btn btn-sm <?php echo $status_filter === 'completed' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Completed</a>
                </div>
                <a href="<?php echo $base_url; ?>order.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Order</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($orders as $o):
            $items = [];
            try {
                $st = $db->prepare("SELECT item_name, quantity, price FROM order_items WHERE food_order_id = ?");
                $st->execute([$o['id']]);
                $items = $st->fetchAll();
            } catch (Exception $e) {}
            $badge_map = ['pending'=>'warning','preparing'=>'info','ready'=>'success','completed'=>'secondary','cancelled'=>'danger'];
            $bc = $badge_map[$o['status']] ?? 'secondary';
        ?>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($o['reference'] ?? 'ORD-' . $o['id']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($o['branch_name'] ?? '—'); ?> &middot; <?php echo formatDateTime($o['created_at']); ?></small>
                        </div>
                        <span class="badge bg-<?php echo $bc; ?>"><?php echo htmlspecialchars(ucfirst($o['status'])); ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Items:</small>
                        <?php if ($items): ?>
                        <ul class="list-unstyled mb-0 small">
                            <?php foreach ($items as $item): ?>
                            <li><?php echo htmlspecialchars($item['item_name']); ?> x<?php echo $item['quantity']; ?> — <?php echo formatMoney($item['price'] * $item['quantity']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <small class="text-muted">Order items not available</small>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2">
                        <strong>Total: <?php echo formatMoney($o['total_amount']); ?></strong>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="alert('Order details: <?php echo htmlspecialchars($o['reference'] ?? 'ORD-' . $o['id']); ?>\nStatus: <?php echo $o['status']; ?>\nTotal: <?php echo formatMoney($o['total_amount']); ?>')"><i class="bi bi-info-circle"></i> Details</button>
                            <a href="<?php echo $base_url; ?>order.php?reorder=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Reorder</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; if (empty($orders)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-cart-x display-4 text-muted"></i>
                    <h5 class="mt-3">No Orders Found</h5>
                    <p class="text-muted">You haven't placed any food orders yet.</p>
                    <a href="<?php echo $base_url; ?>order.php" class="btn btn-success"><i class="bi bi-utensils"></i> Order Food</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
