<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['kitchen_chef']);

$page_title = 'Unavailable Items';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/kitchen-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $item_id = (int)$_POST['toggle_id'];
    try {
        $stmt = $db->prepare("SELECT is_available FROM food_items WHERE id = ? AND branch_id = ?");
        $stmt->execute([$item_id, $branch_id]);
        $item = $stmt->fetch();
        if ($item) {
            $new_status = $item['is_available'] ? 0 : 1;
            $stmt = $db->prepare("UPDATE food_items SET is_available = ? WHERE id = ? AND branch_id = ?");
            $stmt->execute([$new_status, $item_id, $branch_id]);
            set_flash('success', 'Item availability updated.');
        }
    } catch (Exception $e) {
        error_log('Toggle availability error: ' . $e->getMessage());
        set_flash('danger', 'Failed to update item.');
    }
    header('Location: unavailable-items.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock_note'])) {
    $item_id = (int)$_POST['item_id'];
    $note = trim($_POST['stock_note'] ?? '');
    try {
        $stmt = $db->prepare("UPDATE food_items SET description = COALESCE(description, '') WHERE id = ? AND branch_id = ?");
        $stmt->execute([$item_id, $branch_id]);

        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_type, reference_id) VALUES (?, ?, ?, 'system', 'food_item', ?)
                               ON CONFLICT (id) DO UPDATE SET message = EXCLUDED.message, created_at = NOW()");
        $stmt->execute([0, 'Stock Note Updated', 'Stock note for item #' . $item_id . ': ' . $note, $item_id]);
        set_flash('info', 'Stock note saved.');
    } catch (Exception $e) {
        error_log('Stock note error: ' . $e->getMessage());
    }
    header('Location: unavailable-items.php');
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Kitchen</a></li>
            <li class="breadcrumb-item active">Menu Item Availability</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-semibold">Food Item Availability</h4>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">All Menu Items</h5>
            <div>
                <a href="?filter=all" class="btn btn-sm <?php echo (!isset($_GET['filter']) || $_GET['filter'] === 'all') ? 'btn-dark' : 'btn-outline-secondary'; ?>">All</a>
                <a href="?filter=unavailable" class="btn btn-sm <?php echo ($_GET['filter'] ?? '') === 'unavailable' ? 'btn-danger' : 'btn-outline-danger'; ?>">Unavailable</a>
                <a href="?filter=available" class="btn btn-sm <?php echo ($_GET['filter'] ?? '') === 'available' ? 'btn-success' : 'btn-outline-success'; ?>">Available</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Preparation Time</th>
                            <th>Status</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $filter = $_GET['filter'] ?? 'all';
                            $sql = "SELECT fi.*, fc.name as category_name 
                                    FROM food_items fi 
                                    JOIN food_categories fc ON fi.category_id = fc.id 
                                    WHERE fi.branch_id = ? AND fi.status = 'active'";
                            $params = [$branch_id];

                            if ($filter === 'available') {
                                $sql .= " AND fi.is_available = 1";
                            } elseif ($filter === 'unavailable') {
                                $sql .= " AND fi.is_available = 0";
                            }
                            $sql .= " ORDER BY fc.name, fi.name";

                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $items = $stmt->fetchAll();

                            if (empty($items)):
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No menu items found.</td>
                        </tr>
                            <?php else: ?>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['category_name']); ?></span></td>
                                <td><?php echo formatMoney($item['price']); ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($item['preparation_time'] ?? '-'); ?></small></td>
                                <td>
                                    <?php if ($item['is_available']): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="toggle_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $item['is_available'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>">
                                            <?php if ($item['is_available']): ?>
                                                <i class="bi bi-x-circle"></i> Mark Unavailable
                                            <?php else: ?>
                                                <i class="bi bi-check-circle"></i> Mark Available
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#noteModal<?php echo $item['id']; ?>">
                                        <i class="bi bi-pencil"></i> Note
                                    </button>
                                    <div class="modal fade" id="noteModal<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Stock Note for <?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <textarea name="stock_note" class="form-control" rows="3" placeholder="e.g. Out of stock until Friday"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" name="update_stock_note" class="btn btn-primary btn-sm">Save Note</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        <?php } catch (Exception $e) {
                            error_log('Unavailable items error: ' . $e->getMessage());
                            echo '<tr><td colspan="7" class="text-center py-4 text-danger">Error loading items.</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
