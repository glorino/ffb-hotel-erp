<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Manage Customers';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/admin-sidebar.php';

$db = getDB();
$search = $_GET['search'] ?? '';
$view_id = isset($_GET['view']) && ctype_digit($_GET['view']) ? (int)$_GET['view'] : null;
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Customers</li>
        </ol>
    </nav>

    <?php if ($view_id): ?>
        <?php
        try {
            $stmt = $db->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) as total_bookings,
                       (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.customer_id = c.id AND p.status = 'paid') as total_spent,
                       (SELECT COALESCE(SUM(fo.payable_amount), 0) FROM food_orders fo WHERE fo.customer_id = c.id AND fo.status != 'cancelled') as total_food_spent
                FROM customers c WHERE c.id = ?
            ");
            $stmt->execute([$view_id]);
            $customer = $stmt->fetch();
            if (!$customer) { echo '<div class="alert alert-warning">Customer not found.</div>'; $view_id = null; }
        } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; $view_id = null; }
        ?>

        <?php if ($view_id && $customer): ?>
        <a href="customers.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Back to Customers</a>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width:80px;height:80px;background:#2d3436;color:#fff;font-size:28px;">
                            <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                        </div>
                        <h5 class="fw-semibold"><?php echo htmlspecialchars($customer['full_name']); ?></h5>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($customer['email'] ?? ''); ?><br><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></p>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($customer['city'] ?? ''); ?><?php echo $customer['state'] ? ', ' . htmlspecialchars($customer['state']) : ''; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label text-muted mb-1">Total Bookings</p>
                                <h3 class="stat-value mb-0"><?php echo (int) $customer['total_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label text-muted mb-1">Room Spend</p>
                                <h3 class="stat-value mb-0"><?php echo formatMoney($customer['total_spent']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label text-muted mb-1">Food Spend</p>
                                <h3 class="stat-value mb-0"><?php echo formatMoney($customer['total_food_spent']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold">Recent Bookings</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Reference</th><th>Branch</th><th>Room</th><th>Dates</th><th>Amount</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $db->prepare("
                                        SELECT b.*, br.name as branch_name, r.room_number
                                        FROM bookings b
                                        JOIN branches br ON b.branch_id = br.id
                                        LEFT JOIN rooms r ON b.room_id = r.id
                                        WHERE b.customer_id = ? ORDER BY b.created_at DESC LIMIT 10
                                    ");
                                    $stmt->execute([$view_id]);
                                    while ($bk = $stmt->fetch()):
                                    ?>
                                    <tr>
                                        <td><small><?php echo htmlspecialchars($bk['booking_reference']); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($bk['branch_name']); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($bk['room_number'] ?? 'N/A'); ?></small></td>
                                        <td><small><?php echo formatDate($bk['check_in_date']); ?> - <?php echo formatDate($bk['check_out_date']); ?></small></td>
                                        <td><?php echo formatMoney($bk['total_amount']); ?></td>
                                        <td><?php echo getBookingStatusBadge($bk['booking_status']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!$view_id): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Bookings</th><th>Spent</th><th>Registered</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "
                                SELECT c.*,
                                       (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) as total_bookings,
                                       (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.customer_id = c.id AND p.status = 'paid') as total_spent
                                FROM customers c
                            ";
                            $params = [];
                            if ($search) {
                                $sql .= " WHERE c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?";
                                $s = "%$search%"; $params = [$s, $s, $s];
                            }
                            $sql .= " ORDER BY c.created_at DESC LIMIT 200";
                            $stmt = $db->prepare($sql); $stmt->execute($params);
                            $customers = $stmt->fetchAll();
                            foreach ($customers as $c):
                        ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($c['full_name']); ?></td>
                            <td><small><?php echo htmlspecialchars($c['email'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($c['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($c['city'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-info"><?php echo (int) $c['total_bookings']; ?></span></td>
                            <td><?php echo formatMoney($c['total_spent']); ?></td>
                            <td><small class="text-muted"><?php echo formatDate($c['created_at']); ?></small></td>
                            <td><a href="customers.php?view=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($customers)): ?><tr><td colspan="8" class="text-center py-4 text-muted">No customers found.</td></tr><?php endif; ?>
                        <?php } catch (Exception $e) { echo '<tr><td colspan="8" class="text-danger">' . htmlspecialchars($e->getMessage()) . '</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
