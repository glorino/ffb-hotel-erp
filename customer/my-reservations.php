<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'My Reservations';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$customer_id = $_SESSION['customer_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
if (!$customer_id) {
    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer_id = $stmt->fetchColumn();
    if ($customer_id) $_SESSION['customer_id'] = $customer_id;
}

if (isset($_GET['cancel']) && (int)$_GET['cancel']) {
    try {
        $stmt = $db->prepare("UPDATE table_reservations SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status IN ('pending','confirmed')");
        $stmt->execute([(int)$_GET['cancel'], $customer_id]);
        if ($stmt->rowCount()) {
            set_flash('success', 'Reservation cancelled successfully');
        } else {
            set_flash('warning', 'Reservation cannot be cancelled or not found');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: my-reservations.php'); exit;
}

$status_filter = $_GET['status'] ?? '';
$where = "r.customer_id = ?"; $params = [$customer_id];
if ($status_filter) { $where .= " AND r.status = ?"; $params[] = $status_filter; }

$stmt = $db->prepare("SELECT r.*, br.name as branch_name FROM table_reservations r LEFT JOIN branches br ON r.branch_id = br.id WHERE $where ORDER BY r.reservation_date DESC, r.reservation_time DESC");
$stmt->execute($params);
$reservations = $stmt->fetchAll();
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">My Reservations</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <a href="?status=" class="btn btn-sm <?php echo !$status_filter ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                    <a href="?status=pending" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Pending</a>
                    <a href="?status=confirmed" class="btn btn-sm <?php echo $status_filter === 'confirmed' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Confirmed</a>
                    <a href="?status=seated" class="btn btn-sm <?php echo $status_filter === 'seated' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Seated</a>
                    <a href="?status=cancelled" class="btn btn-sm <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Cancelled</a>
                </div>
                <a href="<?php echo $base_url; ?>reservation.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Reservation</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($reservations as $r):
            $badge_map = ['pending'=>'warning','confirmed'=>'info','seated'=>'success','cancelled'=>'danger'];
            $bc = $badge_map[$r['status']] ?? 'secondary';
        ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($r['branch_name'] ?? 'Restaurant'); ?></h6>
                            <small class="text-muted">Reservation</small>
                        </div>
                        <span class="badge bg-<?php echo $bc; ?>"><?php echo htmlspecialchars(ucfirst($r['status'])); ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="text-center bg-light rounded p-2 px-3">
                            <div class="fw-bold fs-5"><?php echo date('d', strtotime($r['reservation_date'])); ?></div>
                            <small class="text-muted"><?php echo date('M', strtotime($r['reservation_date'])); ?></small>
                        </div>
                        <div>
                            <p class="mb-1"><i class="bi bi-clock me-1"></i> <?php echo htmlspecialchars(date('h:i A', strtotime($r['reservation_time']))); ?></p>
                            <p class="mb-0"><i class="bi bi-people me-1"></i> <?php echo (int)$r['guests']; ?> guest<?php echo $r['guests'] > 1 ? 's' : ''; ?></p>
                            <?php if (!empty($r['notes'])): ?>
                            <small class="text-muted"><i class="bi bi-chat-quote me-1"></i> <?php echo htmlspecialchars($r['notes']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
                    <div class="mt-3">
                        <a href="?cancel=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this reservation?')"><i class="bi bi-x-circle"></i> Cancel Reservation</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; if (empty($reservations)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-clock-history display-4 text-muted"></i>
                    <h5 class="mt-3">No Reservations</h5>
                    <p class="text-muted">You haven't made any table reservations yet.</p>
                    <a href="<?php echo $base_url; ?>reservation.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Make a Reservation</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
