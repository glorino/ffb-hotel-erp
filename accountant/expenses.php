<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['accountant']);

$page_title = 'Expenses';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND branch_id = " . (int)$branch_id : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    $description = $_POST['description'] ?? '';
    $receipt = '';
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $receipt = 'uploads/expenses/' . uniqid('rcpt_') . '.' . $ext;
        move_uploaded_file($_FILES['receipt']['tmp_name'], __DIR__ . '/../' . $receipt);
    }
    try {
        $stmt = $db->prepare("INSERT INTO expenses (branch_id, title, category, amount, description, receipt, recorded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$branch_id ?: null, $title, $category, $amount, $description, $receipt, $_SESSION['user_id']]);
        set_flash('success', 'Expense recorded successfully');
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: expenses.php'); exit;
}

if (isset($_GET['delete']) && (int)$_GET['delete']) {
    try {
        $stmt = $db->prepare("DELETE FROM expenses WHERE id = ? $branch_filter");
        $stmt->execute([(int)$_GET['delete']]);
        set_flash('success', 'Expense deleted');
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: expenses.php'); exit;
}

$cat_filter = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = "1=1"; $params = [];
if ($cat_filter) { $where .= " AND e.category = ?"; $params[] = $cat_filter; }
if ($date_from) { $where .= " AND DATE(e.created_at) >= ?"; $params[] = $date_from; }
if ($date_to) { $where .= " AND DATE(e.created_at) <= ?"; $params[] = $date_to; }

$total_exp = 0;
try {
    $st = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses e WHERE $where $branch_filter");
    $st->execute($params);
    $total_exp = $st->fetchColumn();
} catch (Exception $e) {}
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Expenses</li>
        </ol>
    </nav>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Total Expenses</p>
                    <h3 class="stat-value mb-0 text-danger"><?php echo formatMoney($total_exp); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Add New</p>
                    <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#expenseModal"><i class="bi bi-plus-circle"></i> Record Expense</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach (json_decode(EXPENSE_CATEGORIES) as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $cat_filter === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $cat))); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-lg-3 col-md-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="expenses.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold">Expense Records</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Title</th><th>Category</th><th>Amount</th><th>Date</th><th>Recorded By</th><th>Receipt</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $st = $db->prepare("SELECT e.*, u.first_name as uf, u.last_name as ul FROM expenses e LEFT JOIN users u ON e.recorded_by = u.id WHERE $where $branch_filter ORDER BY e.created_at DESC");
                                    $st->execute($params);
                                    $expenses = $st->fetchAll();
                                    foreach ($expenses as $e):
                                ?>
                                <tr>
                                    <td><span class="fw-medium"><?php echo htmlspecialchars($e['title']); ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $e['category']))); ?></span></td>
                                    <td class="text-danger fw-semibold"><?php echo formatMoney($e['amount']); ?></td>
                                    <td><small class="text-muted"><?php echo formatDate($e['created_at']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars(($e['uf'] ?? '') . ' ' . ($e['ul'] ?? '—')); ?></small></td>
                                    <td>
                                        <?php if ($e['receipt']): ?>
                                        <a href="<?php echo $base_url . $e['receipt']; ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                        <?php else: ?>
                                        <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?delete=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this expense?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; if (empty($expenses)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No expenses recorded</td></tr>
                                <?php } catch (Exception $e) {
                                    echo '<tr><td colspan="7" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header"><h5 class="modal-title">Record Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category</option>
                            <?php foreach (json_decode(EXPENSE_CATEGORIES) as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $cat))); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (<?php echo CURRENCY_SYMBOL; ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Receipt (optional)</label>
                        <input type="file" name="receipt" class="form-control" accept="image/*,.pdf">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_expense" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
