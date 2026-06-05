<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Manage Branches';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/admin-sidebar.php';

$db = getDB();

$edit_id = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_branch = null;
if ($edit_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_branch = $stmt->fetch();
    } catch (Exception $e) {}
}

if (isset($_POST['save_branch']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    try {
        $slug = slugify($_POST['name']);
        if ($edit_id) {
            $stmt = $db->prepare("UPDATE branches SET name=?, slug=?, address=?, city=?, state=?, phone=?, email=?, status=? WHERE id=?");
            $stmt->execute([$_POST['name'], $slug, $_POST['address'] ?? null, $_POST['city'] ?? null, $_POST['state'] ?? null, $_POST['phone'] ?? null, $_POST['email'] ?? null, $_POST['status'] ?? 'active', $edit_id]);
            log_audit('update', 'branch', $edit_id, null, $_POST);
        } else {
            $stmt = $db->prepare("INSERT INTO branches (name, slug, address, city, state, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $slug, $_POST['address'] ?? null, $_POST['city'] ?? null, $_POST['state'] ?? null, $_POST['phone'] ?? null, $_POST['email'] ?? null, $_POST['status'] ?? 'active']);
            log_audit('create', 'branch', $db->lastInsertId(), null, $_POST);
        }
        echo '<div class="alert alert-success alert-dismissible fade show">Branch saved successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        echo '<meta http-equiv="refresh" content="1;url=branches.php">';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
    try {
        $stmt = $db->prepare("SELECT status FROM branches WHERE id = ?");
        $stmt->execute([$_GET['toggle']]);
        $b = $stmt->fetch();
        if ($b) {
            $ns = $b['status'] === 'active' ? 'inactive' : 'active';
            $stmt = $db->prepare("UPDATE branches SET status = ? WHERE id = ?");
            $stmt->execute([$ns, $_GET['toggle']]);
            echo '<div class="alert alert-success alert-dismissible fade show">Branch status updated.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM branches WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        echo '<div class="alert alert-success alert-dismissible fade show">Branch deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Cannot delete branch: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Branches</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Manage Branches</h4>
        <a href="branches.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Branch</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-semibold"><?php echo $edit_id ? 'Edit Branch' : 'Add New Branch'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_branch['name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($edit_branch['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($edit_branch['phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($edit_branch['city'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($edit_branch['state'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($edit_branch['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo ($edit_branch['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($edit_branch['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" name="save_branch" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> <?php echo $edit_id ? 'Update Branch' : 'Save Branch'; ?>
                        </button>
                        <?php if ($edit_id): ?>
                        <a href="branches.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Name</th><th>City</th><th>Phone</th><th>Email</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("SELECT * FROM branches ORDER BY name");
                                    $branches = $stmt->fetchAll();
                                    foreach ($branches as $b):
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($b['name']); ?></td>
                                    <td><?php echo htmlspecialchars($b['city'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($b['phone'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo htmlspecialchars($b['email'] ?? 'N/A'); ?></small></td>
                                    <td><?php echo $b['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="branches.php?edit=<?php echo $b['id']; ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            <a href="branches.php?toggle=<?php echo $b['id']; ?>" class="btn btn-outline-secondary"><i class="bi bi-toggle-on"></i></a>
                                            <a href="branches.php?delete=<?php echo $b['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this branch? This cannot be undone.')"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($branches)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No branches found.</td></tr>
                                <?php endif; ?>
                                <?php } catch (Exception $e) { ?>
                                <tr><td colspan="6" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
