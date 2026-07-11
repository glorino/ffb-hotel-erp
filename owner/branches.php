<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Branches';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Branches</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">All Branches</h4>
        <a href="branches.php?action=add" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Branch</a>
    </div>

    <?php
    $db = getDB();
    $branches = [];
    try {
        $stmt = $db->query("
            SELECT b.*, 
                   (SELECT COUNT(*) FROM rooms r WHERE r.branch_id = b.id) as total_rooms,
                   (SELECT COUNT(*) FROM rooms r WHERE r.branch_id = b.id AND r.status = 'occupied') as occupied_rooms,
                   (SELECT u.full_name FROM users u WHERE u.branch_id = b.id AND u.role_id = (SELECT id FROM roles WHERE slug = 'branch_manager') LIMIT 1) as manager_name
            FROM branches b
            ORDER BY b.name
        ");
        $branches = $stmt->fetchAll();
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error loading branches: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-semibold">Add New Branch</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="branches.php" class="row g-3">
                <?php echo csrf_field(); ?>
                <div class="col-md-6">
                    <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" name="save_branch" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Branch</button>
                    <a href="branches.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php
    endif;

    if (isset($_POST['save_branch']) && verify_csrf($_POST['csrf_token'] ?? '')) {
        try {
            $slug = slugify($_POST['name']);
            $stmt = $db->prepare("INSERT INTO branches (name, slug, address, city, state, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'], $slug, $_POST['address'] ?? null, $_POST['city'] ?? null,
                $_POST['state'] ?? null, $_POST['phone'] ?? null, $_POST['email'] ?? null,
                $_POST['status'] ?? 'active'
            ]);
            log_audit('create', 'branch', $db->lastInsertId(), null, $_POST);
            echo '<div class="alert alert-success alert-dismissible fade show">Branch created successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            echo '<meta http-equiv="refresh" content="1;url=branches.php">';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
        try {
            $stmt = $db->prepare("SELECT status FROM branches WHERE id = ?");
            $stmt->execute([$_GET['toggle']]);
            $branch = $stmt->fetch();
            if ($branch) {
                $new_status = $branch['status'] === 'active' ? 'inactive' : 'active';
                $stmt = $db->prepare("UPDATE branches SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $_GET['toggle']]);
                log_audit('update', 'branch', $_GET['toggle'], ['status' => $branch['status']], ['status' => $new_status]);
                echo '<div class="alert alert-success alert-dismissible fade show">Branch status updated.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
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
                            <th>Address</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Manager</th>
                            <th>Rooms</th>
                            <th>Occupancy</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($branches)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No branches found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($branches as $b): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($b['name']); ?></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($b['address'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($b['city'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($b['phone'] ?? 'N/A'); ?></td>
                            <td><small><?php echo htmlspecialchars($b['email'] ?? 'N/A'); ?></small></td>
                            <td><?php echo $b['manager_name'] ? htmlspecialchars($b['manager_name']) : '<span class="text-muted">Unassigned</span>'; ?></td>
                            <td><span class="badge bg-secondary"><?php echo $b['total_rooms']; ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;max-width:80px;">
                                        <div class="progress-bar bg-success" style="width:<?php echo $b['total_rooms'] > 0 ? round(($b['occupied_rooms']/$b['total_rooms'])*100) : 0; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $b['total_rooms'] > 0 ? round(($b['occupied_rooms']/$b['total_rooms'])*100) : 0; ?>%</small>
                                </div>
                            </td>
                            <td><?php echo $b['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="branches.php?toggle=<?php echo $b['id']; ?>" class="btn btn-outline-secondary" title="Toggle Status">
                                        <i class="bi bi-toggle-on"></i>
                                    </a>
                                    <a href="branches.php?edit=<?php echo $b['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
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

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
