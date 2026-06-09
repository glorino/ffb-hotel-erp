<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Room Availability';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

$check_in = $_GET['check_in'] ?? date('Y-m-d');
$check_out = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
$room_type = $_GET['room_type'] ?? '';
$show_available = isset($_GET['show_available']) && $_GET['show_available'] == 1;
if (!validateDate($check_in)) $check_in = date('Y-m-d');
if (!validateDate($check_out)) $check_out = date('Y-m-d', strtotime('+1 day'));
if ($check_out <= $check_in) $check_out = date('Y-m-d', strtotime($check_in . '+1 day'));
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Room Availability</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Check In</label>
                    <input type="date" name="check_in" class="form-control form-control-sm" value="<?php echo $check_in; ?>" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Check Out</label>
                    <input type="date" name="check_out" class="form-control form-control-sm" value="<?php echo $check_out; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Room Type</label>
                    <select name="room_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <?php
                        $types = $db->prepare("SELECT id, name FROM room_types WHERE status = 'active'");
                        $types->execute();
                        while ($t = $types->fetch()):
                        ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo $room_type == $t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="show_available" value="1" id="showAvailable" <?php echo $show_available ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <label class="form-check-label small" for="showAvailable">Available only</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Check</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    $nights = calculateNights($check_in, $check_out);

    $booked_room_ids = [];
    try {
        $stmt = $db->prepare("SELECT DISTINCT room_id FROM bookings WHERE branch_id = ? AND booking_status IN ('confirmed', 'checked_in') AND ((check_in_date <= ? AND check_out_date > ?) OR (check_in_date < ? AND check_out_date >= ?))");
        $stmt->execute([$branch_id, $check_out, $check_in, $check_out, $check_in]);
        while ($row = $stmt->fetch()) { $booked_room_ids[] = $row['room_id']; }
    } catch (Exception $e) {}

    try {
        $sql = "SELECT r.*, rt.name as type_name, rt.base_price, rt.capacity FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.branch_id = ?";
        $params = [$branch_id];
        if ($room_type) { $sql .= " AND r.room_type_id = ?"; $params[] = $room_type; }
        $sql .= " ORDER BY r.floor, r.room_number";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();
    } catch (Exception $e) {
        $rooms = [];
    }

    $room_statuses = [];
    foreach ($rooms as $r) {
        if ($r['status'] === 'occupied' || in_array($r['id'], $booked_room_ids)) {
            $room_statuses[$r['id']] = 'occupied';
        } elseif ($r['status'] === 'maintenance' || $r['status'] === 'out_of_service') {
            $room_statuses[$r['id']] = 'unavailable';
        } elseif ($r['status'] === 'cleaning') {
            $room_statuses[$r['id']] = 'cleaning';
        } elseif ($r['status'] === 'reserved') {
            $room_statuses[$r['id']] = 'reserved';
        } else {
            $room_statuses[$r['id']] = 'available';
        }
    }
    ?>

    <div class="row g-2 mb-3">
        <div class="col-auto"><span class="badge bg-success px-3 py-2">Available</span></div>
        <div class="col-auto"><span class="badge bg-warning text-dark px-3 py-2">Reserved</span></div>
        <div class="col-auto"><span class="badge bg-danger px-3 py-2">Occupied</span></div>
        <div class="col-auto"><span class="badge bg-secondary px-3 py-2">Cleaning</span></div>
        <div class="col-auto"><span class="badge bg-dark px-3 py-2">Unavailable</span></div>
        <div class="col-auto ms-auto">
            <strong><?php echo count($rooms); ?></strong> rooms &middot; <strong class="text-success"><?php echo count(array_filter($room_statuses, fn($s) => $s === 'available')); ?></strong> available
        </div>
    </div>

    <?php
    $grouped = [];
    foreach ($rooms as $r) {
        $floor = $r['floor'];
        if (!isset($grouped[$floor])) $grouped[$floor] = [];
        $grouped[$floor][] = $r;
    }
    ksort($grouped);
    ?>

    <?php foreach ($grouped as $floor => $floor_rooms): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="bi bi-layers me-2"></i>Floor <?php echo htmlspecialchars($floor); ?></h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <?php foreach ($floor_rooms as $r):
                    $status = $room_statuses[$r['id']] ?? 'available';
                    $bg = match($status) { 'available'=>'bg-success', 'reserved'=>'bg-warning text-dark', 'occupied'=>'bg-danger', 'cleaning'=>'bg-secondary', default=>'bg-dark' };
                    if ($show_available && $status !== 'available') continue;
                ?>
                <div class="col-lg-2 col-md-3 col-4">
                    <div class="card <?php echo $bg; ?> text-white border-0 room-card" style="cursor:pointer;" data-room-id="<?php echo $r['id']; ?>" data-room-number="<?php echo htmlspecialchars($r['room_number']); ?>" data-type="<?php echo htmlspecialchars($r['type_name']); ?>" data-price="<?php echo $r['base_price']; ?>" data-capacity="<?php echo $r['capacity']; ?>" data-status="<?php echo $status; ?>" onclick="selectRoom(this)">
                        <div class="card-body text-center py-3">
                            <h5 class="mb-0"><?php echo htmlspecialchars($r['room_number']); ?></h5>
                            <small><?php echo htmlspecialchars($r['type_name']); ?></small>
                            <div class="mt-1"><strong><?php echo formatMoney($r['base_price']); ?><small>/night</small></strong></div>
                            <div class="mt-1">
                                <?php if ($status === 'available'): ?>
                                <i class="bi bi-check-circle"></i> Available
                                <?php elseif ($status === 'reserved'): ?>
                                <i class="bi bi-clock"></i> Reserved
                                <?php elseif ($status === 'occupied'): ?>
                                <i class="bi bi-person-fill"></i> Occupied
                                <?php elseif ($status === 'cleaning'): ?>
                                <i class="bi bi-broom"></i> Cleaning
                                <?php else: ?>
                                <i class="bi bi-x-circle"></i> Unavailable
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if ($show_available && empty(array_filter($room_statuses, fn($s) => $s === 'available'))): ?>
    <div class="alert alert-warning">No available rooms match your criteria.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body text-center">
            <button type="button" class="btn btn-primary btn-lg" id="bookSelectedBtn" disabled onclick="proceedToBook()">
                <i class="bi bi-calendar-plus me-2"></i>Book Selected Room
            </button>
            <small class="d-block text-muted mt-2">Click a room above to select it, then click to book</small>
        </div>
    </div>
