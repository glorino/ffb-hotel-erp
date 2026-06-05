<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Check-in';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/reception-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_checkin'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $id_verified = isset($_POST['id_verified']) ? 1 : 0;
    $signature_collected = isset($_POST['signature_collected']) ? 1 : 0;
    $room_id = (int)($_POST['room_id'] ?? 0);

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? AND branch_id = ? AND booking_status IN ('confirmed','pending')");
        $stmt->execute([$booking_id, $branch_id]);
        $booking = $stmt->fetch();
        if (!$booking) throw new Exception('Booking not found or cannot be checked in');

        $target_room = $room_id ?: $booking['room_id'];
        if (!$target_room) throw new Exception('No room assigned to this booking');

        $stmt = $db->prepare("UPDATE bookings SET booking_status = 'checked_in', check_in_time = NOW(), id_verified = ?, signature_collected = ?, room_id = ? WHERE id = ?");
        $stmt->execute([$id_verified, $signature_collected, $target_room, $booking_id]);

        $stmt = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
        $stmt->execute([$target_room]);

        log_audit('check_in', 'booking', $booking_id, null, ['room_id' => $target_room]);
        $db->commit();
        set_flash('success', 'Guest checked in successfully');
    } catch (Exception $e) {
        $db->rollBack();
        set_flash('danger', 'Check-in failed: ' . $e->getMessage());
    }
    header('Location: check-in.php');
    exit;
}

$booking_id = $_GET['booking_id'] ?? 0;
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Check-in</li>
        </ol>
    </nav>

    <?php if ($booking_id): ?>
    <?php
    try {
        $stmt = $db->prepare("
            SELECT b.*, c.first_name, c.last_name, c.email, c.phone, c.id_type, c.id_number, rm.room_number, rt.name as room_type, rt.base_price
            FROM bookings b
            LEFT JOIN customers c ON b.customer_id = c.id
            LEFT JOIN rooms rm ON b.room_id = rm.id
            LEFT JOIN room_types rt ON rm.room_type_id = rt.id
            WHERE b.id = ? AND b.branch_id = ?
        ");
        $stmt->execute([$booking_id, $branch_id]);
        $booking = $stmt->fetch();
        if (!$booking) { throw new Exception('Booking not found'); }

        $available_rooms = [];
        $stmt = $db->prepare("SELECT r.*, rt.name as type_name FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.branch_id = ? AND r.status IN ('available','reserved') ORDER BY r.room_number");
        $stmt->execute([$branch_id]);
        $available_rooms = $stmt->fetchAll();
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Check-in: <?php echo htmlspecialchars($booking['reference']); ?></h5>
        </div>
        <div class="card-body">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Guest Information</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '')); ?></strong></p>
                        <p class="mb-1">Email: <?php echo htmlspecialchars($booking['email'] ?? 'N/A'); ?></p>
                        <p class="mb-1">Phone: <?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?></p>
                        <p class="mb-0">ID: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $booking['id_type'] ?? 'N/A'))); ?> — <?php echo htmlspecialchars($booking['id_number'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Booking Details</h6>
                        <p class="mb-1">Reference: <strong><?php echo htmlspecialchars($booking['reference']); ?></strong></p>
                        <p class="mb-1">Room: <?php echo htmlspecialchars($booking['room_number'] ?? 'Not assigned'); ?></p>
                        <p class="mb-1">Check In: <?php echo formatDate($booking['check_in_date']); ?></p>
                        <p class="mb-1">Check Out: <?php echo formatDate($booking['check_out_date']); ?></p>
                        <p class="mb-0">Amount: <?php echo formatMoney($booking['total_amount'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Assign Room</label>
                        <select name="room_id" class="form-select">
                            <option value="">Current room (<?php echo htmlspecialchars($booking['room_number'] ?? 'N/A'); ?>)</option>
                            <?php foreach ($available_rooms as $r): ?>
                            <option value="<?php echo $r['id']; ?>">
                                <?php echo htmlspecialchars($r['room_number'] . ' - ' . $r['type_name'] . ' - ' . formatMoney($r['base_price']) . '/night'); ?>
                                (<?php echo ucfirst($r['status']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="id_verified" id="idVerified" value="1">
                            <label class="form-check-label" for="idVerified">ID Verified</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="signature_collected" id="signatureCollected" value="1">
                            <label class="form-check-label" for="signatureCollected">Signature Collected</label>
                        </div>
                    </div>
                </div>

                <button type="submit" name="do_checkin" class="btn btn-success btn-lg px-5" onclick="return confirm('Confirm check-in for this guest?')">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Confirm Check-in
                </button>
                <a href="check-in.php" class="btn btn-outline-secondary btn-lg">Back to List</a>
            </form>
        </div>
    </div>
    <?php
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <?php else: ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Pending & Confirmed Bookings</h5>
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
                            <th>Source</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->prepare("
                                SELECT b.*, c.first_name, c.last_name, rm.room_number
                                FROM bookings b
                                LEFT JOIN customers c ON b.customer_id = c.id
                                LEFT JOIN rooms rm ON b.room_id = rm.id
                                WHERE b.branch_id = ? AND b.booking_status IN ('pending','confirmed')
                                ORDER BY b.check_in_date ASC, b.created_at DESC
                            ");
                            $stmt->execute([$branch_id]);
                            $bookings = $stmt->fetchAll();
                            foreach ($bookings as $b):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? 'Guest')); ?></td>
                            <td><?php echo htmlspecialchars($b['room_number'] ?? '—'); ?></td>
                            <td><?php echo formatDate($b['check_in_date']); ?></td>
                            <td><?php echo formatDate($b['check_out_date']); ?></td>
                            <td><?php echo formatMoney($b['total_amount'] ?? 0); ?></td>
                            <td><?php echo getBookingStatusBadge($b['booking_status']); ?></td>
                            <td><span class="badge bg-<?php echo $b['booking_source'] === 'online' ? 'info' : 'secondary'; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $b['booking_source'] ?? 'walk_in'))); ?></span></td>
                            <td>
                                <a href="?booking_id=<?php echo $b['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-box-arrow-in-right"></i> Check-in
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#bookingModal<?php echo $b['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($bookings)):
                        ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No pending or confirmed bookings for check-in</td></tr>
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

    <?php foreach ($bookings ?? [] as $b): ?>
    <div class="modal fade" id="bookingModal<?php echo $b['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking <?php echo htmlspecialchars($b['reference']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Status:</strong> <?php echo getBookingStatusBadge($b['booking_status']); ?></p>
                    <p><strong>Guest:</strong> <?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')); ?></p>
                    <p><strong>Room:</strong> <?php echo htmlspecialchars($b['room_number'] ?? 'Not assigned'); ?></p>
                    <p><strong>Check In:</strong> <?php echo formatDate($b['check_in_date']); ?></p>
                    <p><strong>Check Out:</strong> <?php echo formatDate($b['check_out_date']); ?></p>
                    <p><strong>Total:</strong> <?php echo formatMoney($b['total_amount'] ?? 0); ?></p>
                    <p><strong>Source:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $b['booking_source'] ?? 'N/A'))); ?></p>
                </div>
                <div class="modal-footer">
                    <a href="?booking_id=<?php echo $b['id']; ?>" class="btn btn-success"><i class="bi bi-box-arrow-in-right"></i> Check-in</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
