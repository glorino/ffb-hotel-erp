<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

$page_title = 'Stock Items';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND i.branch_id = " . (int)$branch_id : "";

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "WHERE i.status != 'deleted' $branch_filter";
$params = [];
if ($search) {
    $where .= " AND i.name LIKE ?";
    $params[] = "%$search%";
}
if ($category_filter) {
    $where .= " AND i.category = ?";
    $params[] = $category_filter;
}

$total = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM inventory_items i $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
} catch (Exception $e) { $total = 0; }

$items = [];
try {
    $sql = "SELECT i.*, s.name as supplier_name FROM inventory_items i LEFT JOIN suppliers s ON i.supplier_id = s.id $where ORDER BY i.name ASC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}

$categories = [];
try {
    $stmt = $db->query("SELECT DISTINCT category FROM inventory_items WHERE category IS NOT NULL AND category != '' AND status != 'deleted' $branch_filter ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
}

$suppliers = [];
try {
    $stmt = $db->query("SELECT id, name FROM suppliers WHERE status = 'active'" . ($branch_id ? " AND branch_id = " . (int)$branch_id : ""));
    $suppliers = $stmt->fetchAll();
} catch (Exception $e) {
}

$total_pages = max(1, ceil($total / $limit));
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Stock Items</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Stock Items</h4>
        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="bi bi-plus-lg"></i> Add Item</button>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by item name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-navy w-100"><i class="bi bi-filter"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="stock-items.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Reorder Level</th>
                            <th>Price/Unit</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">No stock items found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <?php $is_low = $item['quantity'] <= $item['reorder_level']; ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></span></td>
                            <td><?php echo htmlspecialchars($item['unit'] ?? 'N/A'); ?></td>
                            <td class="<?php echo $is_low ? 'text-danger fw-bold' : ''; ?>"><?php echo number_format($item['quantity']); ?></td>
                            <td><?php echo number_format($item['reorder_level']); ?></td>
                            <td><?php echo formatMoney($item['price_per_unit'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $item['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary edit-item" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-category="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" data-unit="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>" data-quantity="<?php echo $item['quantity']; ?>" data-reorder="<?php echo $item['reorder_level']; ?>" data-price="<?php echo $item['price_per_unit']; ?>" data-supplier="<?php echo $item['supplier_id']; ?>" data-status="<?php echo $item['status']; ?>" title="Edit"><i class="bi bi-pencil"></i></button>
                                <a href="../ajax/toggle-item-status.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-<?php echo $item['status'] === 'active' ? 'warning' : 'success'; ?> confirm-link" title="<?php echo $item['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>"><i class="bi bi-<?php echo $item['status'] === 'active' ? 'pause' : 'play'; ?>"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="ajax/save-item.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Stock Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <input type="text" name="category" class="form-control" list="catList" placeholder="e.g. Food, Beverage, Cleaning">
                            <datalist id="catList">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit</label>
                            <select name="unit" class="form-select">
                                <option value="pieces">Pieces</option>
                                <option value="kg">Kilograms</option>
                                <option value="liters">Liters</option>
                                <option value="grams">Grams</option>
                                <option value="boxes">Boxes</option>
                                <option value="packs">Packs</option>
                                <option value="bottles">Bottles</option>
                                <option value="bags">Bags</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="0" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="10" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price per Unit (<?php echo CURRENCY_SYMBOL; ?>)</label>
                            <input type="number" name="price_per_unit" class="form-control" value="0" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="bi bi-check-lg"></i> Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="ajax/update-item.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Edit Stock Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <input type="text" name="category" id="edit_category" class="form-control" list="catList">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit</label>
                            <select name="unit" id="edit_unit" class="form-select">
                                <option value="pieces">Pieces</option>
                                <option value="kg">Kilograms</option>
                                <option value="liters">Liters</option>
                                <option value="grams">Grams</option>
                                <option value="boxes">Boxes</option>
                                <option value="packs">Packs</option>
                                <option value="bottles">Bottles</option>
                                <option value="bags">Bags</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Reorder Level</label>
                            <input type="number" name="reorder_level" id="edit_reorder" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price per Unit (<?php echo CURRENCY_SYMBOL; ?>)</label>
                            <input type="number" name="price_per_unit" id="edit_price" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select name="supplier_id" id="edit_supplier" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="bi bi-check-lg"></i> Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_category').value = this.dataset.category;
            document.getElementById('edit_unit').value = this.dataset.unit;
            document.getElementById('edit_quantity').value = this.dataset.quantity;
            document.getElementById('edit_reorder').value = this.dataset.reorder;
            document.getElementById('edit_price').value = this.dataset.price;
            document.getElementById('edit_supplier').value = this.dataset.supplier;
            document.getElementById('edit_status').value = this.dataset.status;
            new bootstrap.Modal(document.getElementById('editItemModal')).show();
        });
    });

    document.querySelectorAll('.confirm-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to toggle this item\'s status?')) {
                window.location.href = this.href;
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
