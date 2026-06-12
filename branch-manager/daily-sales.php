<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Daily Sales';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$sale_date = $_GET['date'] ?? date('Y-m-d');
if (!validateDate($sale_date)) $sale_date = date('Y-m-d');
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Daily Sales</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small">Select Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?php echo $sale_date; ?>" onchange="this.form.submit()">
                </div>
                <div class="col-auto">
                    <a href="daily-sales.php" class="btn btn-outline-secondary btn-sm">Today</a>
                </div>
            </form>
        </div>
    </div>

    <?php
    $breakdown = ['room_revenue' => 0, 'food_revenue' => 0, 'service_revenue' => 0];
    $payment_methods = [];
    $total_sales = 0;
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = ?");
        $stmt->execute([$branch_id, $sale_date]);
        $total_sales = $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            WHERE p.branch_id = ? AND p.status = 'paid' AND DATE(p.created_at) = ? AND p.payment_category = 'room'
        ");
        $stmt->execute([$branch_id, $sale_date]);
        $breakdown['room_revenue'] = $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM payments p
            WHERE p.branch_id = ? AND p.status = 'paid' AND DATE(p.created_at) = ? AND p.payment_category = 'food'
        ");
        $stmt->execute([$branch_id, $sale_date]);
        $breakdown['food_revenue'] = $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM payments p
            WHERE p.branch_id = ? AND p.status = 'paid' AND DATE(p.created_at) = ? AND p.payment_category = 'service'
        ");
        $stmt->execute([$branch_id, $sale_date]);
        $breakdown['service_revenue'] = $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT payment_method, COALESCE(SUM(amount), 0) as total FROM payments
            WHERE branch_id = ? AND status = 'paid' AND DATE(created_at) = ?
            GROUP BY payment_method
        ");
        $stmt->execute([$branch_id, $sale_date]);
        while ($row = $stmt->fetch()) {
            $payment_methods[$row['payment_method']] = $row['total'];
        }
    } catch (Exception $e) {
        error_log('Daily sales error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Total Sales</p>
                    <h3 class="stat-value mb-0"><?php echo formatMoney($total_sales); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100 border-start border-primary border-4">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Room Revenue</p>
                    <h3 class="stat-value mb-0 text-primary"><?php echo formatMoney($breakdown['room_revenue']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100 border-start border-success border-4">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Food Revenue</p>
                    <h3 class="stat-value mb-0 text-success"><?php echo formatMoney($breakdown['food_revenue']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100 border-start border-info border-4">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Service Revenue</p>
                    <h3 class="stat-value mb-0 text-info"><?php echo formatMoney($breakdown['service_revenue']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Revenue Breakdown</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueBreakdownChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $methods = ['cash' => 'Cash', 'pos' => 'POS', 'bank_transfer' => 'Bank Transfer', 'flutterwave' => 'Flutterwave', 'split_payment' => 'Split Payment'];
                                foreach ($methods as $key => $label):
                                    $amount = $payment_methods[$key] ?? 0;
                                    $pct = $total_sales > 0 ? round(($amount / $total_sales) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><i class="bi bi-<?php echo $key === 'cash' ? 'cash' : ($key === 'pos' ? 'credit-card' : ($key === 'bank_transfer' ? 'bank' : ($key === 'flutterwave' ? 'globe' : 'arrows'))); ?> me-2"></i><?php echo $label; ?></td>
                                    <td><?php echo formatMoney($amount); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:6px;">
                                                <div class="progress-bar bg-<?php echo $key === 'cash' ? 'success' : ($key === 'pos' ? 'primary' : ($key === 'bank_transfer' ? 'info' : ($key === 'flutterwave' ? 'warning' : 'secondary'))); ?>" role="progressbar" style="width:<?php echo $pct; ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $pct; ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Recent Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->prepare("
                                        SELECT p.*, b.reference as booking_ref
                                        FROM payments p
                                        LEFT JOIN bookings b ON p.booking_id = b.id
                                        WHERE p.branch_id = ? AND DATE(p.created_at) = ?
                                        ORDER BY p.created_at DESC LIMIT 50
                                    ");
                                    $stmt->execute([$branch_id, $sale_date]);
                                    $txns = $stmt->fetchAll();
                                    foreach ($txns as $t):
                                ?>
                                <tr>
                                    <td><small><?php echo htmlspecialchars($t['reference'] ?? $t['id']); ?></small></td>
                                    <td><?php echo htmlspecialchars(ucfirst($t['payment_category'] ?? 'N/A')); ?></td>
                                    <td><?php echo formatMoney($t['amount']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $t['payment_method'] ?? 'N/A'))); ?></td>
                                    <td><?php echo getPaymentStatusBadge($t['status']); ?></td>
                                    <td><small class="text-muted"><?php echo date('h:i A', strtotime($t['created_at'])); ?></small></td>
                                </tr>
                                <?php
                                    endforeach;
                                    if (empty($txns)):
                                ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No transactions for this date</td></tr>
                                <?php
                                    endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="6" class="text-center py-4 text-danger">Error loading transactions</td></tr>';
                                }
                                ?>
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
    new Chart(document.getElementById('revenueBreakdownChart'), {
        type: 'bar', data: {
            labels: ['Room', 'Food', 'Service'],
            datasets: [{
                label: 'Revenue',
                data: [<?php echo $breakdown['room_revenue']; ?>, <?php echo $breakdown['food_revenue']; ?>, <?php echo $breakdown['service_revenue']; ?>],
                backgroundColor: ['#0d6efd', '#198754', '#0dcaf0'],
                borderRadius: 6
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?php echo CURRENCY_SYMBOL; ?>' + Number(v).toLocaleString() } } } }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
