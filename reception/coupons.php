<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Coupons';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

$validate_result = null;
$validate_code = $_GET['validate'] ?? '';

if ($validate_code) {
    try {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND valid_from <= NOW() AND valid_to >= NOW() AND (branch_id = ? OR branch_id IS NULL)");
        $stmt->execute([$validate_code, $branch_id]);
        $coupon = $stmt->fetch();
        if ($coupon) {
            if ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) {
                $validate_result = ['valid' => false, 'message' => 'Coupon usage limit reached'];
            } else {
                $discount_text = $coupon['discount_type'] === 'percentage' ? $coupon['discount_value'] . '%' : formatMoney($coupon['discount_value']);
                $validate_result = ['valid' => true, 'message' => "Coupon valid! {$discount_text} discount", 'coupon' => $coupon];
            }
        } else {
            $validate_result = ['valid' => false, 'message' => 'Invalid or expired coupon code'];
        }
    } catch (Exception $e) {
        $validate_result = ['valid' => false, 'message' => 'Error validating coupon'];
    }

    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($validate_result ?: ['valid' => false, 'message' => 'Unknown error']);
        exit;
    }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Coupons</li>
        </ol>
    </nav>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold">Validate Coupon</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Enter Coupon Code</label>
                            <div class="input-group input-group-lg">
                                <input type="text" name="validate" class="form-control" placeholder="e.g. WELCOME20" value="<?php echo htmlspecialchars($validate_code); ?>" autocomplete="off">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Validate</button>
                            </div>
                        </div>
                    </form>
                    <?php if ($validate_result): ?>
                    <div class="alert alert-<?php echo $validate_result['valid'] ? 'success' : 'danger'; ?> mt-3 mb-0">
                        <i class="bi bi-<?php echo $validate_result['valid'] ? 'check-circle' : 'x-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($validate_result['message']); ?>
                        <?php if ($validate_result['valid'] && isset($validate_result['coupon'])): ?>
                        <hr>
                        <small>
                            <strong>Code:</strong> <?php echo htmlspecialchars($validate_result['coupon']['code']); ?><br>
                            <strong>Discount:</strong> <?php echo $validate_result['coupon']['discount_type'] === 'percentage' ? $validate_result['coupon']['discount_value'] . '%' : formatMoney($validate_result['coupon']['discount_value']); ?><br>
                            <strong>Valid until:</strong> <?php echo formatDate($validate_result['coupon']['valid_to']); ?><br>
                            <strong>Uses:</strong> <?php echo $validate_result['coupon']['used_count']; ?>/<?php echo $validate_result['coupon']['max_uses'] ?: '∞'; ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Active Coupons</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Discount</th>
                                    <th>Type</th>
                                    <th>Valid From</th>
                                    <th>Valid To</th>
                                    <th>Uses</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT * FROM coupons WHERE status = 'active' AND valid_to >= CURRENT_DATE AND (branch_id = ? OR branch_id IS NULL) ORDER BY valid_to ASC");
                                    $stmt->execute([$branch_id]);
                                    $coupons = $stmt->fetchAll();
                                    foreach ($coupons as $c):
                                        $is_expired = $c['valid_to'] < date('Y-m-d H:i:s');
                                        $is_full = $c['max_uses'] > 0 && $c['used_count'] >= $c['max_uses'];
                                ?>
                                <tr>
                                    <td><strong class="text-uppercase"><?php echo htmlspecialchars($c['code']); ?></strong></td>
                                    <td>
                                        <?php if ($c['discount_type'] === 'percentage'): ?>
                                            <?php echo $c['discount_value']; ?>%
                                        <?php else: ?>
                                            <?php echo formatMoney($c['discount_value']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?php echo $c['discount_type'] === 'percentage' ? 'info' : 'primary'; ?>"><?php echo ucfirst($c['discount_type']); ?></span></td>
                                    <td><small><?php echo formatDate($c['valid_from']); ?></small></td>
                                    <td><small><?php echo formatDate($c['valid_to']); ?></small></td>
                                    <td><?php echo $c['used_count']; ?>/<?php echo $c['max_uses'] ?: '∞'; ?></td>
                                    <td>
                                        <?php if ($is_expired): ?>
                                        <span class="badge bg-danger">Expired</span>
                                        <?php elseif ($is_full): ?>
                                        <span class="badge bg-warning">Fully Used</span>
                                        <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                                    endforeach;
                                    if (empty($coupons)):
                                ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No active coupons</td></tr>
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
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
