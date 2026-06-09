<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Coupons & Promotions';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Coupons & Promotions</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">All Coupons</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCouponModal">
            <i class="bi bi-plus-lg"></i> Create Coupon
        </button>
    </div>

    <?php
    if (isset($_POST['save_coupon']) && verify_csrf($_POST['csrf_token'] ?? '')) {
        try {
            $stmt = $db->prepare("INSERT INTO coupons (code, title, description, discount_type, discount_value, start_date, end_date, branch_id, applicable_to, minimum_spend, usage_limit, usage_per_customer, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([
                strtoupper($_POST['code']), $_POST['title'], $_POST['description'] ?? null,
                $_POST['discount_type'], $_POST['discount_value'], $_POST['start_date'], $_POST['end_date'],
                $_POST['branch_id'] ?: null, $_POST['applicable_to'],
                $_POST['minimum_spend'] ?: 0, $_POST['usage_limit'] ?: 0, $_POST['usage_per_customer'] ?: 0
            ]);
            log_audit('create', 'coupon', $db->lastInsertId(), null, $_POST);
            echo '<div class="alert alert-success alert-dismissible fade show">Coupon created successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
        try {
            $stmt = $db->prepare("SELECT status FROM coupons WHERE id = ?");
            $stmt->execute([$_GET['toggle']]);
            $c = $stmt->fetch();
            if ($c) {
                $new_status = $c['status'] === 'active' ? 'inactive' : 'active';
                $stmt = $db->prepare("UPDATE coupons SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $_GET['toggle']]);
                echo '<div class="alert alert-success alert-dismissible fade show">Coupon status updated.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    ?>

    <div class="modal fade" id="addCouponModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Coupon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body row g-3">
                        <?php echo csrf_field(); ?>
                        <div class="col-md-6">
                            <label class="form-label">Coupon Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase" required placeholder="e.g. SUMMER20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Summer Special">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" class="form-select">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (<?php echo CURRENCY_SYMBOL; ?>)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="discount_value" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Minimum Spend</label>
                            <input type="number" step="0.01" name="minimum_spend" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Applicable To</label>
                            <select name="applicable_to" class="form-select">
                                <option value="all">All</option>
                                <option value="rooms">Rooms</option>
                                <option value="food">Food</option>
                                <option value="services">Services</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch (optional)</label>
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
                        <div class="col-md-3">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-control" value="0" placeholder="0 = unlimited">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Per Customer</label>
                            <input type="number" name="usage_per_customer" class="form-control" value="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_coupon" class="btn btn-primary">Create Coupon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Usage</th>
                            <th>Applicable</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->query("
                                SELECT c.*, 
                                       (SELECT COUNT(*) FROM coupon_usages cu WHERE cu.coupon_id = c.id) as times_used
                                FROM coupons c ORDER BY c.created_at DESC
                            ");
                            $coupons = $stmt->fetchAll();
                            if (empty($coupons)):
                        ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No coupons created yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($coupons as $cp): ?>
                        <tr>
                            <td class="fw-medium"><span class="badge bg-dark"><?php echo htmlspecialchars($cp['code']); ?></span></td>
                            <td><?php echo htmlspecialchars($cp['title'] ?? 'N/A'); ?></td>
                            <td><?php echo $cp['discount_type'] === 'percentage' ? '<span class="badge bg-info">%</span>' : '<span class="badge bg-secondary">' . CURRENCY_SYMBOL . '</span>'; ?></td>
                            <td><?php echo $cp['discount_type'] === 'percentage' ? number_format($cp['discount_value'], 1) . '%' : formatMoney($cp['discount_value']); ?></td>
                            <td><small><?php echo formatDate($cp['start_date']); ?></small></td>
                            <td><small><?php echo formatDate($cp['end_date']); ?></small></td>
                            <td><span class="badge bg-primary"><?php echo (int) $cp['times_used']; ?> / <?php echo $cp['usage_limit'] ?: '∞'; ?></span></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($cp['applicable_to']); ?></span></td>
                            <td>
                                <?php if ($cp['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php elseif ($cp['status'] === 'inactive'): ?>
                                    <span class="badge bg-warning text-dark">Inactive</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?toggle=<?php echo $cp['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Toggle Status">
                                    <i class="bi bi-toggle-on"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr><td colspan="10" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
