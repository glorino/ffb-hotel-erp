<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'Coupons & Offers';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$customer_id = $_SESSION['customer_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
if (!$customer_id) {
    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer_id = $stmt->fetchColumn();
    if ($customer_id) $_SESSION['customer_id'] = $customer_id;
}

$available = [];
try {
    $stmt = $db->query("SELECT c.*, (SELECT COUNT(*) FROM customer_coupons cc WHERE cc.coupon_id = c.id) as used_count FROM coupons c WHERE c.status = 'active' AND (c.expiry_date IS NULL OR c.expiry_date >= CURRENT_DATE) AND (c.max_usage IS NULL OR c.used_count < c.max_usage) ORDER BY c.expiry_date ASC");
    $available = $stmt->fetchAll();
} catch (Exception $e) {}

$used = [];
try {
    $stmt = $db->prepare("SELECT c.*, cc.used_at FROM customer_coupons cc JOIN coupons c ON cc.coupon_id = c.id WHERE cc.customer_id = ? ORDER BY cc.used_at DESC");
    $stmt->execute([$customer_id]);
    $used = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Coupons & Offers</li>
        </ol>
    </nav>

    <?php if (empty($available) && empty($used)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-5">
            <i class="bi bi-tags display-4 text-muted"></i>
            <h5 class="mt-3">No Coupons Available</h5>
            <p class="text-muted">There are no coupons or offers available right now. Check back later!</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($available): ?>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="fw-semibold mb-3"><i class="bi bi-tag-fill text-primary"></i> Available Coupons</h5>
        </div>
        <?php foreach ($available as $coupon): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 coupon-card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($coupon['code']); ?></span>
                            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($coupon['title'] ?? 'Special Offer'); ?></h6>
                        </div>
                        <div class="text-end">
                            <span class="fs-4 fw-bold text-success">
                                <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                <?php echo (int)$coupon['discount_value']; ?>%
                                <?php else: ?>
                                <?php echo formatMoney($coupon['discount_value']); ?>
                                <?php endif; ?>
                            </span>
                            <small class="d-block text-muted">OFF</small>
                        </div>
                    </div>
                    <?php if (!empty($coupon['description'])): ?>
                    <p class="small text-muted mb-2 flex-grow-1"><?php echo htmlspecialchars($coupon['description']); ?></p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <small class="text-muted">
                            <?php if ($coupon['expiry_date']): ?>
                            <i class="bi bi-clock"></i> Expires: <?php echo formatDate($coupon['expiry_date']); ?>
                            <?php else: ?>
                            No expiry
                            <?php endif; ?>
                        </small>
                        <button class="btn btn-sm btn-outline-primary copy-code" data-code="<?php echo htmlspecialchars($coupon['code']); ?>">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($used): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-check-circle text-secondary"></i> My Used Coupons</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Code</th><th>Title</th><th>Discount</th><th>Used On</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($used as $u): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($u['code']); ?></span></td>
                            <td><?php echo htmlspecialchars($u['title'] ?? '—'); ?></td>
                            <td>
                                <?php if ($u['discount_type'] === 'percentage'): ?>
                                <?php echo (int)$u['discount_value']; ?>%
                                <?php else: ?>
                                <?php echo formatMoney($u['discount_value']); ?>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo formatDateTime($u['used_at']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-code').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var code = this.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-success');
                setTimeout(function() {
                    btn.innerHTML = orig;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                }, 2000);
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
