<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'Financial Reports';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND b.branch_id = " . (int)$branch_id : "";
$branch_filter_refunds = $branch_id ? "AND r.branch_id = " . (int)$branch_id : "";
$branch_filter_expenses = $branch_id ? "AND e.branch_id = " . (int)$branch_id : "";

$report_type = $_GET['report'] ?? 'profit_loss';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$date_where = "DATE(created_at) >= ? AND DATE(created_at) <= ?";

if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $report_type . '_' . $date_from . '_to_' . $date_to . '.csv');
    $output = fopen('php://output', 'w');
    switch ($report_type) {
        case 'profit_loss':
            fputcsv($output, ['Category', 'Amount']);
            $rev = $db->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND $date_where $branch_filter");
            $rev->execute([$date_from, $date_to]); $r = $rev->fetchColumn();
            fputcsv($output, ['Total Revenue', $r]);
            $exp = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $date_where $branch_filter_expenses");
            $exp->execute([$date_from, $date_to]); $e = $exp->fetchColumn();
            fputcsv($output, ['Total Expenses', $e]);
            $ref = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'completed' AND $date_where $branch_filter_refunds");
            $ref->execute([$date_from, $date_to]); $rf = $ref->fetchColumn();
            fputcsv($output, ['Refunds', $rf]);
            fputcsv($output, ['Net Profit/Loss', $r - $e - $rf]);
            break;
        case 'revenue':
            fputcsv($output, ['Date', 'Amount', 'Method', 'Category', 'Reference']);
            $st = $db->prepare("SELECT p.created_at, p.amount, p.payment_method, p.payment_category, p.reference FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND $date_where $branch_filter ORDER BY p.created_at");
            $st->execute([$date_from, $date_to]);
            while ($row = $st->fetch()) { fputcsv($output, $row); }
            break;
        case 'expense':
            fputcsv($output, ['Title', 'Category', 'Amount', 'Date', 'Description']);
            $st = $db->prepare("SELECT title, category, amount, created_at, description FROM expenses WHERE $date_where $branch_filter_expenses ORDER BY created_at");
            $st->execute([$date_from, $date_to]);
            while ($row = $st->fetch()) { fputcsv($output, $row); }
            break;
        case 'tax':
            fputcsv($output, ['Category', 'Revenue', 'Estimated Tax (7.5%)']);
            $st = $db->prepare("SELECT COALESCE(p.payment_category, 'other') as cat, COALESCE(SUM(p.amount), 0) as total FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND $date_where $branch_filter GROUP BY p.payment_category");
            $st->execute([$date_from, $date_to]);
            while ($row = $st->fetch()) { fputcsv($output, [$row['cat'], $row['total'], $row['total'] * 0.075]); }
            break;
    }
    fclose($output); exit;
}

