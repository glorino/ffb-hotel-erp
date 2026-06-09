<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Staff Management';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$role_filter = $_GET['role'] ?? '';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Staff</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">All Staff</h4>
        <div>
            <select id="roleFilter" class="form-select form-select-sm d-inline-block w-auto" onchange="location.href='?role='+this.value">
                <option value="">All Roles</option>
                <?php
                try {
                    $stmt = $db->query("SELECT id, name, slug FROM roles WHERE slug != 'customer' ORDER BY name");
                    $roles = $stmt->fetchAll();
                    foreach ($roles as $r) {
                        $selected = $role_filter === $r['slug'] ? 'selected' : '';
                        echo "<option value=\"{$r['slug']}\" $selected>" . htmlspecialchars($r['name']) . "</option>";
                    }
                } catch (Exception $e) {}
                ?>
            </select>
            <a href="staff.php?action=add" class="btn btn-primary btn-sm ms-2"><i class="bi bi-plus-lg"></i> Add Staff</a>
        </div>
    </div>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-semibold">Add New Staff Member</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="staff.php" class="row g-3">
                <?php echo csrf_field(); ?>
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        <?php
                        $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active'");
                        while ($b = $stmt->fetch()) {
                            echo "<option value=\"{$b['id']}\">" . htmlspecialchars($b['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="col-12">
                    <button type="submit" name="save_staff" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Staff</button>
                    <a href="staff.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif;

    if (isset($_POST['save_staff']) && verify_csrf($_POST['csrf_token'] ?? '')) {
        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if ($stmt->fetch()) {
                echo '<div class="alert alert-danger">A user with this email already exists.</div>';
            } else {
                $stmt = $db->prepare("INSERT INTO users (branch_id, role_id, full_name, email, phone, password, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([
                    $_POST['branch_id'] ?: null, $_POST['role_id'], $_POST['full_name'],
                    $_POST['email'], $_POST['phone'] ?: null, password_hash($_POST['password'], PASSWORD_DEFAULT)
                ]);
                log_audit('create', 'user', $db->lastInsertId(), null, $_POST);
                echo '<div class="alert alert-success alert-dismissible fade show">Staff created successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                echo '<meta http-equiv="refresh" content="1;url=staff.php">';
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "
                                SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name
                                FROM users u
                                JOIN roles r ON u.role_id = r.id
                                LEFT JOIN branches b ON u.branch_id = b.id
                                WHERE r.slug != 'customer'
                            ";
                            $params = [];
                            if ($role_filter) {
                                $sql .= " AND r.slug = ?";
                                $params[] = $role_filter;
                            }
                            $sql .= " ORDER BY u.created_at DESC";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $staff_list = $stmt->fetchAll();

                            if (empty($staff_list)):
                        ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No staff found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($staff_list as $s): ?>
                        <tr>
                            <td class="fw-medium">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#2d3436;color:#fff;font-size:12px;">
                                        <?php echo strtoupper(substr($s['full_name'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($s['full_name']); ?>
                                </div>
                            </td>
                            <td><small><?php echo htmlspecialchars($s['email']); ?></small></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($s['role_name']); ?></span></td>
                            <td><?php echo htmlspecialchars($s['branch_name'] ?? 'All'); ?></td>
                            <td>
                                <?php if ($s['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php elseif ($s['status'] === 'inactive'): ?>
                                    <span class="badge bg-warning text-dark">Inactive</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Blocked</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo $s['last_login'] ? formatDateTime($s['last_login']) : 'Never'; ?></small></td>
                            <td><small class="text-muted"><?php echo formatDate($s['created_at']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr><td colspan="7" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
