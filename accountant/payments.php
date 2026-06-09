<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'All Payments';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/accountant-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND p.branch_id = " . (int)$branch_id : "";

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$method = $_GET['method'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = "1=1";
$params = [];
if ($date_from) { $where .= " AND DATE(p.created_at) >= ?"; $params[] = $date_from; }
if ($date_to) { $where .= " AND DATE(p.created_at) <= ?"; $params[] = $date_to; }
if ($method) { $where .= " AND p.payment_method = ?"; $params[] = $method; }
if ($status) { $where .= " AND p.status = ?"; $params[] = $status; }
if ($search) { $where .= " AND (p.reference LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; }

if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=payments_export_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Reference', 'Customer', 'Amount', 'Method', 'Category', 'Status', 'Date', 'Recorded By']);
    $q = $db->prepare("SELECT p.*, c.first_name, c.last_name, u.first_name as uf, u.last_name as ul FROM payments p LEFT JOIN customers c ON p.customer_id = c.id LEFT JOIN users u ON p.recorded_by = u.id WHERE $where $branch_filter ORDER BY p.created_at DESC");
    $q->execute($params);
    while ($row = $q->fetch()) {
        fputcsv($output, [
            $row['reference'] ?? 'PAY-' . $row['id'],
            ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''),
            number_format($row['amount'], 2),
            ucfirst(str_replace('_', ' ', $row['payment_method'] ?? '')),
            $row['payment_category'] ?? '',
            $row['status'],
            $row['created_at'],
            ($row['uf'] ?? '') . ' ' . ($row['ul'] ?? '')
        ]);
    }
    fclose($output);
    exit;
}

$per_page = 25;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $per_page;

$count_q = $db->prepare("SELECT COUNT(*) FROM payments p LEFT JOIN customers c ON p.customer_id = c.id WHERE $where $branch_filter");
$count_q->execute($params);
$total_records = $count_q->fetchColumn();
$total_pages = ceil($total_records / $per_page);

$stmt = $db->prepare("SELECT p.*, c.first_name, c.last_name, c.email as cust_email, u.first_name as uf, u.last_name as ul FROM payments p LEFT JOIN customers c ON p.customer_id = c.id LEFT JOIN users u ON p.recorded_by = u.id WHERE $where $branch_filter ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Payments</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small">Method</label>
                    <select name="method" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="flutterwave" <?php echo $method === 'flutterwave' ? 'selected' : ''; ?>>Flutterwave</option>
                        <option value="cash" <?php echo $method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="pos" <?php echo $method === 'pos' ? 'selected' : ''; ?>>POS</option>
                        <option value="bank_transfer" <?php echo $method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="split_payment" <?php echo $method === 'split_payment' ? 'selected' : ''; ?>>Split Payment</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        <option value="partially_paid" <?php echo $status === 'partially_paid' ? 'selected' : ''; ?>>Partially Paid</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Reference or customer" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-lg-2 col-md-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="payments.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                    <a href="?export_csv=1&<?php echo http_build_query($_GET); ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">Payments</h5>
            <span class="badge bg-secondary"><?php echo $total_records; ?> records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Reference</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Recorded By</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><small class="fw-medium"><?php echo htmlspecialchars($p['reference'] ?? 'PAY-' . $p['id']); ?></small></td>
                            <td><?php echo htmlspecialchars(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '—')); ?><br><small class="text-muted"><?php echo htmlspecialchars($p['cust_email'] ?? ''); ?></small></td>
                            <td><strong><?php echo formatMoney($p['amount']); ?></strong></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $p['payment_method'] ?? '—'))); ?></td>
                            <td><?php echo getPaymentStatusBadge($p['status']); ?></td>
                            <td><small class="text-muted"><?php echo formatDateTime($p['created_at']); ?></small></td>
                            <td><small><?php echo htmlspecialchars(($p['uf'] ?? '') . ' ' . ($p['ul'] ?? '—')); ?></small></td>
                        </tr>
                        <?php endforeach; if (empty($payments)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No payments found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page - 1])); ?>">Previous</a></li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page + 1])); ?>">Next</a></li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
