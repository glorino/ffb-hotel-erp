<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Customer Issues';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resolve_issue'])) {
        $issue_id = (int)($_POST['issue_id'] ?? 0);
        try {
            $stmt = $db->prepare("UPDATE customer_issues SET status = 'resolved', resolved_at = NOW(), resolved_by = ? WHERE id = ? AND branch_id = ?");
            $stmt->execute([$_SESSION['user_id'], $issue_id, $branch_id]);
            log_audit('resolve_issue', 'customer_issue', $issue_id);
            set_flash('success', 'Issue marked as resolved');
        } catch (Exception $e) {
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
        header('Location: customer-issues.php');
        exit;
    }

    if (isset($_POST['add_issue'])) {
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $issue_type = sanitize($_POST['issue_type'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'medium');
        if ($customer_id && $issue_type && $description) {
            try {
                $stmt = $db->prepare("INSERT INTO customer_issues (branch_id, customer_id, issue_type, description, priority, status, created_at) VALUES (?, ?, ?, ?, ?, 'open', NOW())");
                $stmt->execute([$branch_id, $customer_id, $issue_type, $description, $priority]);
                log_audit('create_issue', 'customer_issue', $db->lastInsertId());
                set_flash('success', 'Issue logged successfully');
            } catch (Exception $e) {
                set_flash('danger', 'Error: ' . $e->getMessage());
            }
        } else {
            set_flash('warning', 'Please fill all required fields');
        }
        header('Location: customer-issues.php');
        exit;
    }
}

$status_filter = $_GET['status'] ?? '';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Customer Issues</li>
        </ol>
    </nav>

    <?php
    $open_count = 0; $resolved_count = 0;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM customer_issues WHERE branch_id = ? AND status = 'open'");
        $stmt->execute([$branch_id]); $open_count = $stmt->fetchColumn();
        $stmt = $db->prepare("SELECT COUNT(*) FROM customer_issues WHERE branch_id = ? AND status = 'resolved'");
        $stmt->execute([$branch_id]); $resolved_count = $stmt->fetchColumn();
    } catch (Exception $e) {}
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Open Issues</p>
                    <h3 class="stat-value mb-0 text-danger"><?php echo number_format($open_count); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Resolved</p>
                    <h3 class="stat-value mb-0 text-success"><?php echo number_format($resolved_count); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Issues Log</h5>
                    <div class="btn-group btn-group-sm">
                        <a href="?status=" class="btn btn-outline-secondary <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
                        <a href="?status=open" class="btn btn-outline-danger <?php echo $status_filter === 'open' ? 'active' : ''; ?>">Open</a>
                        <a href="?status=resolved" class="btn btn-outline-success <?php echo $status_filter === 'resolved' ? 'active' : ''; ?>">Resolved</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th>Issue Type</th>
                                    <th>Priority</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $sql = "SELECT ci.*, c.first_name, c.last_name, c.email, c.phone
                                            FROM customer_issues ci
                                            LEFT JOIN customers c ON ci.customer_id = c.id
                                            WHERE ci.branch_id = ?";
                                    $params = [$branch_id];
                                    if ($status_filter) { $sql .= " AND ci.status = ?"; $params[] = $status_filter; }
                                    $sql .= " ORDER BY ci.created_at DESC";
                                    $stmt = $db->prepare($sql);
                                    $stmt->execute($params);
                                    $issues = $stmt->fetchAll();
                                    foreach ($issues as $iss):
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars(($iss['first_name'] ?? '') . ' ' . ($iss['last_name'] ?? 'Unknown')); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($iss['phone'] ?? $iss['email'] ?? ''); ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $iss['issue_type']))); ?></span></td>
                                    <td>
                                        <?php
                                        $priority_class = ['low'=>'success','medium'=>'warning','high'=>'danger','critical'=>'dark'];
                                        $pclass = $priority_class[$iss['priority']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $pclass; ?>"><?php echo ucfirst($iss['priority']); ?></span>
                                    </td>
                                    <td><small><?php echo htmlspecialchars(truncate($iss['description'], 60)); ?></small></td>
                                    <td>
                                        <span class="badge bg-<?php echo $iss['status'] === 'open' ? 'danger' : 'success'; ?>">
                                            <?php echo ucfirst($iss['status']); ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?php echo timeAgo($iss['created_at']); ?></small></td>
                                    <td>
                                        <?php if ($iss['status'] === 'open'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Mark this issue as resolved?')">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="issue_id" value="<?php echo $iss['id']; ?>">
                                            <button type="submit" name="resolve_issue" class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i> Resolve</button>
                                        </form>
                                        <?php else: ?>
                                        <small class="text-muted">Resolved <?php echo isset($iss['resolved_at']) ? timeAgo($iss['resolved_at']) : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                                    endforeach;
                                    if (empty($issues)):
                                ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No issues found</td></tr>
                                <?php
                                    endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="7" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Log New Issue</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label small">Customer</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select customer</option>
                                <?php
                                $custs = $db->prepare("SELECT id, first_name, last_name, email, phone FROM customers WHERE branch_id = ? OR branch_id IS NULL ORDER BY first_name LIMIT 100");
                                $custs->execute([$branch_id]);
                                while ($c = $custs->fetch()):
                                ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . ($c['phone'] ?? $c['email']) . ')'); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Issue Type</label>
                            <select name="issue_type" class="form-select" required>
                                <option value="">Select type</option>
                                <option value="noise_complaint">Noise Complaint</option>
                                <option value="room_issue">Room Issue</option>
                                <option value="cleanliness">Cleanliness</option>
                                <option value="service_quality">Service Quality</option>
                                <option value="billing">Billing</option>
                                <option value="food_quality">Food Quality</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Description</label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="Describe the issue..."></textarea>
                        </div>
                        <button type="submit" name="add_issue" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-2"></i>Log Issue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
