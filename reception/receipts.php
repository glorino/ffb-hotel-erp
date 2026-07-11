<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Receipts';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

$search_ref = $_GET['ref'] ?? '';

if (isset($_GET['print']) && $_GET['print']) {
    $receipt_id = (int)$_GET['print'];
    try {
        $stmt = $db->prepare("
            SELECT r.*, b.booking_reference as booking_ref, b.check_in_date, b.check_out_date, b.total_amount, b.payable_amount,
                   c.full_name, c.email, c.phone, rm.room_number, rt.name as room_type, br.name as branch_name
            FROM receipts r
            JOIN bookings b ON r.booking_id = b.id
            LEFT JOIN customers c ON b.customer_id = c.id
            LEFT JOIN rooms rm ON b.room_id = rm.id
            LEFT JOIN room_types rt ON rm.room_type_id = rt.id
            LEFT JOIN branches br ON b.branch_id = br.id
            WHERE r.id = ? AND b.branch_id = ?
        ");
        $stmt->execute([$receipt_id, $branch_id]);
        $receipt = $stmt->fetch();
        if (!$receipt) { throw new Exception('Receipt not found'); }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo htmlspecialchars($receipt['receipt_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; padding: 40px; color: #333; }
        .receipt-box { max-width: 700px; margin: 0 auto; border: 1px solid #dee2e6; padding: 30px; }
        @media print { .no-print { display: none !important; } }
        .receipt-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .receipt-footer { text-align: center; border-top: 2px solid #333; padding-top: 20px; margin-top: 20px; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="no-print text-center mb-3">
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>
    <div class="receipt-box">
        <div class="receipt-header">
            <h2><?php echo htmlspecialchars($receipt['branch_name'] ?? APP_NAME); ?></h2>
            <h4>Payment Receipt</h4>
            <p class="mb-0"><strong>Receipt #: <?php echo htmlspecialchars($receipt['receipt_number']); ?></strong></p>
            <p class="mb-0">Date: <?php echo formatDateTime($receipt['generated_at']); ?></p>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <h6>Guest Details</h6>
                <p class="mb-0"><?php echo htmlspecialchars($receipt['full_name'] ?? ''); ?></p>
                <p class="mb-0"><?php echo htmlspecialchars($receipt['email'] ?? ''); ?></p>
                <p class="mb-0"><?php echo htmlspecialchars($receipt['phone'] ?? ''); ?></p>
            </div>
            <div class="col-6 text-end">
                <h6>Booking Details</h6>
                <p class="mb-0">Ref: <?php echo htmlspecialchars($receipt['booking_ref'] ?? ''); ?></p>
                <p class="mb-0">Room: <?php echo htmlspecialchars($receipt['room_number'] ?? 'N/A'); ?></p>
                <p class="mb-0"><?php echo formatDate($receipt['check_in_date']); ?> — <?php echo formatDate($receipt['check_out_date']); ?></p>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr><th>Description</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($receipt['room_type'] ?? 'Room'); ?> (<?php echo calculateNights($receipt['check_in_date'], $receipt['check_out_date']); ?> nights)</td>
                    <td class="text-end"><?php echo formatMoney($receipt['total_amount'] ?? 0); ?></td>
                </tr>
                <?php if (($receipt['total_amount'] ?? 0) != ($receipt['payable_amount'] ?? 0)): ?>
                <tr>
                    <td>Discounts / Adjustments</td>
                    <td class="text-end text-danger">-<?php echo formatMoney(($receipt['total_amount'] ?? 0) - ($receipt['payable_amount'] ?? 0)); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td>Total Paid</td>
                    <td class="text-end"><?php echo formatMoney($receipt['amount']); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="receipt-footer">
            <p class="mb-0">Thank you for choosing <?php echo htmlspecialchars($receipt['branch_name'] ?? APP_NAME); ?>!</p>
            <p class="mb-0">This is a computer-generated receipt.</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
        exit;
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if (isset($_GET['regenerate']) && $_GET['regenerate']) {
    $booking_id = (int)$_GET['regenerate'];
    try {
        $stmt = $db->prepare("SELECT id, total_amount FROM bookings WHERE id = ? AND branch_id = ?");
        $stmt->execute([$booking_id, $branch_id]);
        $booking = $stmt->fetch();
        if ($booking) {
            $receipt_no = generateReceiptNumber();
            $stmt = $db->prepare("INSERT INTO receipts (booking_id, receipt_number, amount, generated_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$booking_id, $receipt_no, $booking['total_amount']]);
            set_flash('success', "Receipt regenerated: {$receipt_no}");
        } else {
            set_flash('danger', 'Booking not found');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: receipts.php');
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Receipts</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small">Search by Receipt Number</label>
                    <input type="text" name="ref" class="form-control" placeholder="e.g. RCP-20260515-XXXXXX" value="<?php echo htmlspecialchars($search_ref); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
                    <a href="receipts.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Generated Receipts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt #</th>
                            <th>Booking Ref</th>
                            <th>Guest</th>
                            <th>Amount</th>
                            <th>Generated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT r.*, b.booking_reference as booking_ref, b.total_amount, b.branch_id,
                                    c.full_name
                                    FROM receipts r
                                    JOIN bookings b ON r.booking_id = b.id
                                    LEFT JOIN customers c ON b.customer_id = c.id
                                    WHERE b.branch_id = ?";
                            $params = [$branch_id];
                            if ($search_ref) {
                                $sql .= " AND r.receipt_number LIKE ?";
                                $params[] = "%$search_ref%";
                            }
                            $sql .= " ORDER BY r.generated_at DESC LIMIT 100";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $receipts = $stmt->fetchAll();
                            foreach ($receipts as $r):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['receipt_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['booking_ref'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($r['full_name'] ?? ''); ?></td>
                            <td><?php echo formatMoney($r['amount']); ?></td>
                            <td><small class="text-muted"><?php echo timeAgo($r['generated_at']); ?></small></td>
                            <td>
                                <a href="?print=<?php echo $r['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Print</a>
                                <a href="?regenerate=<?php echo $r['booking_id']; ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Regenerate receipt for this booking?')"><i class="bi bi-arrow-clockwise"></i> Regenerate</a>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($receipts)):
                        ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No receipts found</td></tr>
                        <?php
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="6" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
