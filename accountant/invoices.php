<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'Invoices';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/accountant-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND branch_id = " . (int)$branch_id : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_invoice'])) {
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $due_date = $_POST['due_date'] ?? '';
        $invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        try {
            $stmt = $db->prepare("INSERT INTO invoices (branch_id, customer_id, invoice_number, amount, amount_paid, status, due_date, created_at) VALUES (?, ?, ?, ?, 0, 'unpaid', ?, NOW())");
            $stmt->execute([$branch_id ?: null, $customer_id ?: null, $invoice_number, $amount, $due_date]);
            set_flash('success', "Invoice $invoice_number generated successfully");
        } catch (Exception $e) {
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
        header('Location: invoices.php'); exit;
    }
    if (isset($_POST['mark_paid'])) {
        $id = (int)($_POST['invoice_id'] ?? 0);
        $amount_paid = (float)($_POST['amount_paid'] ?? 0);
        try {
            $stmt = $db->prepare("UPDATE invoices SET status = 'paid', amount_paid = ?, paid_at = NOW() WHERE id = ?");
            $stmt->execute([$amount_paid, $id]);
            set_flash('success', 'Invoice marked as paid');
        } catch (Exception $e) {
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
        header('Location: invoices.php'); exit;
    }
}

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$inv_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = "1=1"; $params = [];
if ($date_from) { $where .= " AND DATE(i.created_at) >= ?"; $params[] = $date_from; }
if ($date_to) { $where .= " AND DATE(i.created_at) <= ?"; $params[] = $date_to; }
if ($inv_status) { $where .= " AND i.status = ?"; $params[] = $inv_status; }
if ($search) { $where .= " AND (i.invoice_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; }
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Invoices</li>
        </ol>
    </nav>

    <?php
    $inv_stats = [];
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'unpaid' $branch_filter");
        $inv_stats['unpaid'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'unpaid' $branch_filter");
        $inv_stats['unpaid_total'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'overdue' $branch_filter");
        $inv_stats['overdue'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'paid' $branch_filter");
        $inv_stats['paid'] = $stmt->fetchColumn();
    } catch (Exception $e) { $inv_stats = ['unpaid'=>0,'unpaid_total'=>0,'overdue'=>0,'paid'=>0]; }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Unpaid</p>
                    <h3 class="stat-value mb-0 text-warning"><?php echo $inv_stats['unpaid']; ?></h3>
                    <small class="text-muted"><?php echo formatMoney($inv_stats['unpaid_total']); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Overdue</p>
                    <h3 class="stat-value mb-0 text-danger"><?php echo $inv_stats['overdue']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Paid</p>
                    <h3 class="stat-value mb-0 text-success"><?php echo $inv_stats['paid']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Generate</p>
                    <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#generateModal"><i class="bi bi-plus-circle"></i> New Invoice</button>
                </div>
            </div>
        </div>
    </div>

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
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="paid" <?php echo $inv_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="unpaid" <?php echo $inv_status === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="overdue" <?php echo $inv_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        <option value="partially_paid" <?php echo $inv_status === 'partially_paid' ? 'selected' : ''; ?>>Partially Paid</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Invoice # or customer" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-lg-3 col-md-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="invoices.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">All Invoices</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Invoice #</th><th>Customer</th><th>Amount</th><th>Paid</th><th>Due</th><th>Status</th><th>Due Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $st = $db->prepare("SELECT i.*, c.first_name, c.last_name, c.email FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE $where $branch_filter ORDER BY i.created_at DESC");
                            $st->execute($params);
                            $invoices = $st->fetchAll();
                            foreach ($invoices as $inv):
                        ?>
                        <tr>
                            <td><span class="fw-medium"><?php echo htmlspecialchars($inv['invoice_number']); ?></span></td>
                            <td><?php echo htmlspecialchars(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? '—')); ?><br><small class="text-muted"><?php echo htmlspecialchars($inv['email'] ?? ''); ?></small></td>
                            <td><?php echo formatMoney($inv['amount']); ?></td>
                            <td><?php echo formatMoney($inv['amount_paid'] ?? 0); ?></td>
                            <td><?php echo formatMoney(max(0, $inv['amount'] - ($inv['amount_paid'] ?? 0))); ?></td>
                            <td>
                                <?php
                                $badge_map = ['paid'=>'success','unpaid'=>'warning','overdue'=>'danger','partially_paid'=>'info'];
                                $c = $badge_map[$inv['status']] ?? 'secondary';
                                echo "<span class='badge bg-{$c}'>" . htmlspecialchars(str_replace('_', ' ', $inv['status'])) . "</span>";
                                ?>
                            </td>
                            <td><small class="text-muted"><?php echo $inv['due_date'] ? formatDate($inv['due_date']) : '—'; ?></small></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-gear"></i></button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="window.open('print-invoice.php?id=<?php echo $inv['id']; ?>','_blank')"><i class="bi bi-printer"></i> Print</a></li>
                                        <?php if ($inv['status'] !== 'paid'): ?>
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#markPaidModal<?php echo $inv['id']; ?>"><i class="bi bi-check-circle"></i> Mark as Paid</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <?php if ($inv['status'] !== 'paid'): ?>
                                <div class="modal fade" id="markPaidModal<?php echo $inv['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                                <div class="modal-header"><h6 class="modal-title">Mark Invoice Paid</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small">Amount Paid</label>
                                                        <input type="number" step="0.01" name="amount_paid" class="form-control" value="<?php echo $inv['amount']; ?>" required>
                                                    </div>
                                                    <p class="small text-muted mb-0">Invoice: <?php echo htmlspecialchars($inv['invoice_number']); ?> — Total: <?php echo formatMoney($inv['amount']); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="mark_paid" class="btn btn-sm btn-success">Confirm Payment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; if (empty($invoices)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No invoices found</td></tr>
                        <?php } catch (Exception $e) {
                            echo '<tr><td colspan="8" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Generate Invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select customer</option>
                            <?php
                            $custs = $db->query("SELECT id, first_name, last_name, email FROM customers WHERE status = 'active' OR status IS NULL ORDER BY first_name");
                            while ($c = $custs->fetch()):
                            ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . ($c['email'] ?? '') . ')'); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (<?php echo CURRENCY_SYMBOL; ?>)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="generate_invoice" class="btn btn-primary">Generate Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
