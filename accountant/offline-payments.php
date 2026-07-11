<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'Offline Payments';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND p.branch_id = " . (int)$branch_id : "";

if (isset($_GET['confirm']) && (int)$_GET['confirm']) {
    try {
        $stmt = $db->prepare("UPDATE payments SET status = 'paid', confirmed_at = NOW(), confirmed_by = ? WHERE id = ? AND (channel = 'offline' OR channel IS NULL) AND status = 'pending'");
        $stmt->execute([$_SESSION['user_id'], (int)$_GET['confirm']]);
        if ($stmt->rowCount()) {
            set_flash('success', 'Payment confirmed successfully');
        } else {
            set_flash('warning', 'Payment already confirmed or not found');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: offline-payments.php'); exit;
}

$method_filter = $_GET['method'] ?? '';
$status_filter = $_GET['status'] ?? '';
$where = "(p.channel = 'offline' OR p.channel IS NULL)"; $params = [];
if ($method_filter) { $where .= " AND p.payment_method = ?"; $params[] = $method_filter; }
if ($status_filter) { $where .= " AND p.status = ?"; $params[] = $status_filter; }
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Offline Payments</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Method</label>
                    <select name="method" class="form-select form-select-sm">
                        <option value="">All Methods</option>
                        <option value="cash" <?php echo $method_filter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="pos" <?php echo $method_filter === 'pos' ? 'selected' : ''; ?>>POS</option>
                        <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="offline-payments.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Offline Payments (Cash, POS, Bank Transfer)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Reference</th><th>Method</th><th>Customer</th><th>Amount</th><th>Status</th><th>Receipt</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $st = $db->prepare("SELECT p.*, c.full_name FROM payments p LEFT JOIN customers c ON p.customer_id = c.id WHERE $where $branch_filter ORDER BY p.created_at DESC");
                            $st->execute($params);
                            $payments = $st->fetchAll();
                            foreach ($payments as $p):
                        ?>
                        <tr>
                            <td><small class="fw-medium"><?php echo htmlspecialchars($p['reference'] ?? 'PAY-' . $p['id']); ?></small></td>
                            <td>
                                <?php
                                $icon = ['cash'=>'bi-cash','pos'=>'bi-credit-card-2-front','bank_transfer'=>'bi-bank'];
                                $ic = $icon[$p['payment_method']] ?? 'bi-wallet2';
                                echo "<i class='bi {$ic} me-1'></i> " . htmlspecialchars(ucfirst(str_replace('_', ' ', $p['payment_method'] ?? '—')));
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($p['full_name'] ?? '—'); ?></td>
                            <td><strong><?php echo formatMoney($p['amount']); ?></strong></td>
                            <td><?php echo getPaymentStatusBadge($p['status']); ?></td>
                            <td>
                                <?php if (!empty($p['receipt_url'])): ?>
                                <a href="<?php echo $base_url . htmlspecialchars($p['receipt_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo timeAgo($p['created_at']); ?></small></td>
                            <td>
                                <?php if ($p['status'] === 'pending'): ?>
                                <a href="?confirm=<?php echo $p['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Confirm this payment of <?php echo formatMoney($p['amount']); ?>?')"><i class="bi bi-check-lg"></i> Confirm</a>
                                <?php else: ?>
                                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle"></i> Confirmed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No offline payments found</td></tr>
                        <?php endif; ?>
                        <?php } catch (Exception $e) {
                            echo '<tr><td colspan="8" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