</div>

<input type="hidden" id="selectedRoomId" value="">
<input type="hidden" id="selectedRoomNumber" value="">
<input type="hidden" id="selectedRoomPrice" value="">
<input type="hidden" id="checkInDate" value="<?php echo $check_in; ?>">
<input type="hidden" id="checkOutDate" value="<?php echo $check_out; ?>">
<input type="hidden" id="nightsCount" value="<?php echo $nights; ?>">

<script>
function selectRoom(el) {
    document.querySelectorAll('.room-card').forEach(c => c.classList.remove('border', 'border-white', 'border-3'));
    el.classList.add('border', 'border-white', 'border-3');
    document.getElementById('selectedRoomId').value = el.dataset.roomId;
    document.getElementById('selectedRoomNumber').value = el.dataset.roomNumber;
    document.getElementById('selectedRoomPrice').value = el.dataset.price;
    document.getElementById('bookSelectedBtn').disabled = false;
}

function proceedToBook() {
    const roomId = document.getElementById('selectedRoomId').value;
    const roomNumber = document.getElementById('selectedRoomNumber').value;
    const price = document.getElementById('selectedRoomPrice').value;
    const checkIn = document.getElementById('checkInDate').value;
    const checkOut = document.getElementById('checkOutDate').value;
    const nights = document.getElementById('nightsCount').value;
    if (!roomId) return;
    window.location.href = 'walk-in-booking.php?room_id=' + roomId + '&check_in=' + checkIn + '&check_out=' + checkOut + '&nights=' + nights;
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
