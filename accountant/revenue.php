<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'Revenue Analysis';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND branch_id = " . (int)$branch_id : "";

$period = $_GET['period'] ?? 'monthly';
$year = $_GET['year'] ?? date('Y');

$revenue_by_source = [];
try {
    $stmt = $db->prepare("SELECT COALESCE(payment_category, 'other') as source, COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid' AND EXTRACT(YEAR FROM created_at) = ? $branch_filter GROUP BY payment_category");
    $stmt->execute([$year]);
    $revenue_by_source = $stmt->fetchAll();
} catch (Exception $e) {}

$revenue_by_method = [];
try {
    $stmt = $db->prepare("SELECT COALESCE(payment_method, 'unknown') as method, COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid' AND EXTRACT(YEAR FROM created_at) = ? $branch_filter GROUP BY payment_method");
    $stmt->execute([$year]);
    $revenue_by_method = $stmt->fetchAll();
} catch (Exception $e) {}

$comparison = [];
try {
    if ($period === 'monthly') {
        $stmt = $db->prepare("SELECT TO_CHAR(created_at, 'YYYY-MM') as label, COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid' AND EXTRACT(YEAR FROM created_at) = ? $branch_filter GROUP BY TO_CHAR(created_at, 'YYYY-MM') ORDER BY label");
    } elseif ($period === 'quarterly') {
        $stmt = $db->prepare("SELECT CONCAT(EXTRACT(YEAR FROM created_at)::text, '-Q', EXTRACT(QUARTER FROM created_at)::text) as label, COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid' AND EXTRACT(YEAR FROM created_at) = ? $branch_filter GROUP BY CONCAT(EXTRACT(YEAR FROM created_at)::text, '-Q', EXTRACT(QUARTER FROM created_at)::text) ORDER BY label");
    } else {
        $stmt = $db->prepare("SELECT TO_CHAR(created_at, 'YYYY') as label, COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid' $branch_filter GROUP BY TO_CHAR(created_at, 'YYYY') ORDER BY label");
    }
    $stmt->execute([$year]);
    $comparison = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Revenue Analysis</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Period</label>
                    <select name="period" class="form-select form-select-sm">
                        <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="quarterly" <?php echo $period === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                        <option value="yearly" <?php echo $period === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Year</label>
                    <select name="year" class="form-select form-select-sm">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo (int)$year === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Apply</button>
                    <a href="revenue.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Revenue by Source</h5>
                </div>
                <div class="card-body">
                    <canvas id="sourceChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Revenue by Payment Method</h5>
                </div>
                <div class="card-body">
                    <canvas id="methodChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold"><?php echo ucfirst($period); ?> Comparison</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold">Revenue by Source</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Source</th><th class="text-end">Amount</th><th class="text-end">%</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $src_total = array_sum(array_column($revenue_by_source, 'total'));
                                foreach ($revenue_by_source as $s):
                                $pct = $src_total > 0 ? ($s['total'] / $src_total * 100) : 0;
                                ?>
                                <tr>
                                    <td><span class="fw-medium"><?php echo htmlspecialchars(ucfirst($s['source'])); ?></span></td>
                                    <td class="text-end"><?php echo formatMoney($s['total']); ?></td>
                                    <td class="text-end"><?php echo number_format($pct, 1); ?>%</td>
                                </tr>
                                <?php endforeach; if (empty($revenue_by_source)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No data</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold"><?php echo ucfirst($period); ?> Revenue Trend</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Period</th><th class="text-end">Amount</th><th class="text-end">Change</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $prev = null;
                                foreach ($comparison as $c):
                                $change = $prev ? (($c['total'] - $prev) / $prev * 100) : 0;
                                $prev = $c['total'];
                                ?>
                                <tr>
                                    <td><span class="fw-medium"><?php echo htmlspecialchars($c['label']); ?></span></td>
                                    <td class="text-end"><?php echo formatMoney($c['total']); ?></td>
                                    <td class="text-end <?php echo $change >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo $change ? number_format($change, 1) . '%' : '—'; ?></td>
                                </tr>
                                <?php endforeach; if (empty($comparison)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No data</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('sourceChart'), {
        type: 'doughnut', data: {
            labels: <?php echo json_encode(array_map(function($s) { return ucfirst($s['source']); }, $revenue_by_source)); ?>,
            datasets: [{ data: <?php echo json_encode(array_map(function($s) { return (float)$s['total']; }, $revenue_by_source)); ?>, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6c757d','#0dcaf0'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '60%' }
    });

    new Chart(document.getElementById('methodChart'), {
        type: 'pie', data: {
            labels: <?php echo json_encode(array_map(function($m) { return ucfirst(str_replace('_', ' ', $m['method'])); }, $revenue_by_method)); ?>,
            datasets: [{ data: <?php echo json_encode(array_map(function($m) { return (float)$m['total']; }, $revenue_by_method)); ?>, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6c757d','#0dcafov'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'bar', data: {
            labels: <?php echo json_encode(array_map(function($c) { return $c['label']; }, $comparison)); ?>,
            datasets: [{ label: 'Revenue', data: <?php echo json_encode(array_map(function($c) { return (float)$c['total']; }, $comparison)); ?>, backgroundColor: 'rgba(13,110,253,0.7)', borderRadius: 4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } } } }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
