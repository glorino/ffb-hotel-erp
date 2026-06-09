<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

$page_title = 'Supplier Management';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND branch_id = " . (int)$branch_id : "";

$suppliers = [];
try {
    $stmt = $db->query("SELECT s.*, (SELECT COUNT(*) FROM inventory_items WHERE supplier_id = s.id AND status = 'active') as item_count FROM suppliers s WHERE 1=1 $branch_filter ORDER BY s.name");
    $suppliers = $stmt->fetchAll();
} catch (Exception $e) {
}

$items_by_supplier = [];
if (!empty($_GET['view_items'])) {
    try {
        $stmt = $db->prepare("SELECT i.* FROM inventory_items i WHERE i.supplier_id = ? AND i.status = 'active' ORDER BY i.name");
        $stmt->execute([(int)$_GET['view_items']]);
        $items_by_supplier = $stmt->fetchAll();
    } catch (Exception $e) {
    }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Supplier Management</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Suppliers</h4>
        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addSupplierModal"><i class="bi bi-plus-lg"></i> Add Supplier</button>
    </div>

    <?php if (!empty($_GET['view_items'])): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Items Supplied by: <?php echo htmlspecialchars($suppliers[array_search((int)$_GET['view_items'], array_column($suppliers, 'id'))]['name'] ?? 'Supplier'); ?></h5>
            <a href="suppliers.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Close</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Item</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Price/Unit</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items_by_supplier)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No items from this supplier.</td></tr>
                        <?php else: ?>
                        <?php foreach ($items_by_supplier as $it): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($it['name']); ?></td>
                            <td><?php echo htmlspecialchars($it['category'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($it['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($it['unit'] ?? 'N/A'); ?></td>
                            <td><?php echo formatMoney($it['price_per_unit'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($suppliers)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No suppliers found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo htmlspecialchars($s['contact_person'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($s['email'] ?? ''); ?>"><?php echo htmlspecialchars($s['email'] ?? 'N/A'); ?></a></td>
                            <td><small><?php echo htmlspecialchars(truncate($s['address'] ?? '', 40)); ?></small></td>
                            <td><a href="suppliers.php?view_items=<?php echo $s['id']; ?>" class="badge bg-info text-decoration-none"><?php echo $s['item_count']; ?> items</a></td>
                            <td><?php echo $s['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary edit-supplier" data-id="<?php echo $s['id']; ?>" data-name="<?php echo htmlspecialchars($s['name']); ?>" data-contact="<?php echo htmlspecialchars($s['contact_person'] ?? ''); ?>" data-phone="<?php echo htmlspecialchars($s['phone'] ?? ''); ?>" data-email="<?php echo htmlspecialchars($s['email'] ?? ''); ?>" data-address="<?php echo htmlspecialchars($s['address'] ?? ''); ?>" data-status="<?php echo $s['status']; ?>" title="Edit"><i class="bi bi-pencil"></i></button>
                                <a href="ajax/toggle-supplier-status.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-<?php echo $s['status'] === 'active' ? 'warning' : 'success'; ?> confirm-link" title="<?php echo $s['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>"><i class="bi bi-<?php echo $s['status'] === 'active' ? 'pause' : 'play'; ?>"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="ajax/save-supplier.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2"></i>Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="bi bi-check-lg"></i> Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="ajax/update-supplier.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contact Person</label>
                        <input type="text" name="contact_person" id="edit_contact" class="form-control">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="bi bi-check-lg"></i> Update Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-supplier').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_contact').value = this.dataset.contact;
        document.getElementById('edit_phone').value = this.dataset.phone;
        document.getElementById('edit_email').value = this.dataset.email;
        document.getElementById('edit_address').value = this.dataset.address;
        new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
    });
});

document.querySelectorAll('.confirm-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to toggle this supplier\'s status?')) {
            window.location.href = this.href;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
