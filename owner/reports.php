<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Reports';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
        <li class="breadcrumb-item active">Reports</li>
    </ol>
</nav>

<div class="welcome-banner" style="margin-bottom:24px;">
    <h3><i class="bi bi-file-earmark-bar-graph me-2"></i>Report Center</h3>
    <p>Generate professional reports for bookings, payments, revenue, and more. Export as CSV or PDF.</p>
</div>

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
        <div class="card border-0 shadow-sm animate-fade-in-up">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-funnel me-2" style="color:var(--gold);"></i>Generate Report</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <?php echo csrf_field(); ?>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Report Type</label>
                        <select name="report_type" class="form-select" required>
                            <option value="">-- Select Report --</option>
                            <option value="bookings" <?php echo $report_type === 'bookings' ? 'selected' : ''; ?>>Bookings Report</option>
                            <option value="payments" <?php echo $report_type === 'payments' ? 'selected' : ''; ?>>Payments Report</option>
                            <option value="revenue" <?php echo $report_type === 'revenue' ? 'selected' : ''; ?>>Revenue Summary</option>
                            <option value="customers" <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>Customers Report</option>
                            <option value="occupancy" <?php echo $report_type === 'occupancy' ? 'selected' : ''; ?>>Room Occupancy</option>
                            <option value="orders" <?php echo $report_type === 'orders' ? 'selected' : ''; ?>>Food Orders</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" name="export" value="csv" class="btn btn-success w-100"><i class="bi bi-download me-1"></i> CSV</button>
                        <button type="button" onclick="exportPDF()" class="btn btn-danger w-100"><i class="bi bi-file-pdf me-1"></i> PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4 col-md-6 animate-fade-in-up delay-1">
        <div class="card border-0 shadow-sm h-100 quick-action-card" onclick="document.querySelector('[name=report_type]').value='bookings'" style="cursor:pointer;">
            <div class="card-body text-center py-4">
                <div class="qa-icon mx-auto mb-3" style="background:var(--info-bg);color:var(--info);width:56px;height:56px;font-size:1.5rem;"><i class="bi bi-calendar-check"></i></div>
                <h5 class="fw-semibold mb-1">Bookings Report</h5>
                <p class="text-muted small mb-0">Comprehensive list of all bookings within a date range.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 animate-fade-in-up delay-2">
        <div class="card border-0 shadow-sm h-100 quick-action-card" onclick="document.querySelector('[name=report_type]').value='payments'" style="cursor:pointer;">
            <div class="card-body text-center py-4">
                <div class="qa-icon mx-auto mb-3" style="background:var(--success-bg);color:var(--success);width:56px;height:56px;font-size:1.5rem;"><i class="bi bi-credit-card"></i></div>
                <h5 class="fw-semibold mb-1">Payments Report</h5>
                <p class="text-muted small mb-0">All payments recorded including method and status details.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 animate-fade-in-up delay-3">
        <div class="card border-0 shadow-sm h-100 quick-action-card" onclick="document.querySelector('[name=report_type]').value='revenue'" style="cursor:pointer;">
            <div class="card-body text-center py-4">
                <div class="qa-icon mx-auto mb-3" style="background:var(--warning-bg);color:var(--warning);width:56px;height:56px;font-size:1.5rem;"><i class="bi bi-graph-up"></i></div>
                <h5 class="fw-semibold mb-1">Revenue Summary</h5>
                <p class="text-muted small mb-0">Daily revenue breakdown with category analysis.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 animate-fade-in-up delay-4">
        <div class="card border-0 shadow-sm h-100 quick-action-card" onclick="document.querySelector('[name=report_type]').value='customers'" style="cursor:pointer;">
            <div class="card-body text-center py-4">
                <div class="qa-icon mx-auto mb-3" style="background:#f5f3ff;color:#8b5cf6;width:56px;height:56px;font-size:1.5rem;"><i class="bi bi-people"></i></div>
                <h5 class="fw-semibold mb-1">Customers Report</h5>
                <p class="text-muted small mb-0">Customer list with booking history and spending data.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 animate-fade-in-up delay-5">
        <div class="card border-0 shadow-sm h-100 quick-action-card" onclick="document.querySelector('[name=report_type]').value='occupancy'" style="cursor:pointer;">
            <div class="card-body text-center py-4">
                <div class="qa-icon mx-auto mb-3" style="background:var(--bg-main);color:var(--text-muted);width:56px;height:56px;font-size:1.5rem;"><i class="bi bi-door-open"></i></div>
                <h5 class="fw-semibold mb-1">Room Occupancy</h5>
                <p class="text-muted small mb-0">Current room statuses across all branches.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 animate-fade-in-up delay-6">
        <div class="card border-0 shadow-sm h-100 quick-action-card" onclick="document.querySelector('[name=report_type]').value='orders'" style="cursor:pointer;">
            <div class="card-body text-center py-4">
                <div class="qa-icon mx-auto mb-3" style="background:var(--danger-bg);color:var(--danger);width:56px;height:56px;font-size:1.5rem;"><i class="bi bi-utensils"></i></div>
                <h5 class="fw-semibold mb-1">Food Orders</h5>
                <p class="text-muted small mb-0">All food orders with status and payment information.</p>
            </div>
        </div>
    </div>