$data = [];
try {
    switch ($report_type) {
        case 'profit_loss':
            $stmt = $db->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND $date_where $branch_filter");
            $stmt->execute([$date_from, $date_to]); $data['revenue'] = $stmt->fetchColumn();
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $date_where $branch_filter_expenses");
            $stmt->execute([$date_from, $date_to]); $data['expenses'] = $stmt->fetchColumn();
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'completed' AND $date_where $branch_filter_refunds");
            $stmt->execute([$date_from, $date_to]); $data['refunds'] = $stmt->fetchColumn();
            $data['net_profit'] = $data['revenue'] - $data['expenses'] - $data['refunds'];

            $stmt = $db->prepare("SELECT COALESCE(p.payment_category, 'other') as cat, COALESCE(SUM(p.amount), 0) as total FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND $date_where $branch_filter GROUP BY p.payment_category ORDER BY total DESC");
            $stmt->execute([$date_from, $date_to]);
            $data['revenue_by_source'] = $stmt->fetchAll();
            break;

        case 'balance_sheet':
            $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' $branch_filter");
            $data['total_receivables'] = $stmt->fetchColumn();
            $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'pending' $branch_filter");
            $data['pending_receivables'] = $stmt->fetchColumn();
            $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM expenses $branch_filter_expenses");
            $data['total_liabilities'] = $stmt->fetchColumn();
            $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status IN ('unpaid','overdue')");
            $data['outstanding_invoices'] = $stmt->fetchColumn();
            $data['equity'] = $data['total_receivables'] - $data['total_liabilities'];
            break;

        case 'revenue':
            $stmt = $db->prepare("SELECT DATE(p.created_at) as dt, COALESCE(SUM(p.amount), 0) as total FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND $date_where $branch_filter GROUP BY DATE(p.created_at) ORDER BY dt");
            $stmt->execute([$date_from, $date_to]);
            $data['daily'] = $stmt->fetchAll();
            $stmt = $db->prepare("SELECT COALESCE(p.payment_method, 'unknown') as method, COALESCE(SUM(p.amount), 0) as total FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND $date_where $branch_filter GROUP BY p.payment_method");
            $stmt->execute([$date_from, $date_to]);
            $data['by_method'] = $stmt->fetchAll();
            break;

        case 'expense':
            $stmt = $db->prepare("SELECT category, COALESCE(SUM(amount), 0) as total, COUNT(*) as cnt FROM expenses WHERE $date_where $branch_filter_expenses GROUP BY category ORDER BY total DESC");
            $stmt->execute([$date_from, $date_to]);
            $data['by_category'] = $stmt->fetchAll();
            break;

        case 'tax':
            $stmt = $db->prepare("SELECT COALESCE(p.payment_category, 'other') as cat, COALESCE(SUM(p.amount), 0) as total FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.status = 'paid' AND $date_where $branch_filter GROUP BY p.payment_category");
            $stmt->execute([$date_from, $date_to]);
            $data['by_source'] = $stmt->fetchAll();
            $data['vat_rate'] = 0.075;
            break;
    }
} catch (Exception $e) {
    set_flash('danger', 'Error loading report: ' . $e->getMessage());
}
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Financial Reports</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small">Report Type</label>
                    <select name="report" class="form-select form-select-sm">
                        <option value="profit_loss" <?php echo $report_type === 'profit_loss' ? 'selected' : ''; ?>>Profit & Loss</option>
                        <option value="balance_sheet" <?php echo $report_type === 'balance_sheet' ? 'selected' : ''; ?>>Balance Sheet</option>
                        <option value="revenue" <?php echo $report_type === 'revenue' ? 'selected' : ''; ?>>Revenue Report</option>
                        <option value="expense" <?php echo $report_type === 'expense' ? 'selected' : ''; ?>>Expense Report</option>
                        <option value="tax" <?php echo $report_type === 'tax' ? 'selected' : ''; ?>>Tax Report</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-lg-3 col-md-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Generate</button>
                    <a href="?export_csv=1&report=<?php echo $report_type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i> Export CSV</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($report_type === 'profit_loss'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card h-100 border-start border-success border-4">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Total Revenue</p>
                    <h3 class="stat-value mb-0 text-success"><?php echo formatMoney($data['revenue'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card h-100 border-start border-danger border-4">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Total Expenses</p>
                    <h3 class="stat-value mb-0 text-danger"><?php echo formatMoney($data['expenses'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card h-100 border-start border-<?php echo ($data['net_profit'] ?? 0) >= 0 ? 'primary' : 'danger'; ?> border-4">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1">Net Profit / Loss</p>
                    <h3 class="stat-value mb-0 <?php echo ($data['net_profit'] ?? 0) >= 0 ? 'text-primary' : 'text-danger'; ?>"><?php echo formatMoney(abs($data['net_profit'] ?? 0)); ?></h3>
                    <small class="<?php echo ($data['net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($data['net_profit'] ?? 0) >= 0 ? 'Profit' : 'Loss'; ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Profit & Loss Summary</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr class="table-success"><td class="fw-semibold ps-4">Total Revenue</td><td class="text-end pe-4"><?php echo formatMoney($data['revenue'] ?? 0); ?></td></tr>
                        <tr><td class="ps-5"><?php
                            foreach ($data['revenue_by_source'] ?? [] as $src) {
                                echo htmlspecialchars(ucfirst($src['cat'])) . ': ' . formatMoney($src['total']) . '<br>';
                            }
                        ?></td><td></td></tr>
                        <tr class="table-danger"><td class="fw-semibold ps-4">Total Expenses</td><td class="text-end pe-4">-<?php echo formatMoney($data['expenses'] ?? 0); ?></td></tr>
                        <tr class="table-secondary"><td class="fw-semibold ps-4">Refunds</td><td class="text-end pe-4">-<?php echo formatMoney($data['refunds'] ?? 0); ?></td></tr>
                        <tr class="<?php echo ($data['net_profit'] ?? 0) >= 0 ? 'table-primary' : 'table-warning'; ?>">
                            <td class="fw-bold ps-4 fs-5"><?php echo ($data['net_profit'] ?? 0) >= 0 ? 'Net Profit' : 'Net Loss'; ?></td>
                            <td class="text-end pe-4 fw-bold fs-5"><?php echo formatMoney(abs($data['net_profit'] ?? 0)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Revenue by Source</h5></div>
        <div class="card-body">
            <canvas id="reportChart" height="280"></canvas>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('reportChart'), {
            type: 'bar', data: {
                labels: <?php echo json_encode(array_map(function($s) { return ucfirst($s['cat']); }, $data['revenue_by_source'] ?? [])); ?>,
                datasets: [{ label: 'Revenue', data: <?php echo json_encode(array_map(function($s) { return (float)$s['total']; }, $data['revenue_by_source'] ?? [])); ?>, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6c757d'], borderRadius: 4 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } } } }
        });
    });
    </script>

    <?php elseif ($report_type === 'balance_sheet'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Assets</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr><td class="ps-4">Paid Receivables</td><td class="text-end pe-4"><?php echo formatMoney($data['total_receivables'] ?? 0); ?></td></tr>
                                <tr><td class="ps-4">Pending Receivables</td><td class="text-end pe-4"><?php echo formatMoney($data['pending_receivables'] ?? 0); ?></td></tr>
                                <tr class="table-primary"><td class="fw-bold ps-4">Total Assets</td><td class="text-end pe-4 fw-bold"><?php echo formatMoney(($data['total_receivables'] ?? 0) + ($data['pending_receivables'] ?? 0)); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Liabilities & Equity</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr><td class="ps-4">Total Expenses (Liabilities)</td><td class="text-end pe-4"><?php echo formatMoney($data['total_liabilities'] ?? 0); ?></td></tr>
                                <tr><td class="ps-4">Outstanding Invoices</td><td class="text-end pe-4"><?php echo formatMoney($data['outstanding_invoices'] ?? 0); ?></td></tr>
                                <tr class="table-success"><td class="fw-bold ps-4">Equity</td><td class="text-end pe-4 fw-bold"><?php echo formatMoney($data['equity'] ?? 0); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($report_type === 'revenue'): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Daily Revenue</h5></div>
        <div class="card-body">
            <canvas id="reportChart" height="280"></canvas>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('reportChart'), {
            type: 'line', data: {
                labels: <?php echo json_encode(array_map(function($d) { return $d['dt']; }, $data['daily'] ?? [])); ?>,
                datasets: [{ label: 'Revenue', data: <?php echo json_encode(array_map(function($d) { return (float)$d['total']; }, $data['daily'] ?? [])); ?>, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', fill: true, tension: 0.4 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + v.toLocaleString() } } } }
        });
    });
    </script>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Revenue by Method</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Method</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['by_method'] ?? [] as $m): ?>
                        <tr><td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $m['method']))); ?></td><td class="text-end"><?php echo formatMoney($m['total']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($report_type === 'expense'): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Expenses by Category</h5></div>
        <div class="card-body">
            <canvas id="reportChart" height="280"></canvas>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('reportChart'), {
            type: 'doughnut', data: {
                labels: <?php echo json_encode(array_map(function($e) { return ucfirst(str_replace('_', ' ', $e['category'])); }, $data['by_category'] ?? [])); ?>,
                datasets: [{ data: <?php echo json_encode(array_map(function($e) { return (float)$e['total']; }, $data['by_category'] ?? [])); ?>, backgroundColor: ['#dc3545','#fd7e14','#ffc107','#198754','#0dcaf0','#0d6efd','#6c757d','#d63384'], borderWidth: 2 }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
        });
    });
    </script>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Expense Details</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Category</th><th class="text-end">Count</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['by_category'] ?? [] as $e): ?>
                        <tr><td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $e['category']))); ?></td><td class="text-end"><?php echo $e['cnt']; ?></td><td class="text-end text-danger"><?php echo formatMoney($e['total']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($report_type === 'tax'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Tax Report (VAT <?php echo ($data['vat_rate'] ?? 7.5) * 100; ?>%)</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Revenue Source</th><th class="text-end">Revenue</th><th class="text-end">Estimated VAT</th></tr>
                    </thead>
                    <tbody>
                        <?php $tot_rev = 0; $tot_tax = 0; foreach ($data['by_source'] ?? [] as $s):
                            $tax = $s['total'] * ($data['vat_rate'] ?? 0);
                            $tot_rev += $s['total']; $tot_tax += $tax;
                        ?>
                        <tr><td><?php echo htmlspecialchars(ucfirst($s['cat'])); ?></td><td class="text-end"><?php echo formatMoney($s['total']); ?></td><td class="text-end"><?php echo formatMoney($tax); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr><td>Total</td><td class="text-end"><?php echo formatMoney($tot_rev); ?></td><td class="text-end"><?php echo formatMoney($tot_tax); ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-muted small">
            <i class="bi bi-info-circle"></i> VAT is calculated at <?php echo ($data['vat_rate'] ?? 7.5) * 100; ?>% of revenue. This is an estimate; consult your tax professional.
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
