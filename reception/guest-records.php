<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Guest Records';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/reception-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

$search = $_GET['search'] ?? '';
$search_type = $_GET['search_type'] ?? 'name';
$guest_id = $_GET['view'] ?? 0;
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Guest Records</li>
        </ol>
    </nav>

    <?php if ($guest_id): ?>
    <?php
    try {
        $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$guest_id]);
        $guest = $stmt->fetch();
        if (!$guest) throw new Exception('Guest not found');
    ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">Guest Profile</h5>
            <a href="guest-records.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <h6>Personal Information</h6>
                    <p class="mb-1"><strong><?php echo htmlspecialchars(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? '')); ?></strong></p>
                    <p class="mb-1">Email: <?php echo htmlspecialchars($guest['email'] ?? 'N/A'); ?></p>
                    <p class="mb-1">Phone: <?php echo htmlspecialchars($guest['phone'] ?? 'N/A'); ?></p>
                    <p class="mb-0">ID: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $guest['id_type'] ?? 'N/A'))); ?> — <?php echo htmlspecialchars($guest['id_number'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                    <h6>Statistics</h6>
                    <?php
                    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
                    $stmt->execute([$guest_id]);
                    $total_visits = $stmt->fetchColumn();

                    $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE customer_id = ? AND booking_status NOT IN ('cancelled')");
                    $stmt->execute([$guest_id]);
                    $total_spent = $stmt->fetchColumn();

                    $stmt = $db->prepare("SELECT MAX(created_at) FROM bookings WHERE customer_id = ?");
                    $stmt->execute([$guest_id]);
                    $last_visit = $stmt->fetchColumn();
                    ?>
                    <p class="mb-1">Total Visits: <strong><?php echo $total_visits; ?></strong></p>
                    <p class="mb-1">Total Spent: <strong><?php echo formatMoney($total_spent); ?></strong></p>
                    <p class="mb-0">Last Visit: <strong><?php echo $last_visit ? formatDate($last_visit) : 'N/A'; ?></strong></p>
                </div>
            </div>

            <h6 class="fw-semibold mb-3">Booking History</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Reference</th><th>Room</th><th>Check In</th><th>Check Out</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->prepare("SELECT b.*, rm.room_number FROM bookings b LEFT JOIN rooms rm ON b.room_id = rm.id WHERE b.customer_id = ? ORDER BY b.created_at DESC LIMIT 20");
                        $stmt->execute([$guest_id]);
                        while ($b = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['reference']); ?></td>
                            <td><?php echo htmlspecialchars($b['room_number'] ?? 'N/A'); ?></td>
                            <td><?php echo formatDate($b['check_in_date']); ?></td>
                            <td><?php echo formatDate($b['check_out_date']); ?></td>
                            <td><?php echo formatMoney($b['total_amount'] ?? 0); ?></td>
                            <td><?php echo getBookingStatusBadge($b['booking_status']); ?></td>
                            <td><small><?php echo formatDate($b['created_at']); ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <h6 class="fw-semibold mb-3 mt-4">Payment History</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Reference</th><th>Amount</th><th>Method</th><th>Category</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->prepare("SELECT p.*, b.reference as booking_ref FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE p.customer_id = ? ORDER BY p.created_at DESC LIMIT 20");
                        $stmt->execute([$guest_id]);
                        while ($p = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><small><?php echo htmlspecialchars($p['reference'] ?? $p['id']); ?></small></td>
                            <td><?php echo formatMoney($p['amount']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $p['payment_method'] ?? ''))); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($p['payment_category'] ?? '')); ?></td>
                            <td><?php echo getPaymentStatusBadge($p['status']); ?></td>
                            <td><small><?php echo formatDate($p['created_at']); ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <?php else: ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Search by</label>
                    <select name="search_type" class="form-select form-select-sm">
                        <option value="name" <?php echo $search_type === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="email" <?php echo $search_type === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="phone" <?php echo $search_type === 'phone' ? 'selected' : ''; ?>>Phone</option>
                        <option value="id_number" <?php echo $search_type === 'id_number' ? 'selected' : ''; ?>>ID Number</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Type to search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Search</button>
                </div>
                <div class="col-md-2">
                    <a href="guest-records.php" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">Guest Records</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>ID Type/Number</th>
                            <th>Total Visits</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT c.*, 
                                    (SELECT COUNT(*) FROM bookings WHERE customer_id = c.id) as total_visits,
                                    (SELECT MAX(created_at) FROM bookings WHERE customer_id = c.id) as last_visit
                                    FROM customers c WHERE (c.branch_id = ? OR c.id IN (SELECT customer_id FROM bookings WHERE branch_id = ?))";
                            $params = [$branch_id, $branch_id];
                            if ($search) {
                                $col = match($search_type) {
                                    'email' => 'c.email',
                                    'phone' => 'c.phone',
                                    'id_number' => 'c.id_number',
                                    default => "CONCAT(c.first_name, ' ', c.last_name)"
                                };
                                $sql .= " AND $col LIKE ?";
                                $params[] = "%$search%";
                            }
                            $sql .= " ORDER BY last_visit DESC LIMIT 100";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $guests = $stmt->fetchAll();
                            foreach ($guests as $g):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? '')); ?></strong></td>
                            <td><small><?php echo htmlspecialchars($g['email'] ?? '-'); ?></small></td>
                            <td><small><?php echo htmlspecialchars($g['phone'] ?? '-'); ?></small></td>
                            <td><small><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $g['id_type'] ?? ''))); ?> <?php echo htmlspecialchars($g['id_number'] ?? ''); ?></small></td>
                            <td><span class="badge bg-secondary"><?php echo $g['total_visits'] ?? 0; ?></span></td>
                            <td><small class="text-muted"><?php echo $g['last_visit'] ? timeAgo($g['last_visit']) : '-'; ?></small></td>
                            <td>
                                <a href="?view=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> View</a>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($guests)):
                        ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No guest records found</td></tr>
                        <?php
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="7" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
