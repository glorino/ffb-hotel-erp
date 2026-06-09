<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['branch_manager']);

$page_title = 'Staff on Duty';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Staff on Duty</li>
        </ol>
    </nav>

    <?php
    $stats = [];
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE branch_id = ? AND status = 'active'");
        $stmt->execute([$branch_id]);
        $stats['total_staff'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM staff_duty WHERE branch_id = ? AND shift_date = CURRENT_DATE AND status = 'on_duty'");
        $stmt->execute([$branch_id]);
        $stats['on_duty_today'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM staff_duty WHERE branch_id = ? AND shift_date = CURRENT_DATE AND status = 'absent'");
        $stmt->execute([$branch_id]);
        $stats['absent_today'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM staff_duty WHERE branch_id = ? AND shift_date = CURRENT_DATE AND status = 'on_leave'");
        $stmt->execute([$branch_id]);
        $stats['on_leave'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Staff duty error: ' . $e->getMessage());
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Total Active Staff</p>
                    <h3 class="stat-value mb-0"><?php echo number_format($stats['total_staff'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">On Duty Today</p>
                    <h3 class="stat-value mb-0 text-success"><?php echo number_format($stats['on_duty_today'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">Absent Today</p>
                    <h3 class="stat-value mb-0 text-danger"><?php echo number_format($stats['absent_today'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <p class="stat-label text-muted mb-1 small">On Leave</p>
                    <h3 class="stat-value mb-0 text-warning"><?php echo number_format($stats['on_leave'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">Today's Duty Roster — <?php echo date('l, F j, Y'); ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Shift</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->prepare("
                                SELECT u.first_name, u.last_name, r.name as role_name, sd.shift_type, sd.start_time, sd.end_time, sd.status
                                FROM users u
                                JOIN roles r ON u.role_id = r.id
                                LEFT JOIN staff_duty sd ON u.id = sd.user_id AND sd.shift_date = CURRENT_DATE
                                WHERE u.branch_id = ? AND r.slug != 'customer'
                                ORDER BY sd.status ASC, u.first_name ASC
                            ");
                            $stmt->execute([$branch_id]);
                            $staff = $stmt->fetchAll();
                            foreach ($staff as $s):
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#e9ecef;color:#495057;font-size:13px;font-weight:600;">
                                        <?php echo strtoupper(substr($s['first_name'] ?? '?', 0, 1) . substr($s['last_name'] ?? '?', 0, 1)); ?>
                                    </div>
                                    <strong><?php echo htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?></strong>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($s['role_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($s['shift_type']): ?>
                                <span class="badge bg-<?php echo $s['shift_type'] === 'morning' ? 'info' : ($s['shift_type'] === 'afternoon' ? 'warning' : 'dark'); ?>">
                                    <?php echo ucfirst($s['shift_type'] ?? 'N/A'); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['start_time']): ?>
                                <small><?php echo date('h:i A', strtotime($s['start_time'])); ?> - <?php echo date('h:i A', strtotime($s['end_time'])); ?></small>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status = $s['status'] ?? 'unscheduled';
                                $badge_map = ['on_duty'=>'success','absent'=>'danger','on_leave'=>'warning','unscheduled'=>'secondary'];
                                $badge_class = $badge_map[$status] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                            if (empty($staff)):
                        ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No staff records found</td></tr>
                        <?php
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="5" class="text-center py-4 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
