<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Online Bookings';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $action = $_POST['action'];
    try {
        $stmt = $db->prepare("SELECT booking_status, room_id FROM bookings WHERE id = ? AND branch_id = ?");
        $stmt->execute([$booking_id, $branch_id]);
        $booking = $stmt->fetch();
        if (!$booking) { throw new Exception('Booking not found'); }

        if ($action === 'confirm') {
            $stmt = $db->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE id = ?");
            $stmt->execute([$booking_id]);
            if ($booking['room_id']) {
                $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ?");
                $stmt->execute([$booking['room_id']]);
            }
            set_flash('success', 'Online booking confirmed');
        } elseif ($action === 'cancel') {
            $stmt = $db->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
            $stmt->execute([$booking_id]);
            if ($booking['room_id']) {
                $stmt = $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
                $stmt->execute([$booking['room_id']]);
            }
            set_flash('success', 'Booking cancelled');
        }
        log_audit($action . '_online_booking', 'booking', $booking_id);
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: online-bookings.php');
    exit;
}

$status_filter = $_GET['status'] ?? '';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Online Bookings</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Online Bookings</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                        <option value="checked_out" <?php echo $status_filter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Online Bookings</h5>
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
                            <th>Booked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT b.*, c.full_name, c.email, c.phone, rm.room_number
                                    FROM bookings b
                                    LEFT JOIN customers c ON b.customer_id = c.id
                                    LEFT JOIN rooms rm ON b.room_id = rm.id
                                    WHERE b.branch_id = ? AND b.booking_source = 'online'";
                            $params = [$branch_id];
                            if ($status_filter) { $sql .= " AND b.booking_status = ?"; $params[] = $status_filter; }
                            $sql .= " ORDER BY b.created_at DESC";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $bookings = $stmt->fetchAll();
                            foreach ($bookings as $b):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['reference']); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($b['full_name'] ?? ''); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($b['email'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($b['room_number'] ?? 'Not assigned'); ?></td>
                            <td><?php echo formatDate($b['check_in_date']); ?></td>
                            <td><?php echo formatDate($b['check_out_date']); ?></td>
                            <td><?php echo formatMoney($b['total_amount'] ?? 0); ?></td>
                            <td><?php echo getBookingStatusBadge($b['booking_status']); ?></td>
                            <td><small class="text-muted"><?php echo timeAgo($b['created_at']); ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="check-in.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-outline-info" title="View/Check-in"><i class="bi bi-eye"></i></a>
                                    <?php if ($b['booking_status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Confirm this online booking?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="action" value="confirm">
                                        <button type="submit" class="btn btn-outline-success" title="Confirm"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($b['booking_status'], ['pending','confirmed'])): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Cancel this booking?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-outline-danger" title="Cancel"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($b['email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($b['email']); ?>" class="btn btn-outline-primary" title="Contact"><i class="bi bi-envelope"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($bookings)):
                        ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No online bookings found</td></tr>
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
