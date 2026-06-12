<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Branch Bookings';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $action = $_POST['action'];
    try {
        if ($action === 'confirm') {
            $stmt = $db->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE id = ? AND branch_id = ?");
            $stmt->execute([$booking_id, $branch_id]);
            set_flash('success', 'Booking confirmed successfully');
        } elseif ($action === 'cancel') {
            $stmt = $db->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ? AND branch_id = ?");
            $stmt->execute([$booking_id, $branch_id]);
            $stmt2 = $db->prepare("UPDATE rooms SET status = 'available' WHERE id = (SELECT room_id FROM bookings WHERE id = ?)");
            $stmt2->execute([$booking_id]);
            set_flash('success', 'Booking cancelled and room freed');
        }
        log_audit($action . '_booking', 'booking', $booking_id);
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: bookings.php');
    exit;
}
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
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Reference, guest name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                        <option value="checked_out" <?php echo $status_filter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small">From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small">To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-lg-3 col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm me-1"><i class="bi bi-search"></i> Filter</button>
                    <a href="bookings.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i> Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">All Bookings</h5>
            <span class="badge bg-primary"><?php
                try {
                    $count_sql = "SELECT COUNT(*) FROM bookings WHERE branch_id = ?";
                    $params = [$branch_id];
                    if ($status_filter) { $count_sql .= " AND booking_status = ?"; $params[] = $status_filter; }
                    if ($search) { $count_sql .= " AND (reference LIKE ? OR id IN (SELECT booking_id FROM guests WHERE full_name LIKE ?))"; $params[] = "%$search%"; $params[] = "%$search%"; }
                    if ($date_from) { $count_sql .= " AND check_in_date >= ?"; $params[] = $date_from; }
                    if ($date_to) { $count_sql .= " AND check_out_date <= ?"; $params[] = $date_to; }
                    $stmt = $db->prepare($count_sql);
                    $stmt->execute($params);
                    echo $stmt->fetchColumn() . ' total';
                } catch (Exception $e) { echo '0 total'; }
            ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "
                                SELECT b.*, c.first_name, c.last_name, c.email, rm.room_number, rt.name as room_type_name
                                FROM bookings b
                                LEFT JOIN customers c ON b.customer_id = c.id
                                LEFT JOIN rooms rm ON b.room_id = rm.id
                                LEFT JOIN room_types rt ON rm.room_type_id = rt.id
                                WHERE b.branch_id = ?
                            ";
                            $params = [$branch_id];
                            if ($status_filter) { $sql .= " AND b.booking_status = ?"; $params[] = $status_filter; }
                            if ($search) { $sql .= " AND (b.reference LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
                            if ($date_from) { $sql .= " AND b.check_in_date >= ?"; $params[] = $date_from; }
                            if ($date_to) { $sql .= " AND b.check_out_date <= ?"; $params[] = $date_to; }
                            $sql .= " ORDER BY b.created_at DESC";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $bookings = $stmt->fetchAll();
                            foreach ($bookings as $b):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? 'Walk-in')); ?></td>
                            <td><?php echo htmlspecialchars($b['room_number'] ?? 'N/A'); ?><br><small class="text-muted"><?php echo htmlspecialchars($b['room_type_name'] ?? ''); ?></small></td>
                            <td><?php echo formatDate($b['check_in_date']); ?></td>
                            <td><?php echo formatDate($b['check_out_date']); ?></td>
                            <td><?php echo formatMoney($b['total_amount'] ?? 0); ?></td>
                            <td><?php echo getBookingStatusBadge($b['booking_status']); ?></td>
                            <td><small class="text-muted"><?php echo formatDate($b['created_at']); ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="bookings.php?view=<?php echo $b['id']; ?>" class="btn btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    <?php if ($b['booking_status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Confirm this booking?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="action" value="confirm">
                                        <button type="submit" class="btn btn-outline-success" title="Confirm"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($b['booking_status'], ['pending','confirmed'])): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Cancel this booking? Room will be freed.')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-outline-danger" title="Cancel"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($b['booking_status'] === 'checked_in'): ?>
                                    <a href="../reception/check-out.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-outline-warning" title="Check Out"><i class="bi bi-box-arrow-right"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($bookings)):
                        ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No bookings found</td></tr>
                        <?php
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="9" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
