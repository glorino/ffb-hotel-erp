<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Manage Rooms & Suites';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/admin-sidebar.php';

$db = getDB();

$edit_id = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_room = null;
if ($edit_id) {
    try { $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?"); $stmt->execute([$edit_id]); $edit_room = $stmt->fetch(); } catch (Exception $e) {}
}

if (isset($_POST['save_room']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    try {
        $image = $edit_room['image'] ?? null;
        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'uploads/rooms/' . uniqid('room_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../' . $image);
        }
        if ($edit_id) {
            $stmt = $db->prepare("UPDATE rooms SET branch_id=?, room_type_id=?, room_number=?, floor=?, price_per_night=?, status=?, description=?, image=? WHERE id=?");
            $stmt->execute([$_POST['branch_id'], $_POST['room_type_id'], $_POST['room_number'], $_POST['floor'] ?: null, $_POST['price'], $_POST['status'], $_POST['description'] ?: null, $image, $edit_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO rooms (branch_id, room_type_id, room_number, floor, price_per_night, status, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['branch_id'], $_POST['room_type_id'], $_POST['room_number'], $_POST['floor'] ?: null, $_POST['price'], $_POST['status'] ?? 'available', $_POST['description'] ?: null, $image]);
        }
        echo '<div class="alert alert-success">Room saved.</div><meta http-equiv="refresh" content="1;url=rooms.php">';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    try { $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?"); $stmt->execute([$_GET['delete']]); echo '<div class="alert alert-success">Room deleted.</div>'; } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Rooms & Suites</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Manage Rooms & Suites</h4>
        <a href="rooms.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Room</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold"><?php echo $edit_id ? 'Edit Room' : 'Add New Room'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Select Branch</option>
                                <?php
                                $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active'");
                                while ($b = $stmt->fetch()) {
                                    $sel = ($edit_room['branch_id'] ?? '') == $b['id'] ? 'selected' : '';
                                    echo "<option value=\"{$b['id']}\" $sel>" . htmlspecialchars($b['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Room Type <span class="text-danger">*</span></label>
                            <select name="room_type_id" class="form-select" required>
                                <option value="">Select Type</option>
                                <?php
                                $stmt = $db->query("SELECT id, name, base_price FROM room_types WHERE status = 'active'");
                                while ($rt = $stmt->fetch()) {
                                    $sel = ($edit_room['room_type_id'] ?? '') == $rt['id'] ? 'selected' : '';
                                    echo "<option value=\"{$rt['id']}\" $sel>" . htmlspecialchars($rt['name']) . " (" . formatMoney($rt['base_price']) . ")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Room Number <span class="text-danger">*</span></label>
                            <input type="text" name="room_number" class="form-control" required value="<?php echo htmlspecialchars($edit_room['room_number'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Floor</label>
                            <input type="text" name="floor" class="form-control" value="<?php echo htmlspecialchars($edit_room['floor'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Night (<?php echo CURRENCY_SYMBOL; ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo htmlspecialchars($edit_room['price_per_night'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (json_decode(ROOM_STATUSES) as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($edit_room['status'] ?? 'available') === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_room['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Room Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <?php if (!empty($edit_room['image'])): ?>
                            <div class="mt-2"><img src="<?php echo $base_url . htmlspecialchars($edit_room['image']); ?>" class="img-thumbnail" style="max-height:80px;"></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="save_room" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> <?php echo $edit_id ? 'Update Room' : 'Save Room'; ?></button>
                        <?php if ($edit_id): ?><a href="rooms.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Room</th><th>Type</th><th>Branch</th><th>Floor</th><th>Price/Night</th><th>Status</th><th>Image</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("
                                        SELECT r.*, rt.name as type_name, b.name as branch_name
                                        FROM rooms r
                                        JOIN room_types rt ON r.room_type_id = rt.id
                                        JOIN branches b ON r.branch_id = b.id
                                        ORDER BY b.name, r.room_number
                                    ");
                                    $rooms = $stmt->fetchAll();
                                    foreach ($rooms as $room):
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($room['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars($room['branch_name']); ?></small></td>
                                    <td><?php echo htmlspecialchars($room['floor'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatMoney($room['price_per_night']); ?></td>
                                    <td><?php echo getRoomStatusBadge($room['status']); ?></td>
                                    <td>
                                        <?php if ($room['image']): ?>
                                            <img src="<?php echo $base_url . htmlspecialchars($room['image']); ?>" class="rounded" style="width:50px;height:40px;object-fit:cover;">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="rooms.php?edit=<?php echo $room['id']; ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            <a href="rooms.php?delete=<?php echo $room['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this room?')"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($rooms)): ?><tr><td colspan="8" class="text-center py-4 text-muted">No rooms found.</td></tr><?php endif; ?>
                                <?php } catch (Exception $e) { ?>
                                <tr><td colspan="8" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
