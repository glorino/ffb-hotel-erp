<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Manage Services';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();

$edit_id = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_service = null;
if ($edit_id) {
    try { $stmt = $db->prepare("SELECT * FROM services WHERE id = ?"); $stmt->execute([$edit_id]); $edit_service = $stmt->fetch(); } catch (Exception $e) {}
}

if (isset($_POST['save_service']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    try {
        $image = $edit_service['image'] ?? null;
        if (!empty($_FILES['service_image']['name'])) {
            $ext = pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION);
            $image = 'uploads/services/' . uniqid('svc_') . '.' . $ext;
            move_uploaded_file($_FILES['service_image']['tmp_name'], __DIR__ . '/../' . $image);
        }
        if ($edit_id) {
            $stmt = $db->prepare("UPDATE services SET branch_id=?, name=?, description=?, price=?, category=?, status=?, image=? WHERE id=?");
            $stmt->execute([$_POST['branch_id'], $_POST['name'], $_POST['description'] ?: null, $_POST['price'], $_POST['category'] ?: null, $_POST['status'] ?? 'active', $image, $edit_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO services (branch_id, name, description, price, category, image, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$_POST['branch_id'], $_POST['name'], $_POST['description'] ?: null, $_POST['price'], $_POST['category'] ?: null, $image]);
        }
        echo '<div class="alert alert-success">Service saved.</div><meta http-equiv="refresh" content="1;url=services.php">';
    } catch (Exception $e) { echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>'; }
}

if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    try { $stmt = $db->prepare("DELETE FROM services WHERE id = ?"); $stmt->execute([$_GET['delete']]); echo '<div class="alert alert-success">Service deleted.</div>'; } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}

if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
    try {
        $stmt = $db->prepare("SELECT status FROM services WHERE id = ?"); $stmt->execute([$_GET['toggle']]);
        $s = $stmt->fetch();
        if ($s) { $ns = $s['status'] === 'active' ? 'inactive' : 'active'; $stmt = $db->prepare("UPDATE services SET status = ? WHERE id = ?"); $stmt->execute([$ns, $_GET['toggle']]); echo '<div class="alert alert-success">Service status updated.</div>'; }
    } catch (Exception $e) { echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>'; }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Services</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Manage Services</h4>
        <a href="services.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Service</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold"><?php echo $edit_id ? 'Edit Service' : 'Add New Service'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Select Branch</option>
                                <?php
                                $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active'");
                                while ($b = $stmt->fetch()) {
                                    $sel = ($edit_service['branch_id'] ?? '') == $b['id'] ? 'selected' : '';
                                    echo "<option value=\"{$b['id']}\" $sel>" . htmlspecialchars($b['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_service['name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_service['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (<?php echo CURRENCY_SYMBOL; ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo htmlspecialchars($edit_service['price'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($edit_service['category'] ?? ''); ?>" placeholder="e.g. Spa, Laundry, Transport">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="service_image" class="form-control" accept="image/*">
                            <?php if (!empty($edit_service['image'])): ?>
                            <div class="mt-2"><img src="<?php echo $base_url . htmlspecialchars($edit_service['image']); ?>" class="img-thumbnail" style="max-height:60px;"></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="save_service" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> <?php echo $edit_id ? 'Update' : 'Save'; ?></button>
                        <?php if ($edit_id): ?><a href="services.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <?php
            try {
                $stmt = $db->query("SELECT s.*, b.name as branch_name FROM services s JOIN branches b ON s.branch_id = b.id ORDER BY s.name");
                $services = $stmt->fetchAll();
            ?>
            <div class="row g-3">
                <?php foreach ($services as $svc): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($svc['name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($svc['branch_name']); ?></small>
                                </div>
                                <?php echo $svc['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?>
                            </div>
                            <?php if ($svc['image']): ?>
                                <img src="<?php echo $base_url . htmlspecialchars($svc['image']); ?>" class="rounded mb-2" style="width:100%;height:120px;object-fit:cover;">
                            <?php endif; ?>
                            <p class="small text-muted mb-2"><?php echo htmlspecialchars($svc['description'] ?? 'No description'); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary"><?php echo formatMoney($svc['price']); ?></span>
                                <div class="btn-group btn-group-sm">
                                    <a href="services.php?edit=<?php echo $svc['id']; ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <a href="services.php?toggle=<?php echo $svc['id']; ?>" class="btn btn-outline-secondary"><i class="bi bi-toggle-on"></i></a>
                                    <a href="services.php?delete=<?php echo $svc['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($services)): ?>
                <div class="col-12 text-center py-4 text-muted">No services found.</div>
                <?php endif; ?>
            </div>
            <?php } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; } ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
