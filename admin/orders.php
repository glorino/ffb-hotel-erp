<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Manage Orders';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();

if (isset($_GET['action']) && isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    $valid_actions = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
    if (in_array($action, $valid_actions)) {
        try {
            $stmt = $db->prepare("UPDATE food_orders SET status = ? WHERE id = ?");
            $stmt->execute([$action, $id]);
            echo '<div class="alert alert-success">Order status updated to ' . htmlspecialchars($action) . '.</div>';
        } catch (Exception $e) { echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>'; }
    }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Orders</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Food Orders</h4>
        <div class="btn-group btn-group-sm">
            <a href="orders.php" class="btn btn-outline-primary <?php echo !isset($_GET['status']) ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="btn btn-outline-warning <?php echo ($_GET['status'] ?? '') === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=preparing" class="btn btn-outline-info <?php echo ($_GET['status'] ?? '') === 'preparing' ? 'active' : ''; ?>">Preparing</a>
            <a href="?status=completed" class="btn btn-outline-success <?php echo ($_GET['status'] ?? '') === 'completed' ? 'active' : ''; ?>">Completed</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "
                                SELECT fo.*, c.full_name as customer_name, b.name as branch_name
                                FROM food_orders fo
                                LEFT JOIN customers c ON fo.customer_id = c.id
                                JOIN branches b ON fo.branch_id = b.id
                            ";
                            $params = [];
                            if (!empty($_GET['status'])) {
                                $sql .= " WHERE fo.status = ?";
                                $params[] = $_GET['status'];
                            }
                            $sql .= " ORDER BY fo.created_at DESC LIMIT 200";

                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $orders = $stmt->fetchAll();

                            foreach ($orders as $o):
                                $items_stmt = $db->prepare("
                                    SELECT foi.quantity, fi.name 
                                    FROM food_order_items foi 
                                    JOIN food_items fi ON foi.food_item_id = fi.id 
                                    WHERE foi.order_id = ?
                                ");
                                $items_stmt->execute([$o['id']]);
                                $items = $items_stmt->fetchAll();
                                $item_names = array_map(function($i) { return $i['quantity'] . 'x ' . $i['name']; }, $items);
                        ?>
                        <tr>
                            <td class="fw-medium"><small><?php echo htmlspecialchars($o['order_reference']); ?></small></td>
                            <td><?php echo htmlspecialchars($o['customer_name'] ?? 'Walk-in'); ?></td>
                            <td><?php echo htmlspecialchars($o['branch_name']); ?></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($o['order_type']); ?></span></td>
                            <td><small><?php echo htmlspecialchars(implode(', ', $item_names)); ?></small></td>
                            <td class="fw-semibold"><?php echo formatMoney($o['payable_amount']); ?></td>
                            <td>
                                <?php
                                $status_badges = ['pending'=>'warning','preparing'=>'info','ready'=>'primary','completed'=>'success','cancelled'=>'danger'];
                                $sc = $status_badges[$o['status']] ?? 'secondary';
                                echo "<span class='badge bg-{$sc}'>" . htmlspecialchars($o['status']) . "</span>";
                                ?>
                            </td>
                            <td><?php echo getPaymentStatusBadge($o['payment_status']); ?></td>
                            <td><small class="text-muted"><?php echo formatDateTime($o['created_at']); ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($o['status'] === 'pending'): ?>
                                        <a href="?action=preparing&id=<?php echo $o['id']; ?>" class="btn btn-outline-info" title="Start Preparing"><i class="bi bi-play-fill"></i></a>
                                    <?php endif; ?>
                                    <?php if ($o['status'] === 'preparing'): ?>
                                        <a href="?action=ready&id=<?php echo $o['id']; ?>" class="btn btn-outline-primary" title="Mark Ready"><i class="bi bi-check"></i></a>
                                    <?php endif; ?>
                                    <?php if ($o['status'] === 'ready'): ?>
                                        <a href="?action=completed&id=<?php echo $o['id']; ?>" class="btn btn-outline-success" title="Complete"><i class="bi bi-check-all"></i></a>
                                    <?php endif; ?>
                                    <?php if (!in_array($o['status'], ['completed', 'cancelled'])): ?>
                                        <a href="?action=cancelled&id=<?php echo $o['id']; ?>" class="btn btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this order?')"><i class="bi bi-x-lg"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?><tr><td colspan="10" class="text-center py-4 text-muted">No orders found.</td></tr><?php endif; ?>
                        <?php } catch (Exception $e) { echo '<tr><td colspan="10" class="text-danger">' . htmlspecialchars($e->getMessage()) . '</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
