<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['kitchen_chef']);

$page_title = 'Kitchen Inventory Requests';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $item_name = trim($_POST['item_name'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $urgency = $_POST['urgency'] ?? 'normal';
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($item_name)) $errors[] = 'Item name is required.';
    if (!is_numeric($quantity) || $quantity <= 0) $errors[] = 'Valid quantity is required.';
    if (empty($unit)) $errors[] = 'Unit is required.';

    if (empty($errors)) {
        try {
            $ref = 'INVREQ-' . strtoupper(uniqid());
            $stmt = $db->prepare("INSERT INTO kitchen_requests (reference, branch_id, item_name, quantity, unit, urgency, notes, requested_by, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$ref, $branch_id, $item_name, $quantity, $unit, $urgency, $notes, $user_id]);
            set_flash('success', 'Inventory request submitted successfully.');
        } catch (Exception $e) {
            error_log('Inventory request error: ' . $e->getMessage());
            set_flash('danger', 'Failed to submit request. The kitchen_requests table may not exist.');
        }
    } else {
        set_flash('danger', implode(' ', $errors));
    }
    header('Location: inventory-requests.php');
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Kitchen</a></li>
            <li class="breadcrumb-item active">Inventory Requests</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold">Request Ingredients / Supplies</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" placeholder="e.g. Vegetable Oil" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" placeholder="10" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Unit <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select" required>
                                    <option value="">Select unit</option>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="g">Gram (g)</option>
                                    <option value="l">Litre (L)</option>
                                    <option value="ml">Millilitre (ml)</option>
                                    <option value="pcs">Pieces (pcs)</option>
                                    <option value="packs">Packs</option>
                                    <option value="cartons">Cartons</option>
                                    <option value="bottles">Bottles</option>
                                    <option value="bags">Bags</option>
                                    <option value="crates">Crates</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Urgency</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="urgency" value="low" id="urgLow">
                                    <label class="form-check-label" for="urgLow">Low</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="urgency" value="normal" id="urgNormal" checked>
                                    <label class="form-check-label" for="urgNormal">Normal</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="urgency" value="high" id="urgHigh">
                                    <label class="form-check-label" for="urgHigh">High</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="urgency" value="critical" id="urgCritical">
                                    <label class="form-check-label" for="urgCritical">Critical</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                        </div>
                        <button type="submit" name="submit_request" class="btn btn-primary w-100">
                            <i class="bi bi-send"></i> Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">Request History</h5>
                    <div>
                        <a href="?status=pending" class="btn btn-sm btn-outline-warning">Pending</a>
                        <a href="?status=approved" class="btn btn-sm btn-outline-success">Approved</a>
                        <a href="?status=all" class="btn btn-sm btn-outline-secondary">All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Urgency</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $status_filter = $_GET['status'] ?? 'pending';
                                    $sql = "SELECT kr.* FROM kitchen_requests kr WHERE kr.branch_id = ?";
                                    $params = [$branch_id];

                                    if ($status_filter !== 'all') {
                                        $sql .= " AND kr.status = ?";
                                        $params[] = $status_filter;
                                    }
                                    $sql .= " ORDER BY kr.created_at DESC LIMIT 50";

                                    $stmt = $db->prepare($sql);
                                    $stmt->execute($params);
                                    $requests = $stmt->fetchAll();

                                    if (empty($requests)):
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No requests found.</td>
                                </tr>
                                    <?php else: ?>
                                    <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($req['reference'] ?? 'N/A'); ?></small></td>
                                        <td><strong><?php echo htmlspecialchars($req['item_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($req['quantity'] . ' ' . $req['unit']); ?></td>
                                        <td>
                                            <?php
                                            $urgency_colors = ['low' => 'secondary', 'normal' => 'info', 'high' => 'warning', 'critical' => 'danger'];
                                            $uc = $urgency_colors[$req['urgency']] ?? 'secondary';
                                            echo "<span class='badge bg-{$uc}'>{$req['urgency']}</span>";
                                            ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo formatDateTime($req['created_at']); ?></small></td>
                                        <td>
                                            <?php
                                            $status_colors = ['pending' => 'warning', 'approved' => 'success', 'fulfilled' => 'primary', 'rejected' => 'danger'];
                                            $sc = $status_colors[$req['status']] ?? 'secondary';
                                            echo "<span class='badge bg-{$sc}'>{$req['status']}</span>";
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php } catch (Exception $e) {
                                    error_log('Inventory requests history error: ' . $e->getMessage());
                                    echo '<tr><td colspan="6" class="text-center py-4 text-danger">Error loading requests. The kitchen_requests table may not exist.</td></tr>';
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
