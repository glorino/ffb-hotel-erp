<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['waiter']);

$page_title = 'Restaurant Tables';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/waiter-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

$status_filter = $_GET['status'] ?? 'all';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Tables</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="mb-0 fw-semibold">Restaurant Tables</h4>
        <div class="d-flex gap-1 flex-wrap">
            <a href="?status=all" class="btn btn-sm <?php echo $status_filter === 'all' ? 'btn-dark' : 'btn-outline-secondary'; ?>">All</a>
            <a href="?status=available" class="btn btn-sm <?php echo $status_filter === 'available' ? 'btn-success' : 'btn-outline-success'; ?>">Available</a>
            <a href="?status=occupied" class="btn btn-sm <?php echo $status_filter === 'occupied' ? 'btn-warning' : 'btn-outline-warning'; ?>">Occupied</a>
            <a href="?status=reserved" class="btn btn-sm <?php echo $status_filter === 'reserved' ? 'btn-info' : 'btn-outline-info'; ?>">Reserved</a>
            <a href="?status=cleaning" class="btn btn-sm <?php echo $status_filter === 'cleaning' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">Cleaning</a>
        </div>
    </div>

    <?php
    try {
        $sql = "SELECT * FROM restaurant_tables WHERE branch_id = ?";
        $params = [$branch_id];
        if ($status_filter !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status_filter;
        }
        $sql .= " ORDER BY table_number ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tables = $stmt->fetchAll();

        if (empty($tables)):
    ?>
        <div class="text-center py-5">
            <i class="bi bi-table fs-1 text-muted"></i>
            <p class="text-muted mt-2 mb-0">No tables found. The restaurant_tables table may not exist yet.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($tables as $table):
                $status = $table['status'];
                $status_colors = [
                    'available' => ['bg' => 'success', 'icon' => 'bi-check-circle'],
                    'occupied' => ['bg' => 'warning', 'icon' => 'bi-people'],
                    'reserved' => ['bg' => 'info', 'icon' => 'bi-bookmark-check'],
                    'cleaning' => ['bg' => 'secondary', 'icon' => 'bi-broom']
                ];
                $sc = $status_colors[$status] ?? ['bg' => 'secondary', 'icon' => 'bi-question-circle'];

                $active_order = null;
                if ($status === 'occupied') {
                    $stmt2 = $db->prepare("SELECT id, order_reference FROM food_orders WHERE branch_id = ? AND table_number = ? AND status NOT IN ('completed', 'cancelled') ORDER BY created_at DESC LIMIT 1");
                    $stmt2->execute([$branch_id, $table['table_number']]);
                    $active_order = $stmt2->fetch();
                }
            ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm table-card h-100 text-center <?php echo 'border-' . $sc['bg']; ?>" style="border-left: 4px solid var(--bs-<?php echo $sc['bg']; ?>);">
                    <div class="card-body">
                        <div class="table-status-icon mb-2">
                            <i class="bi <?php echo $sc['icon']; ?> fs-1 text-<?php echo $sc['bg']; ?>"></i>
                        </div>
                        <h5 class="card-title mb-1 fw-bold">Table <?php echo htmlspecialchars($table['table_number']); ?></h5>
                        <p class="text-muted small mb-2">Capacity: <?php echo (int)$table['capacity']; ?> seats</p>
                        <span class="badge bg-<?php echo $sc['bg']; ?> mb-2"><?php echo ucfirst($status); ?></span>
                        <?php if ($active_order): ?>
                            <div class="mt-2">
                                <small class="d-block text-muted">Order: <?php echo htmlspecialchars($active_order['order_reference']); ?></small>
                            </div>
                        <?php endif; ?>
                        <div class="mt-3 d-grid gap-1">
                            <?php if ($status === 'available'): ?>
                                <a href="new-order.php?table=<?php echo urlencode($table['table_number']); ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> New Order
                                </a>
                            <?php endif; ?>
                            <?php if ($status === 'occupied' || $status === 'reserved'): ?>
                                <a href="active-orders.php?table=<?php echo urlencode($table['table_number']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Orders
                                </a>
                            <?php endif; ?>
                            <?php if ($status === 'occupied'): ?>
                                <a href="bills.php?table=<?php echo urlencode($table['table_number']); ?>" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-file-earmark-text"></i> Bill
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php } catch (Exception $e) {
        error_log('Tables error: ' . $e->getMessage());
        echo '<div class="alert alert-warning">Unable to load tables. The restaurant_tables table may not exist. Please create the table or contact your administrator.</div>';
    } ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
