<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Reports';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/owner-sidebar.php';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Reports</li>
        </ol>
    </nav>

    <h4 class="fw-semibold mb-4">Report Generation</h4>

    <?php
    $report_export = $_POST['export'] ?? '';
    $report_type = $_POST['report_type'] ?? '';
    $date_from = $_POST['date_from'] ?? date('Y-m-01');
    $date_to = $_POST['date_to'] ?? date('Y-m-d');

    if ($report_export && $report_type) {
        $db = getDB();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $report_type . '_' . $date_from . '_to_' . $date_to . '.csv"');
        header('Pragma: no-cache');
        $output = fopen('php://output', 'w');

        try {
            switch ($report_type) {
                case 'bookings':
                    fputcsv($output, ['Reference', 'Customer', 'Branch', 'Room', 'Check In', 'Check Out', 'Amount', 'Status', 'Date']);
                    $stmt = $db->prepare("
                        SELECT b.booking_reference, c.full_name, br.name as branch, r.room_number,
                               b.check_in_date, b.check_out_date, b.total_amount, b.booking_status, b.created_at
                        FROM bookings b
                        JOIN customers c ON b.customer_id = c.id
                        JOIN branches br ON b.branch_id = br.id
                        LEFT JOIN rooms r ON b.room_id = r.id
                        WHERE DATE(b.created_at) BETWEEN ? AND ?
                        ORDER BY b.created_at
                    ");
                    $stmt->execute([$date_from, $date_to]);
                    while ($row = $stmt->fetch()) {
                        fputcsv($output, [$row['booking_reference'], $row['full_name'], $row['branch'], $row['room_number'], $row['check_in_date'], $row['check_out_date'], $row['total_amount'], $row['booking_status'], $row['created_at']]);
                    }
                    break;

                case 'payments':
                    fputcsv($output, ['Reference', 'Customer', 'Amount', 'Method', 'Status', 'Channel', 'Date']);
                    $stmt = $db->prepare("
                        SELECT p.payment_reference, c.full_name, p.amount, p.method, p.status, p.channel, p.created_at
                        FROM payments p
                        LEFT JOIN customers c ON p.customer_id = c.id
                        WHERE DATE(p.created_at) BETWEEN ? AND ?
                        ORDER BY p.created_at
                    ");
                    $stmt->execute([$date_from, $date_to]);
                    while ($row = $stmt->fetch()) {
                        fputcsv($output, [$row['payment_reference'], $row['full_name'], $row['amount'], $row['method'], $row['status'], $row['channel'], $row['created_at']]);
                    }
                    break;

                case 'revenue':
                    fputcsv($output, ['Date', 'Revenue', 'Room Revenue', 'Food Revenue']);
                    $stmt = $db->prepare("
                        SELECT DATE(p.created_at) as day, SUM(p.amount) as total
                        FROM payments p WHERE p.status = 'paid' AND DATE(p.created_at) BETWEEN ? AND ?
                        GROUP BY DATE(p.created_at) ORDER BY day
                    ");
                    $stmt->execute([$date_from, $date_to]);
                    while ($row = $stmt->fetch()) {
                        fputcsv($output, [$row['day'], $row['total'], '', '']);
                    }
                    break;

                case 'customers':
                    fputcsv($output, ['Name', 'Email', 'Phone', 'City', 'Total Bookings', 'Total Spent', 'Registered']);
                    $stmt = $db->prepare("
                        SELECT c.full_name, c.email, c.phone, c.city,
                               (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) as bookings,
                               (SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.customer_id = c.id AND p.status='paid') as spent,
                               c.created_at
                        FROM customers c
                        WHERE DATE(c.created_at) BETWEEN ? AND ?
                        ORDER BY c.created_at
                    ");
                    $stmt->execute([$date_from, $date_to]);
                    while ($row = $stmt->fetch()) {
                        fputcsv($output, [$row['full_name'], $row['email'], $row['phone'], $row['city'], $row['bookings'], $row['spent'], $row['created_at']]);
                    }
                    break;

                case 'occupancy':
                    fputcsv($output, ['Room', 'Branch', 'Type', 'Status', 'Floor']);
                    $stmt = $db->query("
                        SELECT r.room_number, b.name as branch, rt.name as type, r.status, r.floor
                        FROM rooms r
                        JOIN branches b ON r.branch_id = b.id
                        JOIN room_types rt ON r.room_type_id = rt.id
                        ORDER BY b.name, r.room_number
                    ");
                    while ($row = $stmt->fetch()) {
                        fputcsv($output, [$row['room_number'], $row['branch'], $row['type'], $row['status'], $row['floor']]);
                    }
                    break;

                case 'orders':
                    fputcsv($output, ['Reference', 'Customer', 'Type', 'Status', 'Amount', 'Payment', 'Date']);
                    $stmt = $db->prepare("
                        SELECT fo.order_reference, c.full_name, fo.order_type, fo.status, fo.payable_amount, fo.payment_status, fo.created_at
                        FROM food_orders fo
                        LEFT JOIN customers c ON fo.customer_id = c.id
                        WHERE DATE(fo.created_at) BETWEEN ? AND ?
                        ORDER BY fo.created_at
                    ");
                    $stmt->execute([$date_from, $date_to]);
                    while ($row = $stmt->fetch()) {
                        fputcsv($output, [$row['order_reference'], $row['full_name'], $row['order_type'], $row['status'], $row['payable_amount'], $row['payment_status'], $row['created_at']]);
                    }
                    break;
            }
        } catch (Exception $e) {
            fputcsv($output, ['Error: ' . $e->getMessage()]);
        }
        fclose($output);
        exit;
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-semibold">Select Date Range & Report Type</h5>
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
                                <option value="">-- Select Report --</option>
                                <option value="bookings">Bookings Report</option>
                                <option value="payments">Payments Report</option>
                                <option value="revenue">Revenue Summary</option>
                                <option value="customers">Customers Report</option>
                                <option value="occupancy">Room Occupancy</option>
                                <option value="orders">Food Orders</option>
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
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <div class="display-6 text-primary mb-3"><i class="bi bi-calendar-check"></i></div>
                    <h5 class="fw-semibold">Bookings Report</h5>
                    <p class="text-muted small">Comprehensive list of all bookings within a date range.</p>
                    <a href="#" onclick="document.querySelector('[name=report_type]').value='bookings'; return false;" class="btn btn-outline-primary btn-sm">Generate</a>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <div class="display-6 text-success mb-3"><i class="bi bi-credit-card"></i></div>
                    <h5 class="fw-semibold">Payments Report</h5>
                    <p class="text-muted small">All payments recorded including method and status details.</p>
                    <a href="#" onclick="document.querySelector('[name=report_type]').value='payments'; return false;" class="btn btn-outline-primary btn-sm">Generate</a>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <div class="display-6 text-warning mb-3"><i class="bi bi-graph-up"></i></div>
                    <h5 class="fw-semibold">Revenue Summary</h5>
                    <p class="text-muted small">Daily revenue breakdown with category analysis.</p>
                    <a href="#" onclick="document.querySelector('[name=report_type]').value='revenue'; return false;" class="btn btn-outline-primary btn-sm">Generate</a>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <div class="display-6 text-info mb-3"><i class="bi bi-people"></i></div>
                    <h5 class="fw-semibold">Customers Report</h5>
                    <p class="text-muted small">Customer list with booking history and spending data.</p>
                    <a href="#" onclick="document.querySelector('[name=report_type]').value='customers'; return false;" class="btn btn-outline-primary btn-sm">Generate</a>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <div class="display-6 text-secondary mb-3"><i class="bi bi-door-open"></i></div>
                    <h5 class="fw-semibold">Room Occupancy</h5>
                    <p class="text-muted small">Current room statuses across all branches.</p>
                    <a href="#" onclick="document.querySelector('[name=report_type]').value='occupancy'; return false;" class="btn btn-outline-primary btn-sm">Generate</a>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <div class="display-6 text-danger mb-3"><i class="bi bi-utensils"></i></div>
                    <h5 class="fw-semibold">Food Orders</h5>
                    <p class="text-muted small">All food orders with status and payment information.</p>
                    <a href="#" onclick="document.querySelector('[name=report_type]').value='orders'; return false;" class="btn btn-outline-primary btn-sm">Generate</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
