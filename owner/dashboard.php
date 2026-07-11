<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['business_owner']);

$page_title = 'Owner Dashboard';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$today = date('Y-m-d');
$stats = [];

try {
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'");
    $stats['total_revenue'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND DATE(created_at) = ?");
    $stmt->execute([$today]);
    $stats['today_revenue'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status IN ('confirmed', 'checked_in')");
    $stats['active_bookings'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM rooms");
    $total_rooms = $stmt->fetchColumn();
    $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'");
    $occupied_rooms = $stmt->fetchColumn();
    $stats['occupancy_rate'] = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100) : 0;

    $stmt = $db->query("SELECT COUNT(*) FROM branches WHERE status = 'active'");
    $stats['total_branches'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM customers");
    $stats['total_customers'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
    $stats['pending_payments'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_level AND status = 'active'");
    $stats['low_stock'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
    $stats['available_rooms'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM food_orders WHERE status IN ('pending','preparing')");
    $stats['active_orders'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}
?>

<div class="welcome-banner">
    <h3><?php echo $page_greeting; ?>, <?php echo htmlspecialchars(explode(' ', $current_user['full_name'] ?? 'Owner')[0]); ?>!</h3>
    <p>Here's what's happening across your hotel today. You have <strong><?php echo number_format($stats['active_bookings'] ?? 0); ?></strong> active bookings and <strong><?php echo number_format($stats['pending_payments'] ?? 0); ?></strong> pending payments.</p>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-icon gold"><i class="bi bi-currency-dollar"></i></div>
        </div>
        <div class="stat-value"><?php echo formatMoney($stats['today_revenue'] ?? 0); ?></div>
        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> <?php echo date('M j, Y'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-label">Occupancy Rate</div>
            <div class="stat-icon green"><i class="bi bi-door-open"></i></div>
        </div>
        <div class="stat-value"><?php echo $stats['occupancy_rate']; ?>%</div>
        <div class="stat-change"><small class="text-muted"><?php echo $stats['available_rooms'] ?? 0; ?> rooms available</small></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-label">Active Bookings</div>
            <div class="stat-icon blue"><i class="bi bi-calendar-check"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['active_bookings'] ?? 0); ?></div>
        <div class="stat-change"><small class="text-muted">Confirmed & checked-in</small></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-label">Active Orders</div>
            <div class="stat-icon orange"><i class="bi bi-cup-hot"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['active_orders'] ?? 0); ?></div>
        <div class="stat-change"><small class="text-muted">Kitchen pending</small></div>
    </div>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-icon purple"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
        <div class="stat-value" style="font-size:1.4rem;"><?php echo formatMoney($stats['total_revenue'] ?? 0); ?></div>
        <div class="stat-change"><small class="text-muted">Lifetime earnings</small></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-label">Branches</div>
            <div class="stat-icon blue"><i class="bi bi-geo-alt"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_branches'] ?? 0); ?></div>
        <div class="stat-change"><small class="text-muted">Active locations</small></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-label">Customers</div>
            <div class="stat-icon green"><i class="bi bi-people"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_customers'] ?? 0); ?></div>
        <div class="stat-change"><small class="text-muted">Registered guests</small></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-label">Alerts</div>
            <div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['low_stock'] ?? 0); ?></div>
        <div class="stat-change"><small class="text-muted">Low stock items</small></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0 fw-semibold">Revenue Trends</h5>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary active" data-days="7">7 Days</button>
                    <button type="button" class="btn btn-outline-primary" data-days="30">30 Days</button>
                    <button type="button" class="btn btn-outline-primary" data-days="90">90 Days</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h5 class="card-title mb-0 fw-semibold">Booking Status</h5>
            </div>
            <div class="card-body">
                <canvas id="bookingChart" height="260"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h5 class="card-title mb-0 fw-semibold">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="send-notification.php" class="quick-action-card">
                        <div class="qa-icon" style="background:var(--info-bg);color:var(--info);"><i class="bi bi-megaphone"></i></div>
                        <span class="qa-label">Send Notice</span>
                    </a>
                    <a href="bookings.php" class="quick-action-card">
                        <div class="qa-icon" style="background:var(--success-bg);color:var(--success);"><i class="bi bi-calendar-plus"></i></div>
                        <span class="qa-label">New Booking</span>
                    </a>
                    <a href="staff.php" class="quick-action-card">
                        <div class="qa-icon" style="background:var(--warning-bg);color:var(--warning);"><i class="bi bi-person-badge"></i></div>
                        <span class="qa-label">Manage Staff</span>
                    </a>
                    <a href="reports.php" class="quick-action-card">
                        <div class="qa-icon" style="background:#f5f3ff;color:#8b5cf6;"><i class="bi bi-file-earmark-bar-graph"></i></div>
                        <span class="qa-label">Reports</span>
                    </a>
                    <a href="inventory-overview.php" class="quick-action-card">
                        <div class="qa-icon" style="background:var(--danger-bg);color:var(--danger);"><i class="bi bi-box-seam"></i></div>
                        <span class="qa-label">Inventory</span>
                    </a>
                    <a href="settings.php" class="quick-action-card">
                        <div class="qa-icon" style="background:var(--bg-main);color:var(--text-muted);"><i class="bi bi-gear"></i></div>
                        <span class="qa-label">Settings</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0 fw-semibold">Recent Activity</h5>
                <a href="reports.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <ul class="activity-feed">
                    <?php
                    try {
                        $stmt = $db->query("
                            SELECT 'booking' as type, b.created_at, c.full_name as actor, b.booking_reference as ref, b.booking_status as status
                            FROM bookings b JOIN customers c ON b.customer_id = c.id
                            ORDER BY b.created_at DESC LIMIT 5
                        ");
                        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $stmt = $db->query("
                            SELECT 'payment' as type, p.created_at, COALESCE(c.full_name, 'System') as actor, p.payment_reference as ref, p.status as status
                            FROM payments p LEFT JOIN customers c ON p.customer_id = c.id
                            ORDER BY p.created_at DESC LIMIT 5
                        ");
                        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $all = array_merge($activities, $payments);
                        usort($all, function($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });
                        $all = array_slice($all, 0, 8);

                        if (empty($all)) {
                            echo '<li class="text-center text-muted py-3">No recent activity</li>';
                        }

                        foreach ($all as $act):
                            $dot_color = 'info';
                            $icon = 'bi-bell';
                            if ($act['type'] === 'booking') {
                                $dot_color = in_array($act['status'], ['confirmed','checked_in']) ? 'success' : ($act['status'] === 'cancelled' ? 'danger' : 'warning');
                                $icon = 'bi-calendar-check';
                            } else {
                                $dot_color = $act['status'] === 'paid' ? 'success' : ($act['status'] === 'failed' ? 'danger' : 'warning');
                                $icon = 'bi-credit-card';
                            }
                    ?>
                    <li class="activity-item">
                        <div class="activity-dot <?php echo $dot_color; ?>"></div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <strong><?php echo htmlspecialchars($act['actor']); ?></strong>
                                <?php echo $act['type'] === 'booking' ? 'made a booking' : 'made a payment'; ?>
                                <span class="text-muted">#<?php echo htmlspecialchars($act['ref']); ?></span>
                            </div>
                            <div class="activity-time"><?php echo timeAgo($act['created_at']); ?></div>
                        </div>
                    </li>
                    <?php endforeach; } catch (Exception $e) {} ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0 fw-semibold">Recent Bookings</h5>
                <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $db->query("
                                    SELECT b.*, c.full_name as customer_name
                                    FROM bookings b
                                    JOIN customers c ON b.customer_id = c.id
                                    ORDER BY b.created_at DESC LIMIT 8
                                ");
                                $recent_bookings = $stmt->fetchAll();
                                if (empty($recent_bookings)) {
                                    echo '<tr><td colspan="5" class="text-center text-muted py-3">No bookings yet</td></tr>';
                                }
                                foreach ($recent_bookings as $bk):
                            ?>
                            <tr>
                                <td><span class="fw-medium" style="color:var(--info);"><?php echo htmlspecialchars($bk['booking_reference']); ?></span></td>
                                <td><?php echo htmlspecialchars($bk['customer_name']); ?></td>
                                <td><?php echo formatMoney($bk['total_amount']); ?></td>
                                <td><?php echo getBookingStatusBadge($bk['booking_status']); ?></td>
                                <td><small class="text-muted"><?php echo formatDate($bk['created_at']); ?></small></td>
                            </tr>
                            <?php
                                endforeach;
                            } catch (Exception $e) {
                                echo '<tr><td colspan="5" class="text-danger">Error loading bookings</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0 fw-semibold">Recent Payments</h5>
                <a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $db->query("
                                    SELECT p.*, c.full_name as customer_name
                                    FROM payments p
                                    LEFT JOIN customers c ON p.customer_id = c.id
                                    ORDER BY p.created_at DESC LIMIT 8
                                ");
                                $recent_payments = $stmt->fetchAll();
                                if (empty($recent_payments)) {
                                    echo '<tr><td colspan="5" class="text-center text-muted py-3">No payments yet</td></tr>';
                                }
                                foreach ($recent_payments as $pmt):
                            ?>
                            <tr>
                                <td><small><?php echo htmlspecialchars($pmt['payment_reference']); ?></small></td>
                                <td><?php echo htmlspecialchars($pmt['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatMoney($pmt['amount']); ?></td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars(ucfirst($pmt['method'])); ?></span></td>
                                <td><?php echo getPaymentStatusBadge($pmt['status']); ?></td>
                            </tr>
                            <?php
                                endforeach;
                            } catch (Exception $e) {
                                echo '<tr><td colspan="5" class="text-danger">Error loading payments</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$revenue_labels = [];
$revenue_data = [];
try {
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $revenue_labels[] = date('D', strtotime($day));
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND DATE(created_at) = ?");
        $stmt->execute([$day]);
        $revenue_data[] = (float) $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $revenue_labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $revenue_data = [0,0,0,0,0,0,0];
}

$booking_status_counts = ['pending'=>0,'confirmed'=>0,'checked_in'=>0,'checked_out'=>0,'cancelled'=>0];
try {
    $stmt = $db->query("SELECT booking_status, COUNT(*) as cnt FROM bookings GROUP BY booking_status");
    while ($row = $stmt->fetch()) {
        if (isset($booking_status_counts[$row['booking_status']])) {
            $booking_status_counts[$row['booking_status']] = (int) $row['cnt'];
        }
    }
} catch (Exception $e) {}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var currencySymbol = '<?php echo CURRENCY_SYMBOL; ?>';
    var revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        var revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($revenue_labels); ?>,
                datasets: [{
                    label: 'Revenue (' + currencySymbol + ')',
                    data: <?php echo json_encode($revenue_data); ?>,
                    borderColor: '#d4af37',
                    backgroundColor: 'rgba(212,175,55,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#d4af37',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                    borderWidth: 2.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { callback: function(v) { return currencySymbol + v.toLocaleString(); }, font: { size: 11 } }
                    },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });

        document.querySelectorAll('[data-days]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var days = this.getAttribute('data-days');
                document.querySelectorAll('[data-days]').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
                fetch('<?php echo $base_url; ?>ajax/revenue-data.php?days=' + days)
                    .then(function(r) { return r.json(); })
                    .then(function(json) {
                        revenueChart.data.labels = json.labels;
                        revenueChart.data.datasets[0].data = json.data;
                        revenueChart.update();
                    })
                    .catch(function(err) { console.error('Revenue fetch error:', err); });
            });
        });
    }

    var bookingCtx = document.getElementById('bookingChart');
    if (bookingCtx) {
        new Chart(bookingCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled'],
                datasets: [{
                    data: <?php echo json_encode(array_values($booking_status_counts)); ?>,
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#6b7280', '#ef4444'],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14, font: { size: 11 } } } },
                cutout: '68%',
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
