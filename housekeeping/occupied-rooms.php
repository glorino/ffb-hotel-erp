<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['housekeeping', 'admin', 'branch_manager']);

$page_title = 'Occupied Rooms';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND r.branch_id = " . (int)$branch_id : "";

if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $action = $_GET['action'];
        if ($action === 'needs_cleaning') {
            $stmt = $db->prepare("UPDATE rooms SET status = 'cleaning' WHERE id = ? AND branch_id = ?");
            $stmt->execute([$id, $branch_id]);
            set_flash('success', 'Room marked as needs cleaning.');
        } elseif ($action === 'request_entry') {
            $stmt = $db->prepare("INSERT INTO housekeeping_requests (room_id, request_type, requested_by, notes) VALUES (?, 'entry', ?, ?)");
            $stmt->execute([$id, $_SESSION['user_id'], $_POST['notes'] ?? 'Entry requested for cleaning']);
            set_flash('success', 'Entry request sent to reception.');
        }
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    header('Location: occupied-rooms.php');
    exit;
}

$rooms = [];
try {
    $sql = "SELECT r.*, rt.name as room_type_name, b.id as booking_id, c.full_name as guest_name, b.check_in_date, b.check_out_date, b.booking_reference FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id LEFT JOIN (SELECT b1.*, b1.customer_id FROM bookings b1 WHERE b1.booking_status IN ('checked_in', 'confirmed') ORDER BY b1.check_in_date DESC) b ON r.id = b.room_id LEFT JOIN customers c ON b.customer_id = c.id WHERE r.status = 'occupied' $branch_filter ORDER BY r.floor, r.room_number";
    $stmt = $db->query($sql);
    $rooms = $stmt->fetchAll();
} catch (Exception $e) {
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Occupied Rooms</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Occupied Rooms</h4>
    </div>

    <?php if (empty($rooms)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-house text-muted" style="font-size: 3rem;"></i>
            <h5 class="mt-3">No Occupied Rooms</h5>
            <p class="text-muted mb-0">All rooms are currently available.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Room</th>
                            <th>Floor</th>
                            <th>Type</th>
                            <th>Guest Name</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Cleaning Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($room['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($room['floor'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($room['room_type_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($room['guest_name'] ?? 'N/A'); ?></td>
                            <td><small><?php echo $room['check_in_date'] ? formatDate($room['check_in_date']) : '-'; ?></small></td>
                            <td><small><?php echo $room['check_out_date'] ? formatDate($room['check_out_date']) : '-'; ?></small></td>
                            <td>
                                <?php if ($room['status'] === 'occupied'): ?>
                                <span class="badge bg-warning text-dark">Occupied</span>
                                <?php elseif ($room['status'] === 'cleaning'): ?>
                                <span class="badge bg-info">Being Cleaned</span>
                                <?php else: ?>
                                <span class="badge bg-success"><?php echo ucfirst($room['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info" onclick="requestEntry(<?php echo $room['id']; ?>)"><i class="bi bi-door-open"></i> Request Entry</button>
                                <a href="occupied-rooms.php?action=needs_cleaning&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-warning confirm-link"><i class="bi bi-exclamation-circle"></i> Needs Cleaning</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="entryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="" id="entryForm">
                <div class="modal-header">
                    <h6 class="modal-title">Request Entry to Room</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Reason for entry..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-send"></i> Send Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function requestEntry(id) {
    document.getElementById('entryForm').action = 'occupied-rooms.php?action=request_entry&id=' + id;
    new bootstrap.Modal(document.getElementById('entryModal')).show();
}

document.querySelectorAll('.confirm-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Mark this room as needing cleaning?')) window.location.href = this.href;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