</div>

<script>
function exportPDF() {
    var form = document.querySelector('form[method="POST"]');
    if (!form) return;
    var reportType = form.querySelector('[name="report_type"]').value;
    if (!reportType) { alert('Please select a report type first.'); return; }
    var dateFrom = form.querySelector('[name="date_from"]').value;
    var dateTo = form.querySelector('[name="date_to"]').value;
    var reportNames = {
        bookings: 'Bookings Report',
        payments: 'Payments Report',
        revenue: 'Revenue Summary',
        customers: 'Customers Report',
        occupancy: 'Room Occupancy',
        orders: 'Food Orders Report'
    };

    // Gather table data
    var table = document.querySelector('.table-responsive table');
    var headers = [];
    var rows = [];
    if (table) {
        table.querySelectorAll('thead th').forEach(function(th) {
            headers.push(th.textContent.trim());
        });
        table.querySelectorAll('tbody tr').forEach(function(tr) {
            var row = [];
            tr.querySelectorAll('td').forEach(function(td) {
                row.push(td.textContent.trim());
            });
            if (row.length > 0) rows.push(row);
        });
    }

    // Build professional PDF HTML
    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    html += '<title>' + (reportNames[reportType] || 'Report') + ' — FFB Hotel</title>';
    html += '<style>';
    html += '@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap");';
    html += '* { margin:0; padding:0; box-sizing:border-box; }';
    html += 'body { font-family:"Inter",sans-serif; color:#1a1a2e; background:#fff; padding:0; }';
    html += '.report-page { padding:40px 48px; max-width:900px; margin:0 auto; }';

    // Header
    html += '.report-header { display:flex; align-items:center; justify-content:space-between; padding-bottom:20px; border-bottom:3px solid #0a1628; margin-bottom:8px; }';
    html += '.report-header .brand { display:flex; align-items:center; gap:14px; }';
    html += '.report-header .brand-icon { width:48px; height:48px; background:linear-gradient(135deg,#d4af37,#f0d060); border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:700; color:#0a1628; font-size:18px; font-family:"Playfair Display",serif; }';
    html += '.report-header .brand-text h1 { font-family:"Playfair Display",serif; font-size:22px; font-weight:700; color:#0a1628; letter-spacing:0.5px; }';
    html += '.report-header .brand-text span { font-size:10px; color:#6b7280; text-transform:uppercase; letter-spacing:2px; font-weight:500; }';
    html += '.report-header .header-meta { text-align:right; }';
    html += '.report-header .header-meta .report-type { font-size:13px; font-weight:600; color:#0a1628; background:#f0f2f5; padding:4px 12px; border-radius:6px; }';
    html += '.report-header .header-meta .gen-date { font-size:11px; color:#6b7280; margin-top:4px; }';

    // Gold accent line
    html += '.gold-accent { height:2px; background:linear-gradient(90deg,#d4af37,#f0d060,transparent); margin-bottom:24px; }';

    // Date range
    html += '.date-range { display:flex; gap:24px; margin-bottom:24px; padding:14px 20px; background:#f8f9fa; border-radius:8px; border-left:4px solid #d4af37; }';
    html += '.date-range .dr-item { font-size:12px; color:#6b7280; }';
    html += '.date-range .dr-item strong { color:#0a1628; font-weight:600; }';

    // Summary cards
    html += '.summary-cards { display:flex; gap:16px; margin-bottom:28px; flex-wrap:wrap; }';
    html += '.summary-card { flex:1; min-width:120px; padding:14px 18px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; text-align:center; }';
    html += '.summary-card .sc-value { font-family:"Playfair Display",serif; font-size:20px; font-weight:700; color:#0a1628; }';
    html += '.summary-card .sc-label { font-size:10px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-top:2px; }';

    // Table
    html += '.report-table { width:100%; border-collapse:collapse; margin-bottom:24px; font-size:11px; }';
    html += '.report-table thead th { background:#0a1628; color:#fff; padding:10px 12px; text-align:left; font-weight:600; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap; }';
    html += '.report-table thead th:first-child { border-radius:6px 0 0 0; }';
    html += '.report-table thead th:last-child { border-radius:0 6px 0 0; }';
    html += '.report-table tbody td { padding:9px 12px; border-bottom:1px solid #e5e7eb; font-size:11px; }';
    html += '.report-table tbody tr:nth-child(even) { background:#f9fafb; }';
    html += '.report-table tbody tr:hover { background:rgba(212,175,55,0.04); }';
    html += '.report-table tbody tr:last-child td:first-child { border-radius:0 0 0 6px; }';
    html += '.report-table tbody tr:last-child td:last-child { border-radius:0 0 6px 0; }';

    // Status badges in table
    html += '.badge { display:inline-block; padding:2px 8px; border-radius:50px; font-size:9px; font-weight:600; text-transform:uppercase; }';
    html += '.badge-paid, .badge-confirmed, .badge-completed { background:#ecfdf5; color:#065f46; }';
    html += '.badge-pending { background:#fffbeb; color:#92400e; }';
    html += '.badge-cancelled, .badge-failed { background:#fef2f2; color:#991b1b; }';

    // Footer
    html += '.report-footer { margin-top:32px; padding-top:16px; border-top:2px solid #0a1628; display:flex; justify-content:space-between; align-items:center; }';
    html += '.report-footer .footer-left { font-size:10px; color:#6b7280; }';
    html += '.report-footer .footer-right { font-size:10px; color:#6b7280; }';
    html += '.report-footer .footer-brand { font-family:"Playfair Display",serif; font-weight:600; color:#0a1628; }';

    // Print styles
    html += '@media print { body { padding:0; } .report-page { padding:20px 30px; } }';
    html += '</style></head><body>';
    html += '<div class="report-page">';

    // Header
    html += '<div class="report-header">';
    html += '<div class="brand">';
    html += '<div class="brand-icon">FFB</div>';
    html += '<div class="brand-text"><h1>FFB Hotel</h1><span>Hotel Management System</span></div>';
    html += '</div>';
    html += '<div class="header-meta">';
    html += '<div class="report-type">' + (reportNames[reportType] || 'Report') + '</div>';
    html += '<div class="gen-date">Generated: ' + new Date().toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) + ' at ' + new Date().toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' }) + '</div>';
    html += '</div>';
    html += '</div>';

    // Gold accent
    html += '<div class="gold-accent"></div>';

    // Date range
    html += '<div class="date-range">';
    html += '<div class="dr-item"><strong>From:</strong> ' + dateFrom + '</div>';
    html += '<div class="dr-item"><strong>To:</strong> ' + dateTo + '</div>';
    html += '<div class="dr-item"><strong>Report Type:</strong> ' + (reportNames[reportType] || 'N/A') + '</div>';
    html += '</div>';

    // Summary
    html += '<div class="summary-cards">';
    html += '<div class="summary-card"><div class="sc-value">' + rows.length + '</div><div class="sc-label">Total Records</div></div>';
    html += '<div class="summary-card"><div class="sc-value">' + headers.length + '</div><div class="sc-label">Data Fields</div></div>';
    html += '<div class="summary-card"><div class="sc-value">' + dateFrom + '</div><div class="sc-label">Start Date</div></div>';
    html += '<div class="summary-card"><div class="sc-value">' + dateTo + '</div><div class="sc-label">End Date</div></div>';
    html += '</div>';

    // Table
    if (headers.length > 0 && rows.length > 0) {
        html += '<table class="report-table"><thead><tr>';
        headers.forEach(function(h) { html += '<th>' + h + '</th>'; });
        html += '</tr></thead><tbody>';
        rows.forEach(function(row) {
            html += '<tr>';
            row.forEach(function(cell) {
                var lower = cell.toLowerCase();
                var badgeClass = '';
                if (lower === 'paid' || lower === 'confirmed' || lower === 'completed' || lower === 'available') badgeClass = ' badge-paid';
                else if (lower === 'pending') badgeClass = ' badge-pending';
                else if (lower === 'cancelled' || lower === 'failed') badgeClass = ' badge-cancelled';
                if (badgeClass) {
                    html += '<td><span class="badge' + badgeClass + '">' + cell + '</span></td>';
                } else {
                    html += '<td>' + cell + '</td>';
                }
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<div style="text-align:center;padding:40px;color:#6b7280;">No data available for this report. Generate data first by selecting a type and clicking Export CSV.</div>';
    }

    // Footer
    html += '<div class="report-footer">';
    html += '<div class="footer-left"><span class="footer-brand">FFB Hotel</span> &mdash; Confidential Business Report</div>';
    html += '<div class="footer-right">Page 1 of 1 &bull; ' + new Date().toLocaleDateString('en-GB') + '</div>';
    html += '</div>';

    html += '</div></body></html>';

    var win = window.open('', '_blank');
    win.document.write(html);
    win.document.close();
    win.focus();
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
