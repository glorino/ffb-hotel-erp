<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'All Bookings';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$status = $_GET['status'] ?? '';
$source = $_GET['source'] ?? '';
$search = $_GET['search'] ?? '';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Bookings</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search reference or customer..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="checked_in" <?php echo $status === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                        <option value="checked_out" <?php echo $status === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="no_show" <?php echo $status === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="source" class="form-select form-select-sm">
                        <option value="">All Sources</option>
                        <option value="online" <?php echo $source === 'online' ? 'selected' : ''; ?>>Online</option>
                        <option value="walk_in" <?php echo $source === 'walk_in' ? 'selected' : ''; ?>>Walk In</option>
                        <option value="reception" <?php echo $source === 'reception' ? 'selected' : ''; ?>>Reception</option>
                        <option value="admin" <?php echo $source === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">All Bookings</h5>
            <span class="badge bg-primary"><?php
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM bookings");
                    echo number_format($stmt->fetchColumn()) . ' total';
                } catch (Exception $e) { echo '0'; }
            ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $where = [];
                            $params = [];
                            if ($search) {
                                $where[] = "(b.booking_reference LIKE ? OR c.full_name LIKE ? OR c.email LIKE ?)";
                                $s = "%$search%";
                                $params[] = $s; $params[] = $s; $params[] = $s;
                            }
                            if ($date_from) { $where[] = "b.check_in_date >= ?"; $params[] = $date_from; }
                            if ($date_to) { $where[] = "b.check_out_date <= ?"; $params[] = $date_to; }
                            if ($status) { $where[] = "b.booking_status = ?"; $params[] = $status; }
                            if ($source) { $where[] = "b.source = ?"; $params[] = $source; }

                            $sql = "
                                SELECT b.*, c.full_name as customer_name, c.email as customer_email,
                                       br.name as branch_name, r.room_number, rt.name as room_type
                                FROM bookings b
                                JOIN customers c ON b.customer_id = c.id
                                JOIN branches br ON b.branch_id = br.id
                                LEFT JOIN rooms r ON b.room_id = r.id
                                LEFT JOIN room_types rt ON r.room_type_id = rt.id
                            ";
                            if ($where) $sql .= " WHERE " . implode(" AND ", $where);
                            $sql .= " ORDER BY b.created_at DESC LIMIT 200";

                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $bookings = $stmt->fetchAll();

                            if (empty($bookings)):
                        ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No bookings found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($bookings as $bk): ?>
                        <tr>
                            <td class="fw-medium"><small><?php echo htmlspecialchars($bk['booking_reference']); ?></small></td>
                            <td>
                                <?php echo htmlspecialchars($bk['customer_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($bk['customer_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($bk['branch_name']); ?></td>
                            <td><small><?php echo htmlspecialchars($bk['room_number'] ?? 'N/A'); ?> <?php echo $bk['room_type'] ? '(' . htmlspecialchars($bk['room_type']) . ')' : ''; ?></small></td>
                            <td><small><?php echo formatDate($bk['check_in_date']); ?></small></td>
                            <td><small><?php echo formatDate($bk['check_out_date']); ?></small></td>
                            <td><?php echo formatMoney($bk['total_amount']); ?></td>
                            <td><?php echo getBookingStatusBadge($bk['booking_status']); ?></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($bk['source']); ?></span></td>
                            <td><?php echo getPaymentStatusBadge($bk['payment_status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr><td colspan="10" class="text-center text-danger">Error: <?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
