<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['housekeeping', 'admin', 'branch_manager', 'owner']);

$page_title = 'Housekeeping Reports';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/housekeeping-sidebar.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$branch_filter = $branch_id ? "AND r.branch_id = " . (int)$branch_id : "";
$sm_branch_filter = $branch_id ? "AND sm.branch_id = " . (int)$branch_id : "";

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$total_rooms = 0;
$cleaned_rooms = 0;
try {
    $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status != 'out_of_service' $branch_filter");
    $total_rooms = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(DISTINCT room_id) FROM housekeeping_logs WHERE DATE(cleaned_at) >= ? AND DATE(cleaned_at) <= ?" . ($branch_id ? " AND room_id IN (SELECT id FROM rooms WHERE branch_id = " . (int)$branch_id . ")" : ""));
    $stmt->execute([$from_date, $to_date]);
    $cleaned_rooms = (int)$stmt->fetchColumn();
} catch (Exception $e) {
}

$total_issues = 0;
$resolved_issues = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?" . ($branch_id ? " AND branch_id = " . (int)$branch_id : ""));
    $stmt->execute([$from_date, $to_date]);
    $total_issues = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'resolved' AND DATE(created_at) >= ? AND DATE(created_at) <= ?" . ($branch_id ? " AND branch_id = " . (int)$branch_id : ""));
    $stmt->execute([$from_date, $to_date]);
    $resolved_issues = (int)$stmt->fetchColumn();
} catch (Exception $e) {
}

$supply_usage = [];
try {
    $sql = "SELECT i.name, i.unit, COALESCE(SUM(sm.quantity), 0) as total_used FROM stock_movements sm JOIN inventory_items i ON sm.item_id = i.id WHERE sm.type = 'out' AND sm.reason = 'room_supply' AND DATE(sm.created_at) >= ? AND DATE(sm.created_at) <= ? $sm_branch_filter GROUP BY i.name, i.unit ORDER BY total_used DESC LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute([$from_date, $to_date]);
    $supply_usage = $stmt->fetchAll();
} catch (Exception $e) {
}

$clean_by_day = [];
try {
    $sql = "SELECT DATE(cleaned_at) as d, COUNT(*) as cnt FROM housekeeping_logs WHERE DATE(cleaned_at) >= ? AND DATE(cleaned_at) <= ?" . ($branch_id ? " AND room_id IN (SELECT id FROM rooms WHERE branch_id = " . (int)$branch_id . ")" : "") . " GROUP BY DATE(cleaned_at) ORDER BY d";
    $stmt = $db->prepare($sql);
    $stmt->execute([$from_date, $to_date]);
    $clean_by_day = $stmt->fetchAll();
} catch (Exception $e) {
}

$issues_by_type = [];
try {
    $sql = "SELECT issue_type, COUNT(*) as cnt FROM maintenance_requests WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?" . ($branch_id ? " AND branch_id = " . (int)$branch_id : "") . " GROUP BY issue_type ORDER BY cnt DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$from_date, $to_date]);
    $issues_by_type = $stmt->fetchAll();
} catch (Exception $e) {
}

$completion_rate = $total_rooms > 0 ? round(($cleaned_rooms / $total_rooms) * 100, 1) : 0;
$resolution_rate = $total_issues > 0 ? round(($resolved_issues / $total_issues) * 100, 1) : 0;
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Housekeeping Reports</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-text me-2"></i>Housekeeping Reports</h4>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-navy w-100"><i class="bi bi-filter"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="reports.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Total Rooms</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($total_rooms); ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle rounded-3 p-3">
                            <i class="bi bi-building text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Rooms Cleaned</p>
                            <h3 class="stat-value mb-0"><?php echo number_format($cleaned_rooms); ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle rounded-3 p-3">
                            <i class="bi bi-check2-all text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Completion Rate</p>
                            <h3 class="stat-value mb-0"><?php echo $completion_rate; ?>%</h3>
                        </div>
                        <div class="stat-icon bg-info-subtle rounded-3 p-3">
                            <i class="bi bi-graph-up text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label text-muted mb-1">Resolution Rate</p>
                            <h3 class="stat-value mb-0"><?php echo $resolution_rate; ?>%</h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle rounded-3 p-3">
                            <i class="bi bi-tools text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Cleaning Activity</h5>
                </div>
                <div class="card-body">
                    <canvas id="cleaningChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Issues by Type</h5>
                </div>
                <div class="card-body">
                    <canvas id="issuesChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Supply Usage</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Item</th><th>Quantity Used</th><th>Unit</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($supply_usage)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No supply usage data.</td></tr>
                                <?php else: ?>
                                <?php foreach ($supply_usage as $s): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($s['name']); ?></td>
                                    <td><?php echo number_format($s['total_used']); ?></td>
                                    <td><?php echo htmlspecialchars($s['unit'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Maintenance Report</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Issue Type</th><th>Count</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="3" class="fw-medium">Total Issues: <?php echo number_format($total_issues); ?>, Resolved: <?php echo number_format($resolved_issues); ?></td></tr>
                                <?php if (!empty($issues_by_type)): ?>
                                <?php foreach ($issues_by_type as $it): ?>
                                <tr>
                                    <td><?php echo ucwords(str_replace('_', ' ', $it['issue_type'])); ?></td>
                                    <td><?php echo number_format($it['cnt']); ?></td>
                                    <td><span class="badge bg-<?php echo $resolved_issues > ($total_issues / 2) ? 'success' : 'warning'; ?>"><?php echo $resolution_rate; ?>% resolved</span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$clean_labels = []; $clean_data = [];
foreach ($clean_by_day as $c) {
    $clean_labels[] = $c['d'];
    $clean_data[] = (int)$c['cnt'];
}
$issue_labels = []; $issue_data = []; $issue_colors = [];
$palette = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14'];
foreach ($issues_by_type as $idx => $it) {
    $issue_labels[] = ucwords(str_replace('_', ' ', $it['issue_type']));
    $issue_data[] = (int)$it['cnt'];
    $issue_colors[] = $palette[$idx % count($palette)];
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($clean_labels)): ?>
    new Chart(document.getElementById('cleaningChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($clean_labels); ?>,
            datasets: [{ label: 'Rooms Cleaned', data: <?php echo json_encode($clean_data); ?>, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', fill: true, tension: 0.4, pointRadius: 4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    <?php endif; ?>

    <?php if (!empty($issue_labels)): ?>
    new Chart(document.getElementById('issuesChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($issue_labels); ?>,
            datasets: [{ data: <?php echo json_encode($issue_data); ?>, backgroundColor: <?php echo json_encode($issue_colors); ?>, borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '65%' }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
