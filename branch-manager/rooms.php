<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Branch Rooms';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    try {
        $stmt = $db->prepare("SELECT status FROM rooms WHERE id = ? AND branch_id = ?");
        $stmt->execute([$room_id, $branch_id]);
        $old_status = $stmt->fetchColumn();
        $stmt = $db->prepare("UPDATE rooms SET status = ? WHERE id = ? AND branch_id = ?");
        $stmt->execute([$new_status, $room_id, $branch_id]);
        log_audit('update_room_status', 'room', $room_id, ['status' => $old_status], ['status' => $new_status]);
        set_flash('success', 'Room status updated to ' . ucfirst(str_replace('_', ' ', $new_status)));
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: rooms.php');
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Rooms</li>
        </ol>
    </nav>

    <?php
    $type_filter = $_GET['type'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small">Room Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <?php
                        $types = $db->prepare("SELECT * FROM room_types WHERE status = 'active'");
                        $types->execute();
                        while ($t = $types->fetch()):
                        ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo $type_filter == $t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="reserved" <?php echo $status_filter === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                        <option value="occupied" <?php echo $status_filter === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="cleaning" <?php echo $status_filter === 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                        <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="rooms.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Branch Rooms</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Room #</th>
                            <th>Type</th>
                            <th>Floor</th>
                            <th>Capacity</th>
                            <th>Price/Night</th>
                            <th>Status</th>
                            <th>Current Guest</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT r.*, rt.name as type_name, rt.base_price, rt.max_guests
                                    FROM rooms r
                                    JOIN room_types rt ON r.room_type_id = rt.id
                                    WHERE r.branch_id = ?";
                            $params = [$branch_id];
                            if ($type_filter) { $sql .= " AND r.room_type_id = ?"; $params[] = $type_filter; }
                            if ($status_filter) { $sql .= " AND r.status = ?"; $params[] = $status_filter; }
                            $sql .= " ORDER BY r.room_number";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $rooms = $stmt->fetchAll();
                            foreach ($rooms as $r):
                                $guest_stmt = $db->prepare("SELECT c.full_name FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE b.room_id = ? AND b.booking_status = 'checked_in' LIMIT 1");
                                $guest_stmt->execute([$r['id']]);
                                $guest = $guest_stmt->fetch();
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['room_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['type_name']); ?></td>
                            <td><?php echo $r['floor']; ?></td>
                            <td><?php echo $r['max_guests']; ?> guests</td>
                            <td><?php echo formatMoney($r['base_price']); ?></td>
                            <td><?php echo getRoomStatusBadge($r['status']); ?></td>
                            <td><?php echo $guest ? htmlspecialchars($guest['full_name']) : '<span class="text-muted">—</span>'; ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $r['id']; ?>"><i class="bi bi-eye"></i></button>
                                    <form method="POST" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="room_id" value="<?php echo $r['id']; ?>">
                                        <select name="new_status" class="form-select form-select-sm" style="width:auto;" onchange="if(confirm('Update room status?')){this.form.submit();}">
                                            <option value="">Change</option>
                                            <option value="available">Available</option>
                                            <option value="reserved">Reserved</option>
                                            <option value="occupied">Occupied</option>
                                            <option value="cleaning">Cleaning</option>
                                            <option value="maintenance">Maintenance</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($rooms)):
                        ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No rooms found for this branch</td></tr>
                        <?php
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="8" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach ($rooms ?? [] as $r):
    $g = $db->prepare("SELECT c.full_name, c.email, c.phone FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE b.room_id = ? AND b.booking_status = 'checked_in' LIMIT 1");
    $g->execute([$r['id']]);
    $guest = $g->fetch();
?>
<div class="modal fade" id="roomModal<?php echo $r['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room <?php echo htmlspecialchars($r['room_number']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Type</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($r['type_name']); ?></dd>
                    <dt class="col-sm-5">Floor</dt>
                    <dd class="col-sm-7"><?php echo $r['floor']; ?></dd>
                    <dt class="col-sm-5">Capacity</dt>
                    <dd class="col-sm-7"><?php echo $r['max_guests']; ?></dd>
                    <dt class="col-sm-5">Price/Night</dt>
                    <dd class="col-sm-7"><?php echo formatMoney($r['base_price']); ?></dd>
                    <dt class="col-sm-5">Status</dt>
                    <dd class="col-sm-7"><?php echo getRoomStatusBadge($r['status']); ?></dd>
                    <?php if ($guest): ?>
                    <dt class="col-sm-5">Guest</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($guest['full_name']); ?></dd>
                    <dt class="col-sm-5">Email</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($guest['email'] ?? 'N/A'); ?></dd>
                    <dt class="col-sm-5">Phone</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($guest['phone'] ?? 'N/A'); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
