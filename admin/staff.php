<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Staff Management';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/admin-sidebar.php';

$db = getDB();

$edit_id = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_user = null;
if ($edit_id) {
    try { $stmt = $db->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$edit_id]); $edit_user = $stmt->fetch(); } catch (Exception $e) {}
}

if (isset($_POST['save_user']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    try {
        if ($edit_id) {
            $sql = "UPDATE users SET branch_id=?, role_id=?, full_name=?, email=?, phone=?, status=? WHERE id=?";
            $params = [$_POST['branch_id'] ?: null, $_POST['role_id'], $_POST['full_name'], $_POST['email'], $_POST['phone'] ?: null, $_POST['status'] ?? 'active', $edit_id];
            if (!empty($_POST['password'])) {
                $sql = "UPDATE users SET branch_id=?, role_id=?, full_name=?, email=?, phone=?, password=?, status=? WHERE id=?";
                $params = [$_POST['branch_id'] ?: null, $_POST['role_id'], $_POST['full_name'], $_POST['email'], $_POST['phone'] ?: null, password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['status'] ?? 'active', $edit_id];
            }
            $stmt = $db->prepare($sql); $stmt->execute($params);
            log_audit('update', 'user', $edit_id, null, $_POST);
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?"); $stmt->execute([$_POST['email']]);
            if ($stmt->fetch()) { echo '<div class="alert alert-danger">Email already exists.</div>'; } else {
                $stmt = $db->prepare("INSERT INTO users (branch_id, role_id, full_name, email, phone, password, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$_POST['branch_id'] ?: null, $_POST['role_id'], $_POST['full_name'], $_POST['email'], $_POST['phone'] ?: null, password_hash($_POST['password'], PASSWORD_DEFAULT)]);
                log_audit('create', 'user', $db->lastInsertId(), null, $_POST);
            }
        }
        echo '<div class="alert alert-success alert-dismissible fade show">Staff saved.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        echo '<meta http-equiv="refresh" content="1;url=staff.php">';
    } catch (Exception $e) { echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>'; }
}

if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    try { $stmt = $db->prepare("DELETE FROM users WHERE id = ?"); $stmt->execute([$_GET['delete']]); echo '<div class="alert alert-success">User deleted.</div>'; } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Staff</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Manage Staff</h4>
        <a href="staff.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Staff</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold"><?php echo $edit_id ? 'Edit Staff' : 'Create Staff Account'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php
                                $stmt = $db->query("SELECT id, name, slug FROM roles WHERE slug != 'customer' ORDER BY name");
                                while ($r = $stmt->fetch()) {
                                    $sel = ($edit_user['role_id'] ?? '') == $r['id'] ? 'selected' : '';
                                    echo "<option value=\"{$r['id']}\" $sel>" . htmlspecialchars($r['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">All Branches</option>
                                <?php
                                $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active'");
                                while ($b = $stmt->fetch()) {
                                    $sel = ($edit_user['branch_id'] ?? '') == $b['id'] ? 'selected' : '';
                                    echo "<option value=\"{$b['id']}\" $sel>" . htmlspecialchars($b['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <?php echo $edit_id ? '(leave blank to keep current)' : '<span class="text-danger">*</span>'; ?></label>
                            <input type="password" name="password" class="form-control" <?php echo $edit_id ? '' : 'required minlength="6"'; ?>>
                        </div>
                        <?php if ($edit_id): ?>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo ($edit_user['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($edit_user['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="blocked" <?php echo ($edit_user['status'] ?? '') === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        <button type="submit" name="save_user" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> <?php echo $edit_id ? 'Update Staff' : 'Create Staff'; ?></button>
                        <?php if ($edit_id): ?><a href="staff.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a><?php endif; ?>
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
                                <tr><th>Name</th><th>Email</th><th>Role</th><th>Branch</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("
                                        SELECT u.*, r.name as role_name, b.name as branch_name
                                        FROM users u
                                        JOIN roles r ON u.role_id = r.id
                                        LEFT JOIN branches b ON u.branch_id = b.id
                                        WHERE r.slug != 'customer'
                                        ORDER BY u.created_at DESC
                                    ");
                                    $users = $stmt->fetchAll();
                                    foreach ($users as $u):
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars($u['email']); ?></small></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($u['role_name']); ?></span></td>
                                    <td><?php echo htmlspecialchars($u['branch_name'] ?? 'All'); ?></td>
                                    <td>
                                        <?php if ($u['status'] === 'active'): ?><span class="badge bg-success">Active</span>
                                        <?php elseif ($u['status'] === 'inactive'): ?><span class="badge bg-warning text-dark">Inactive</span>
                                        <?php else: ?><span class="badge bg-danger">Blocked</span><?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo $u['last_login'] ? formatDateTime($u['last_login']) : 'Never'; ?></small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="staff.php?edit=<?php echo $u['id']; ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            <a href="staff.php?delete=<?php echo $u['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($users)): ?><tr><td colspan="7" class="text-center py-4 text-muted">No staff found.</td></tr><?php endif; ?>
                                <?php } catch (Exception $e) { echo '<tr><td colspan="7" class="text-danger">' . htmlspecialchars($e->getMessage()) . '</td></tr>'; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
