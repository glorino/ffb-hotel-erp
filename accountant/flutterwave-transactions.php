<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'Flutterwave Transactions';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = "";

if (isset($_GET['verify']) && (int)$_GET['verify']) {
    $txn_id = (int)$_GET['verify'];
    try {
        $stmt = $db->prepare("SELECT * FROM paystack_transactions WHERE id = ?");
        $stmt->execute([$txn_id]);
        $txn = $stmt->fetch();
        if ($txn && $txn['reference']) {
            $secret_key = getSetting('flutterwave_secret_key', '');
            if ($secret_key) {
                $ch = curl_init("https://api.flutterwave.com/v3/transactions/verify/" . $txn['paystack_reference']);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => ["Authorization: Bearer $secret_key", "Content-Type: application/json"],
                    CURLOPT_TIMEOUT => 30
                ]);
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($http_code === 200) {
                    $result = json_decode($response, true);
                    if ($result['status'] === 'success') {
                        $data = $result['data'];
                        $new_status = $data['status'] === 'successful' ? 'paid' : ($data['status'] === 'failed' ? 'failed' : 'pending');
                        $stmt = $db->prepare("UPDATE paystack_transactions SET status = ?, amount = ?, verified_at = NOW(), reconciliation_status = 'verified' WHERE id = ?");
                        $stmt->execute([$new_status, $data['amount'] / 100, $txn_id]);
                        if ($new_status === 'paid') {
                            $stmt = $db->prepare("UPDATE payments SET status = 'paid', paystack_reference = ? WHERE reference = ?");
                            $stmt->execute([$txn['paystack_reference'], $txn['reference']]);
                        }
                        set_flash('success', 'Transaction verified successfully. Status: ' . $new_status);
                    } else {
                        set_flash('warning', 'Flutterwave verification returned: ' . ($result['message'] ?? 'Unknown'));
                    }
                } else {
                    set_flash('danger', 'Failed to connect to Flutterwave API');
                }
            } else {
                set_flash('danger', 'Flutterwave secret key not configured');
            }
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: flutterwave-transactions.php'); exit;
}

if (isset($_GET['reconcile']) && (int)$_GET['reconcile']) {
    try {
        $stmt = $db->prepare("UPDATE paystack_transactions SET reconciliation_status = 'reconciled', reconciled_at = NOW(), reconciled_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], (int)$_GET['reconcile']]);
        set_flash('success', 'Transaction reconciled');
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: flutterwave-transactions.php'); exit;
}

$status_filter = $_GET['status'] ?? '';
$recon_filter = $_GET['reconciliation'] ?? '';
$where = "1=1"; $params = [];
if ($status_filter) { $where .= " AND pt.status = ?"; $params[] = $status_filter; }
if ($recon_filter) { $where .= " AND (pt.reconciliation_status = ? OR (pt.reconciliation_status IS NULL AND ? = 'pending'))"; $params[] = $recon_filter; $params[] = $recon_filter; }
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Flutterwave Transactions</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Reconciliation</label>
                    <select name="reconciliation" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending" <?php echo $recon_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="verified" <?php echo $recon_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="reconciled" <?php echo $recon_filter === 'reconciled' ? 'selected' : ''; ?>>Reconciled</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="flutterwave-transactions.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Flutterwave Transactions</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Reference</th><th>Gateway Ref</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th><th>Reconciliation</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $st = $db->prepare("SELECT pt.*, c.full_name FROM paystack_transactions pt LEFT JOIN payments pm ON pt.reference = pm.reference LEFT JOIN customers c ON pm.customer_id = c.id WHERE $where $branch_filter ORDER BY pt.created_at DESC");
                            $st->execute($params);
                            $txns = $st->fetchAll();
                            foreach ($txns as $t):
                        ?>
                        <tr>
                            <td><small class="fw-medium"><?php echo htmlspecialchars($t['reference'] ?? '—'); ?></small></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($t['paystack_reference'] ?? '—'); ?></small></td>
                            <td><?php echo htmlspecialchars($t['full_name'] ?? '—'); ?></td>
                            <td><strong><?php echo formatMoney($t['amount']); ?></strong></td>
                            <td><?php echo getPaymentStatusBadge($t['status']); ?></td>
                            <td><small class="text-muted"><?php echo timeAgo($t['created_at']); ?></small></td>
                            <td>
                                <?php
                                $r_status = $t['reconciliation_status'] ?? 'pending';
                                $r_badge = ['reconciled' => 'success', 'verified' => 'info', 'pending' => 'warning'];
                                $rc = $r_badge[$r_status] ?? 'secondary';
                                echo "<span class='badge bg-{$rc}'>" . htmlspecialchars(ucfirst($r_status)) . "</span>";
                                ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="?verify=<?php echo $t['id']; ?>" class="btn btn-outline-primary" title="Verify with Flutterwave"><i class="bi bi-arrow-repeat"></i></a>
                                    <?php if ($r_status !== 'reconciled'): ?>
                                    <a href="?reconcile=<?php echo $t['id']; ?>" class="btn btn-outline-success" title="Mark Reconciled" onclick="return confirm('Mark this transaction as reconciled?')"><i class="bi bi-check-lg"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($txns)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No Flutterwave transactions found</td></tr>
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
