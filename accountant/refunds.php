<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'Refunds';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/accountant-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND r.branch_id = " . (int)$branch_id : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_refund'])) {
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    try {
        $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND status = 'paid'");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch();
        if (!$payment) {
            set_flash('danger', 'Original payment not found or not paid');
        } else {
            $db->beginTransaction();
            $ref = $db->prepare("INSERT INTO refunds (payment_id, customer_id, branch_id, amount, reason, status, processed_by, created_at) VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW())");
            $ref->execute([$payment_id, $payment['customer_id'], $branch_id ?: null, $amount, $reason, $_SESSION['user_id']]);
            $up = $db->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
            $up->execute([$payment_id]);
            if ($payment['booking_id']) {
                $bk = $db->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
                $bk->execute([$payment['booking_id']]);
            }
            $db->commit();
            log_audit('process_refund', 'refund', $ref->rowCount());
            set_flash('success', "Refund of " . formatMoney($amount) . " processed successfully");
        }
    } catch (Exception $e) {
        $db->rollBack();
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: refunds.php'); exit;
}

$status_filter = $_GET['status'] ?? '';
$where = "1=1"; $params = [];
if ($status_filter) { $where .= " AND r.status = ?"; $params[] = $status_filter; }

$stats = [];
try {
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'completed' $branch_filter");
    $stats['total_refunded'] = $stmt->fetchColumn();
    $stmt = $db->query("SELECT COUNT(*) FROM refunds WHERE status = 'completed' $branch_filter");
    $stats['count'] = $stmt->fetchColumn();
} catch (Exception $e) {}
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Refunds</li>
        </ol>
    </nav>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Total Refunded</p>
                    <h3 class="stat-value mb-0 text-danger"><?php echo formatMoney($stats['total_refunded'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Refund Count</p>
                    <h3 class="stat-value mb-0 text-warning"><?php echo $stats['count'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Process New</p>
                    <button class="btn btn-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#refundModal"><i class="bi bi-arrow-return-left"></i> Process Refund</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="refunds.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Refund History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Original Payment</th><th>Customer</th><th>Amount</th><th>Reason</th><th>Status</th><th>Processed By</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $st = $db->prepare("SELECT r.*, p.reference as pay_ref, c.first_name, c.last_name, u.first_name as uf, u.last_name as ul FROM refunds r LEFT JOIN payments p ON r.payment_id = p.id LEFT JOIN customers c ON r.customer_id = c.id LEFT JOIN users u ON r.processed_by = u.id WHERE $where $branch_filter ORDER BY r.created_at DESC");
                            $st->execute($params);
                            $refunds = $st->fetchAll();
                            foreach ($refunds as $r):
                        ?>
                        <tr>
                            <td><small><?php echo htmlspecialchars($r['pay_ref'] ?? '—'); ?></small></td>
                            <td><?php echo htmlspecialchars(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '—')); ?></td>
                            <td class="text-danger fw-semibold">-<?php echo formatMoney($r['amount']); ?></td>
                            <td><small><?php echo htmlspecialchars(truncate($r['reason'] ?? '—', 50)); ?></small></td>
                            <td>
                                <?php
                                $rb = ['completed'=>'success','pending'=>'warning','rejected'=>'danger'];
                                $rc = $rb[$r['status']] ?? 'secondary';
                                echo "<span class='badge bg-{$rc}'>" . htmlspecialchars(ucfirst($r['status'])) . "</span>";
                                ?>
                            </td>
                            <td><small><?php echo htmlspecialchars(($r['uf'] ?? '') . ' ' . ($r['ul'] ?? '—')); ?></small></td>
                            <td><small class="text-muted"><?php echo formatDateTime($r['created_at']); ?></small></td>
                        </tr>
                        <?php endforeach; if (empty($refunds)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No refunds processed</td></tr>
                        <?php } catch (Exception $e) {
                            echo '<tr><td colspan="7" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Process Refund</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Original Payment</label>
                        <select name="payment_id" class="form-select" required>
                            <option value="">Select paid payment</option>
                            <?php
                            $pays = $db->prepare("SELECT p.id, p.reference, p.amount, c.first_name, c.last_name FROM payments p LEFT JOIN customers c ON p.customer_id = c.id WHERE p.status = 'paid' ORDER BY p.created_at DESC LIMIT 100");
                            $pays->execute();
                            while ($p = $pays->fetch()):
                            ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars(($p['reference'] ?? 'PAY-' . $p['id']) . ' - ' . formatMoney($p['amount']) . ' - ' . ($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Refund Amount (<?php echo CURRENCY_SYMBOL; ?>)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why this refund is being processed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="process_refund" class="btn btn-warning" onclick="return confirm('Process this refund? This action cannot be undone.')"><i class="bi bi-exclamation-triangle"></i> Process Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
