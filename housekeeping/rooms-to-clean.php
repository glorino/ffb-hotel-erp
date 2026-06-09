<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['housekeeping', 'admin', 'branch_manager']);

$page_title = 'Rooms to Clean';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND r.branch_id = " . (int)$branch_id : "";

if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $action = $_GET['action'];
        if ($action === 'start') {
            $stmt = $db->prepare("UPDATE rooms SET status = 'cleaning', cleaned_by = ?, cleaning_started_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            set_flash('success', 'Room marked as cleaning.');
        } elseif ($action === 'complete') {
            $stmt = $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
            $stmt->execute([$id]);
            $stmt = $db->prepare("INSERT INTO housekeeping_logs (room_id, cleaned_by, cleaned_at, notes) VALUES (?, ?, NOW(), ?)");
            $stmt->execute([$id, $_SESSION['user_id'], $_POST['notes'] ?? '']);
            set_flash('success', 'Room marked as cleaned.');
        }
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    header('Location: rooms-to-clean.php');
    exit;
}

$floor_filter = $_GET['floor'] ?? '';

$rooms = [];
try {
    $sql = "SELECT r.*, rt.name as room_type_name, b.full_name as guest_name, b.check_in_date, b.check_out_date, b.booking_status FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id LEFT JOIN bookings b ON r.id = b.room_id AND b.booking_status IN ('checked_in', 'confirmed') WHERE (r.status IN ('cleaning', 'occupied') OR b.booking_status = 'checked_out')" . ($floor_filter ? " AND r.floor = " . $db->quote($floor_filter) : "") . " $branch_filter ORDER BY CASE r.status WHEN 'occupied' THEN 0 WHEN 'cleaning' THEN 1 ELSE 2 END, r.floor, r.room_number";
    $stmt = $db->query($sql);
    $rooms = $stmt->fetchAll();
} catch (Exception $e) {
}

$floors = [];
try {
    $stmt = $db->query("SELECT DISTINCT floor FROM rooms WHERE floor IS NOT NULL AND floor != '' ORDER BY floor");
    $floors = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Rooms to Clean</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="bi bi-broom me-2"></i>Rooms to Clean</h4>
        <div>
            <select class="form-select form-select-sm" onchange="window.location.href='rooms-to-clean.php?floor='+this.value" style="width: auto;">
                <option value="">All Floors</option>
                <?php foreach ($floors as $f): ?>
                <option value="<?php echo htmlspecialchars($f); ?>" <?php echo $floor_filter === $f ? 'selected' : ''; ?>>Floor <?php echo htmlspecialchars($f); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (empty($rooms)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-emoji-smile text-success" style="font-size: 3rem;"></i>
            <h5 class="mt-3 text-success">All Rooms Are Clean</h5>
            <p class="text-muted mb-0">No rooms currently need cleaning.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($rooms as $room):
            $reason = 'Daily cleaning';
            $priority = 'normal';
            if ($room['booking_status'] === 'checked_out') {
                $reason = 'Check-out cleaning';
                $priority = 'high';
            } elseif ($room['status'] === 'occupied') {
                $reason = 'Daily cleaning';
                $priority = 'normal';
            } elseif ($room['status'] === 'cleaning') {
                $reason = 'In progress';
                $priority = 'normal';
            }
            $priority_class = $priority === 'high' ? 'bg-danger' : ($priority === 'urgent' ? 'bg-danger text-white' : 'bg-warning text-dark');
        ?>
        <div class="col-xl-4 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($room['room_number']); ?></h5>
                            <small class="text-muted">Floor <?php echo htmlspecialchars($room['floor'] ?? 'N/A'); ?> &middot; <?php echo htmlspecialchars($room['room_type_name'] ?? 'N/A'); ?></small>
                        </div>
                        <span class="badge <?php echo $priority_class; ?>"><?php echo ucfirst($priority); ?> Priority</span>
                    </div>
                    <p class="mb-2"><i class="bi bi-info-circle me-1"></i> <?php echo $reason; ?></p>
                    <?php if ($room['guest_name']): ?>
                    <p class="mb-2 small"><i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($room['guest_name']); ?>
                        <?php if ($room['check_in_date']): ?>&middot; Check-in: <?php echo formatDate($room['check_in_date']); ?><?php endif; ?>
                        <?php if ($room['check_out_date']): ?>&middot; Check-out: <?php echo formatDate($room['check_out_date']); ?><?php endif; ?>
                    </p>
                    <?php endif; ?>
                    <div class="mt-3 d-flex gap-2">
                        <?php if ($room['status'] !== 'cleaning'): ?>
                        <a href="rooms-to-clean.php?action=start&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-play-fill"></i> Start Cleaning</a>
                        <?php else: ?>
                        <button class="btn btn-sm btn-success" onclick="completeClean(<?php echo $room['id']; ?>)"><i class="bi bi-check-lg"></i> Mark Cleaned</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="rooms-to-clean.php?action=complete&id=" id="completeForm">
                <div class="modal-header">
                    <h6 class="modal-title">Mark as Cleaned</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Cleaning notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Confirm Cleaned</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function completeClean(id) {
    document.getElementById('completeForm').action = 'rooms-to-clean.php?action=complete&id=' + id;
    new bootstrap.Modal(document.getElementById('completeModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
