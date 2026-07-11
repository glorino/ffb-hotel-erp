<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'My Profile';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$user_id = $_SESSION['user_id'] ?? 0;
$customer_id = $_SESSION['customer_id'] ?? 0;

if (!$customer_id) {
    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer_id = $stmt->fetchColumn();
    if ($customer_id) $_SESSION['customer_id'] = $customer_id;
}

$user = getUser($user_id);

$customer = [];
if ($customer_id) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $email = $_POST['email'] ?? '';
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $email, $user_id]);
            if ($customer_id) {
                $stmt = $db->prepare("UPDATE customers SET full_name = ?, phone = ?, email = ?, address = ? WHERE id = ?");
                $stmt->execute([$full_name, $phone, $email, $address, $customer_id]);
            }
            $db->commit();
            set_flash('success', 'Profile updated successfully');
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
        header('Location: profile.php'); exit;
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        try {
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $hash = $stmt->fetchColumn();
            if (!password_verify($current, $hash)) {
                set_flash('danger', 'Current password is incorrect');
            } elseif ($new !== $confirm) {
                set_flash('danger', 'New passwords do not match');
            } elseif (strlen($new) < 6) {
                set_flash('danger', 'Password must be at least 6 characters');
            } else {
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
                set_flash('success', 'Password changed successfully');
            }
        } catch (Exception $e) {
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
        header('Location: profile.php'); exit;
    }

    if (isset($_POST['update_preferences'])) {
        set_flash('success', 'Preferences updated');
        header('Location: profile.php'); exit;
    }
}
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">My Profile</li>
        </ol>
    </nav>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-4">
                    <div class="mb-3">
                        <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo $base_url . htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="rounded-circle" width="120" height="120" style="object-fit:cover;">
                        <?php else: ?>
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white" style="width:120px;height:120px;font-size:42px;font-weight:600;">
                            <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-semibold mb-1"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h5>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    <span class="badge bg-primary">Customer</span>
                    <hr>
                    <div class="text-start small">
                        <p class="mb-2"><i class="bi bi-telephone me-2"></i> <?php echo htmlspecialchars($user['phone'] ?? '—'); ?></p>
                        <p class="mb-2"><i class="bi bi-envelope me-2"></i> <?php echo htmlspecialchars($user['email'] ?? '—'); ?></p>
                        <p class="mb-0"><i class="bi bi-calendar me-2"></i> Member since <?php echo $user['created_at'] ? date('M Y', strtotime($user['created_at'])) : '—'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-person"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? $customer['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? $customer['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? $customer['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary mt-3"><i class="bi bi-check-lg"></i> Update Profile</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-lock"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning mt-3"><i class="bi bi-key"></i> Change Password</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-bell"></i> Notification Preferences</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <p class="text-muted small">Notification preferences are managed by the system administrator.</p>
                        <button type="submit" name="update_preferences" class="btn btn-outline-primary"><i class="bi bi-save"></i> Save Preferences</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
