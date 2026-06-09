<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Customers';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$search = $_GET['search'] ?? '';
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Customers</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, email or phone..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">All Customers</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Total Bookings</th>
                            <th>Total Spent</th>
                            <th>Registered</th>
                        </tr>
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
                                $s = "%$search%";
                                $params = [$s, $s, $s];
                            }
                            $sql .= " ORDER BY c.created_at DESC LIMIT 200";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $customers = $stmt->fetchAll();

                            if (empty($customers)):
                        ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No customers found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($c['full_name']); ?></td>
                            <td><small><?php echo htmlspecialchars($c['email'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($c['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($c['city'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-info"><?php echo (int) $c['total_bookings']; ?></span></td>
                            <td><?php echo formatMoney($c['total_spent']); ?></td>
                            <td><small class="text-muted"><?php echo formatDate($c['created_at']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr><td colspan="7" class="text-danger"><?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
