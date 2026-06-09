<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Payments';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/owner-sidebar.php';

$db = getDB();
$method = $_GET['method'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
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
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2">
                    <select name="method" class="form-select form-select-sm">
                        <option value="">All Methods</option>
                        <option value="flutterwave" <?php echo $method === 'flutterwave' ? 'selected' : ''; ?>>Flutterwave</option>
                        <option value="cash" <?php echo $method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="pos" <?php echo $method === 'pos' ? 'selected' : ''; ?>>POS</option>
                        <option value="bank_transfer" <?php echo $method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="split_payment" <?php echo $method === 'split_payment' ? 'selected' : ''; ?>>Split Payment</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="partially_paid" <?php echo $status_filter === 'partially_paid' ? 'selected' : ''; ?>>Partially Paid</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    $total_paid = 0;
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'");
        $total_paid = (float) $stmt->fetchColumn();
    } catch (Exception $e) {}
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold mb-0">All Payments</h5>
        <span class="text-muted">Total Collected: <strong><?php echo formatMoney($total_paid); ?></strong></span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Channel</th>
                            <th>Receipt</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "
                                SELECT p.*, c.full_name as customer_name
                                FROM payments p
                                LEFT JOIN customers c ON p.customer_id = c.id
                                WHERE 1=1
                            ";
                            $params = [];
                            if ($method) { $sql .= " AND p.method = ?"; $params[] = $method; }
                            if ($status_filter) { $sql .= " AND p.status = ?"; $params[] = $status_filter; }
                            if ($date_from) { $sql .= " AND DATE(p.created_at) >= ?"; $params[] = $date_from; }
                            if ($date_to) { $sql .= " AND DATE(p.created_at) <= ?"; $params[] = $date_to; }
                            $sql .= " ORDER BY p.created_at DESC LIMIT 200";

                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $payments = $stmt->fetchAll();

                            if (empty($payments)):
                        ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No payments found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><small class="fw-medium"><?php echo htmlspecialchars($p['payment_reference']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['customer_name'] ?? 'N/A'); ?></td>
                            <td class="fw-semibold"><?php echo formatMoney($p['amount']); ?></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($p['method']); ?></span></td>
                            <td><?php echo getPaymentStatusBadge($p['status']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p['channel']); ?></span></td>
                            <td><small><?php echo htmlspecialchars($p['receipt_number'] ?? 'N/A'); ?></small></td>
                            <td><small class="text-muted"><?php echo formatDateTime($p['created_at']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr><td colspan="8" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
