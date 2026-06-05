<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Manage Bookings';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/admin-sidebar.php';

$db = getDB();

if (isset($_GET['action']) && isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    try {
        $valid_actions = ['confirm' => 'confirmed', 'cancel' => 'cancelled', 'checkin' => 'checked_in', 'checkout' => 'checked_out'];
        if (isset($valid_actions[$action])) {
            $stmt = $db->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
            $stmt->execute([$valid_actions[$action], $id]);
            log_audit('update', 'booking', $id, null, ['booking_status' => $valid_actions[$action]]);
            echo '<div class="alert alert-success alert-dismissible fade show">Booking ' . htmlspecialchars($action) . 'ed successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } catch (Exception $e) { echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>'; }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Bookings</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">All Bookings</h4>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->query("
                                SELECT b.*, c.full_name, c.email as customer_email, c.phone as customer_phone,
                                       br.name as branch_name, r.room_number
                                FROM bookings b
                                JOIN customers c ON b.customer_id = c.id
                                JOIN branches br ON b.branch_id = br.id
                                LEFT JOIN rooms r ON b.room_id = r.id
                                ORDER BY b.created_at DESC LIMIT 200
                            ");
                            $bookings = $stmt->fetchAll();
                            foreach ($bookings as $bk):
                        ?>
                        <tr>
                            <td class="fw-medium"><small><?php echo htmlspecialchars($bk['booking_reference']); ?></small></td>
                            <td>
                                <strong><?php echo htmlspecialchars($bk['full_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($bk['customer_email'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($bk['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($bk['room_number'] ?? 'N/A'); ?></td>
                            <td><small><?php echo formatDate($bk['check_in_date']); ?></small></td>
                            <td><small><?php echo formatDate($bk['check_out_date']); ?></small></td>
                            <td><?php echo formatMoney($bk['total_amount']); ?></td>
                            <td><?php echo getBookingStatusBadge($bk['booking_status']); ?></td>
                            <td><?php echo getPaymentStatusBadge($bk['payment_status']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="?action=confirm&id=<?php echo $bk['id']; ?>" class="btn btn-outline-success" title="Confirm"><i class="bi bi-check-lg"></i></a>
                                    <a href="?action=checkin&id=<?php echo $bk['id']; ?>" class="btn btn-outline-info" title="Check In"><i class="bi bi-box-arrow-in-right"></i></a>
                                    <a href="?action=checkout&id=<?php echo $bk['id']; ?>" class="btn btn-outline-secondary" title="Check Out"><i class="bi bi-box-arrow-right"></i></a>
                                    <a href="?action=cancel&id=<?php echo $bk['id']; ?>" class="btn btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this booking?')"><i class="bi bi-x-lg"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?><tr><td colspan="10" class="text-center py-4 text-muted">No bookings found.</td></tr><?php endif; ?>
                        <?php } catch (Exception $e) { echo '<tr><td colspan="10" class="text-danger">' . htmlspecialchars($e->getMessage()) . '</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
