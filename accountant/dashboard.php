<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'Accountant Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND b.branch_id = " . (int)$branch_id : "";
$branch_filter_refunds = $branch_id ? "AND r.branch_id = " . (int)$branch_id : "";
$branch_filter_expenses = $branch_id ? "AND e.branch_id = " . (int)$branch_id : "";
?>
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Accountant Dashboard</li>
        </ol>
    </nav>

    <?php
    $stats = [];
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND DATE(p.created_at) = CURRENT_DATE $branch_filter");
        $stats['payments_today'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND EXTRACT(MONTH FROM p.created_at) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM p.created_at) = EXTRACT(YEAR FROM CURRENT_DATE) $branch_filter");
        $stats['payments_month'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' $branch_filter");
        $stats['payments_all'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.channel = 'online' AND p.status = 'paid' $branch_filter");
        $stats['online_payments'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE (p.channel = 'offline' OR p.channel IS NULL) AND p.status = 'paid' $branch_filter");
        $stats['offline_payments'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'pending' $branch_filter");
        $stats['pending_payments'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'completed' $branch_filter_refunds");
        $stats['refunds_issued'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM expenses $branch_filter_expenses");
        $stats['total_expenses'] = $stmt->fetchColumn();
        $stats['net_revenue'] = ($stats['payments_all'] ?? 0) - ($stats['refunds_issued'] ?? 0) - ($stats['total_expenses'] ?? 0);
    } catch (Exception $e) {
        error_log('Accountant dashboard error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Today's Payments</p>
                            <h3 class="stat-value mb-0 text-success"><?php echo formatMoney($stats['payments_today'] ?? 0); ?></h3>
                            <small class="text-muted"><?php echo date('M d, Y'); ?></small>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-3"><i class="bi bi-cash-stack text-success fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">This Month</p>
                            <h3 class="stat-value mb-0 text-primary"><?php echo formatMoney($stats['payments_month'] ?? 0); ?></h3>
                            <small class="text-muted"><?php echo date('F Y'); ?></small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-3"><i class="bi bi-calendar-range text-primary fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Online Payments</p>
                            <h3 class="stat-value mb-0 text-info"><?php echo formatMoney($stats['online_payments'] ?? 0); ?></h3>
                            <small class="text-muted">Flutterwave & gateways</small>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3"><i class="bi bi-globe text-info fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Offline Payments</p>
                            <h3 class="stat-value mb-0 text-warning"><?php echo formatMoney($stats['offline_payments'] ?? 0); ?></h3>
                            <small class="text-muted">Cash, POS, Transfer</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3"><i class="bi bi-wallet2 text-warning fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Pending Payments</p>
                            <h3 class="stat-value mb-0 text-danger"><?php echo formatMoney($stats['pending_payments'] ?? 0); ?></h3>
                            <small class="text-muted">Awaiting confirmation</small>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-3"><i class="bi bi-hourglass-split text-danger fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Refunds Issued</p>
                            <h3 class="stat-value mb-0 text-secondary"><?php echo formatMoney($stats['refunds_issued'] ?? 0); ?></h3>
                            <small class="text-muted">Total refunded</small>
                        </div>
                        <div class="stat-icon bg-secondary-subtle rounded-3 p-3"><i class="bi bi-arrow-return-left text-secondary fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Total Expenses</p>
                            <h3 class="stat-value mb-0 text-danger"><?php echo formatMoney($stats['total_expenses'] ?? 0); ?></h3>
                            <small class="text-muted">All time</small>
                        </div>
                        <div class="stat-icon bg-danger-subtle rounded-3 p-3"><i class="bi bi-cart-dash text-danger fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Net Revenue</p>
                            <h3 class="stat-value mb-0 text-primary"><?php echo formatMoney($stats['net_revenue'] ?? 0); ?></h3>
                            <small class="text-muted">After refunds & expenses</small>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-3"><i class="bi bi-pie-chart text-primary fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Revenue vs Expenses</h5>
                    <small class="text-muted">Last 12 months</small>
                </div>
                <div class="card-body">
                    <canvas id="revenueExpenseChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Monthly Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Recent Transactions</h5>
                    <a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Reference</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("SELECT p.*, c.full_name FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id LEFT JOIN customers c ON p.customer_id = c.id WHERE 1=1 $branch_filter ORDER BY p.created_at DESC LIMIT 15");
                                    $txns = $stmt->fetchAll();
                                    foreach ($txns as $t):
                                ?>
                                <tr>
                                    <td><small class="fw-medium"><?php echo htmlspecialchars($t['reference'] ?? 'PAY-' . $t['id']); ?></small></td>
                                    <td><?php echo htmlspecialchars($t['full_name'] ?? '—'); ?></td>
                                    <td><strong><?php echo formatMoney($t['amount']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $t['payment_method'] ?? '—'))); ?></td>
                                    <td><?php echo getPaymentStatusBadge($t['status']); ?></td>
                                    <td><small class="text-muted"><?php echo timeAgo($t['created_at']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($txns)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No transactions found</td></tr>
                                <?php endif; ?>
                                <?php } catch (Exception $e) {
                                    echo '<tr><td colspan="6" class="text-center py-4 text-danger">Error loading data</td></tr>';
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Pending Reconciliations</h5>
                    <a href="flutterwave-transactions.php" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Reference</th><th>Amount</th><th>Date</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("SELECT * FROM flutterwave_transactions WHERE reconciliation_status != 'reconciled' OR reconciliation_status IS NULL ORDER BY created_at DESC LIMIT 10");
                                    $recs = $stmt->fetchAll();
                                    foreach ($recs as $r):
                                ?>
                                <tr>
                                    <td><small><?php echo htmlspecialchars($r['reference'] ?? '—'); ?></small></td>
                                    <td><?php echo formatMoney($r['amount']); ?></td>
                                    <td><small class="text-muted"><?php echo timeAgo($r['created_at']); ?></small></td>
                                    <td><span class="badge bg-warning"><?php echo htmlspecialchars(ucfirst($r['reconciliation_status'] ?? 'pending')); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recs)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">All reconciled</td></tr>
                                <?php endif; ?>
                                <?php } catch (Exception $e) {
                                    echo '<tr><td colspan="4" class="text-center py-4 text-danger">Error loading data</td></tr>';
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$rev_months = []; $exp_months = []; $month_labels = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $month_labels[] = date('M y', strtotime($m));
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND TO_CHAR(created_at, 'YYYY-MM') = ?");
        $stmt->execute([$m]);
        $rev_months[] = (float) $stmt->fetchColumn();
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE TO_CHAR(created_at, 'YYYY-MM') = ?");
        $stmt->execute([$m]);
        $exp_months[] = (float) $stmt->fetchColumn();
    } catch (Exception $e) { $rev_months[] = 0; $exp_months[] = 0; }
}

$pmt_methods = [];
try {
    $stmt = $db->query("SELECT COALESCE(payment_method, 'unknown') as method, COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid' GROUP BY payment_method");
    $pmt_methods = $stmt->fetchAll();
} catch (Exception $e) {}

$monthly_rev = [];
try {
    $stmt = $db->query("SELECT TO_CHAR(created_at, 'YYYY-MM') as month, COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid' GROUP BY TO_CHAR(created_at, 'YYYY-MM') ORDER BY month ASC LIMIT 12");
    $monthly_rev = $stmt->fetchAll();
} catch (Exception $e) {}
$mr_labels = []; $mr_data = [];
foreach ($monthly_rev as $mr) {
    $mr_labels[] = date('M y', strtotime($mr['month'] . '-01'));
    $mr_data[] = (float) $mr['total'];
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('revenueExpenseChart'), {
        type: 'bar', data: {
            labels: <?php echo json_encode($month_labels); ?>,
            datasets: [
                { label: 'Revenue', data: <?php echo json_encode($rev_months); ?>, backgroundColor: 'rgba(13,110,253,0.7)', borderRadius: 4 },
                { label: 'Expenses', data: <?php echo json_encode($exp_months); ?>, backgroundColor: 'rgba(220,53,69,0.7)', borderRadius: 4 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } } } }
    });

    new Chart(document.getElementById('paymentMethodChart'), {
        type: 'doughnut', data: {
            labels: <?php echo json_encode(array_map(function($p) { return ucfirst(str_replace('_', ' ', $p['method'])); }, $pmt_methods)); ?>,
            datasets: [{ data: <?php echo json_encode(array_map(function($p) { return (float)$p['total']; }, $pmt_methods)); ?>, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6c757d','#0dcaf0'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '65%' }
    });

    new Chart(document.getElementById('monthlyTrendChart'), {
        type: 'line', data: {
            labels: <?php echo json_encode($mr_labels ?: $month_labels); ?>,
            datasets: [{ label: 'Revenue', data: <?php echo json_encode($mr_data ?: $rev_months); ?>, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', fill: true, tension: 0.4, pointRadius: 3 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } } } }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
