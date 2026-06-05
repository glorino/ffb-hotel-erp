<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'System Reports';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/admin-sidebar.php';

$db = getDB();

$report_export = $_POST['export'] ?? '';
$report_type = $_POST['report_type'] ?? '';
$date_from = $_POST['date_from'] ?? date('Y-m-01');
$date_to = $_POST['date_to'] ?? date('Y-m-d');

if ($report_export && $report_type) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $report_type . '_' . $date_from . '_to_' . $date_to . '.csv"');
    $output = fopen('php://output', 'w');

    try {
        switch ($report_type) {
            case 'bookings':
                fputcsv($output, ['Reference', 'Customer', 'Branch', 'Room', 'Check In', 'Check Out', 'Amount', 'Status', 'Source', 'Date']);
                $stmt = $db->prepare("SELECT b.booking_reference, c.full_name, br.name, r.room_number, b.check_in_date, b.check_out_date, b.total_amount, b.booking_status, b.source, b.created_at FROM bookings b JOIN customers c ON b.customer_id = c.id JOIN branches br ON b.branch_id = br.id LEFT JOIN rooms r ON b.room_id = r.id WHERE DATE(b.created_at) BETWEEN ? AND ? ORDER BY b.created_at");
                $stmt->execute([$date_from, $date_to]);
                while ($row = $stmt->fetch()) { fputcsv($output, $row); }
                break;
            case 'payments':
                fputcsv($output, ['Reference', 'Customer', 'Amount', 'Method', 'Status', 'Channel', 'Date']);
                $stmt = $db->prepare("SELECT p.payment_reference, c.full_name, p.amount, p.method, p.status, p.channel, p.created_at FROM payments p LEFT JOIN customers c ON p.customer_id = c.id WHERE DATE(p.created_at) BETWEEN ? AND ? ORDER BY p.created_at");
                $stmt->execute([$date_from, $date_to]);
                while ($row = $stmt->fetch()) { fputcsv($output, $row); }
                break;
            case 'revenue':
                fputcsv($output, ['Date', 'Total Revenue', 'Bookings', 'Orders']);
                $stmt = $db->prepare("SELECT DATE(p.created_at) as day, SUM(p.amount) as total FROM payments p WHERE p.status='paid' AND DATE(p.created_at) BETWEEN ? AND ? GROUP BY DATE(p.created_at) ORDER BY day");
                $stmt->execute([$date_from, $date_to]);
                while ($row = $stmt->fetch()) { fputcsv($output, [$row['day'], $row['total'], '', '']); }
                break;
            case 'customers':
                fputcsv($output, ['Name', 'Email', 'Phone', 'City', 'Total Bookings', 'Total Spent', 'Registered']);
                $stmt = $db->prepare("SELECT c.full_name, c.email, c.phone, c.city, (SELECT COUNT(*) FROM bookings b WHERE b.customer_id=c.id) as bk, (SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.customer_id=c.id AND p.status='paid') as spent, c.created_at FROM customers c WHERE DATE(c.created_at) BETWEEN ? AND ? ORDER BY c.created_at");
                $stmt->execute([$date_from, $date_to]);
                while ($row = $stmt->fetch()) { fputcsv($output, [$row['full_name'], $row['email'], $row['phone'], $row['city'], $row['bk'], $row['spent'], $row['created_at']]); }
                break;
            case 'staff':
                fputcsv($output, ['Name', 'Email', 'Role', 'Branch', 'Status', 'Last Login']);
                $stmt = $db->query("SELECT u.full_name, u.email, r.name as role, b.name as branch, u.status, u.last_login FROM users u JOIN roles r ON u.role_id=r.id LEFT JOIN branches b ON u.branch_id=b.id WHERE r.slug!='customer' ORDER BY u.full_name");
                while ($row = $stmt->fetch()) { fputcsv($output, [$row['full_name'], $row['email'], $row['role'], $row['branch'] ?? 'All', $row['status'], $row['last_login'] ?? 'Never']); }
                break;
            case 'inventory':
                fputcsv($output, ['Item', 'Branch', 'Category', 'Quantity', 'Unit', 'Price/Unit', 'Value', 'Status']);
                $stmt = $db->query("SELECT i.name, b.name as branch, i.category, i.quantity, i.unit, i.price_per_unit, (i.quantity * i.price_per_unit) as value, CASE WHEN i.quantity <= i.reorder_level THEN 'Low Stock' ELSE 'In Stock' END as status FROM inventory_items i JOIN branches b ON i.branch_id=b.id WHERE i.status='active' ORDER BY i.name");
                while ($row = $stmt->fetch()) { fputcsv($output, [$row['name'], $row['branch'], $row['category'], $row['quantity'], $row['unit'], $row['price_per_unit'], $row['value'], $row['status']]); }
                break;
        }
    } catch (Exception $e) {
        fputcsv($output, ['Error: ' . $e->getMessage()]);
    }
    fclose($output);
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Reports</li>
        </ol>
    </nav>

    <h4 class="fw-semibold mb-4">System Reports</h4>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-semibold">Generate Report</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <?php echo csrf_field(); ?>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Report Type</label>
                            <select name="report_type" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="bookings">Bookings Report</option>
                                <option value="payments">Payments Report</option>
                                <option value="revenue">Revenue Summary</option>
                                <option value="customers">Customers Report</option>
                                <option value="staff">Staff Report</option>
                                <option value="inventory">Inventory Report</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="export" value="csv" class="btn btn-success w-100"><i class="bi bi-download"></i> Export CSV</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 text-primary mb-3"><i class="bi bi-calendar-check"></i></div>
                    <h6 class="fw-semibold">Bookings Report</h6>
                    <p class="text-muted small">All bookings with customer, branch, and room details.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 text-success mb-3"><i class="bi bi-credit-card"></i></div>
                    <h6 class="fw-semibold">Payments Report</h6>
                    <p class="text-muted small">Payment records with method, status, and channel info.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 text-warning mb-3"><i class="bi bi-graph-up"></i></div>
                    <h6 class="fw-semibold">Revenue Summary</h6>
                    <p class="text-muted small">Daily revenue breakdowns for analysis.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 text-info mb-3"><i class="bi bi-people"></i></div>
                    <h6 class="fw-semibold">Customers Report</h6>
                    <p class="text-muted small">Customer data with booking and spend history.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 text-secondary mb-3"><i class="bi bi-person-badge"></i></div>
                    <h6 class="fw-semibold">Staff Report</h6>
                    <p class="text-muted small">Staff list with roles, branches, and status.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-6 text-danger mb-3"><i class="bi bi-boxes"></i></div>
                    <h6 class="fw-semibold">Inventory Report</h6>
                    <p class="text-muted small">Stock levels, values, and low stock alerts.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
