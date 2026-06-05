<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'System Settings';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/admin-sidebar.php';

$db = getDB();

if (isset($_POST['save_settings']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    $settings = [
        'app_name' => $_POST['app_name'] ?? '',
        'app_email' => $_POST['app_email'] ?? '',
        'app_currency' => $_POST['app_currency'] ?? 'NGN',
        'currency_symbol' => $_POST['currency_symbol'] ?? '&#8358;',
        'timezone' => $_POST['timezone'] ?? 'Africa/Lagos',
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => $_POST['smtp_port'] ?? '',
        'smtp_username' => $_POST['smtp_username'] ?? '',
        'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
        'paystack_public_key' => $_POST['paystack_public_key'] ?? '',
        'paystack_secret_key' => $_POST['paystack_secret_key'] ?? '',
        'termii_api_key' => $_POST['termii_api_key'] ?? '',
        'termii_sender_id' => $_POST['termii_sender_id'] ?? '',
        'date_format' => $_POST['date_format'] ?? 'Y-m-d',
        'time_format' => $_POST['time_format'] ?? 'H:i',
    ];
    if (!empty($_POST['smtp_password'])) { $settings['smtp_password'] = $_POST['smtp_password']; }

    try {
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value");
            $stmt->execute([$key, $value]);
        }
        log_audit('update', 'settings', 0, null, ['keys' => array_keys($settings)]);
        echo '<div class="alert alert-success alert-dismissible fade show">Settings saved successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

$settings_data = [];
try {
    $stmt = $db->query("SELECT key, value FROM settings");
    while ($row = $stmt->fetch()) { $settings_data[$row['key']] = $row['value']; }
} catch (Exception $e) {}

function sval($key, $default = '') {
    global $settings_data;
    return $settings_data[$key] ?? $default;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Settings</li>
        </ol>
    </nav>

    <h4 class="fw-semibold mb-4">System Settings</h4>

    <form method="POST">
        <?php echo csrf_field(); ?>

        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-building"></i> General Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Application Name</label>
                                <input type="text" name="app_name" class="form-control" value="<?php echo htmlspecialchars(sval('app_name', APP_NAME)); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Application Email</label>
                                <input type="email" name="app_email" class="form-control" value="<?php echo htmlspecialchars(sval('app_email', '')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency</label>
                                <select name="app_currency" class="form-select">
                                    <option value="NGN" <?php echo sval('app_currency', 'NGN') === 'NGN' ? 'selected' : ''; ?>>NGN - Naira</option>
                                    <option value="USD" <?php echo sval('app_currency') === 'USD' ? 'selected' : ''; ?>>USD - Dollar</option>
                                    <option value="EUR" <?php echo sval('app_currency') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                    <option value="GBP" <?php echo sval('app_currency') === 'GBP' ? 'selected' : ''; ?>>GBP - Pound</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" name="currency_symbol" class="form-control" value="<?php echo htmlspecialchars(sval('currency_symbol', CURRENCY_SYMBOL)); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Timezone</label>
                                <select name="timezone" class="form-select">
                                    <option value="Africa/Lagos" <?php echo sval('timezone', 'Africa/Lagos') === 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos (WAT)</option>
                                    <option value="Africa/Accra" <?php echo sval('timezone') === 'Africa/Accra' ? 'selected' : ''; ?>>Africa/Accra (GMT)</option>
                                    <option value="Africa/Nairobi" <?php echo sval('timezone') === 'Africa/Nairobi' ? 'selected' : ''; ?>>Africa/Nairobi (EAT)</option>
                                    <option value="America/New_York" <?php echo sval('timezone') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                    <option value="Europe/London" <?php echo sval('timezone') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date Format</label>
                                <input type="text" name="date_format" class="form-control" value="<?php echo htmlspecialchars(sval('date_format', 'Y-m-d')); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-envelope"></i> SMTP Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars(sval('smtp_host', '')); ?>" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Port</label>
                                <input type="text" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars(sval('smtp_port', '587')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars(sval('smtp_username', '')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="smtp_password" class="form-control" placeholder="Leave blank to keep current">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    <option value="tls" <?php echo sval('smtp_encryption', 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo sval('smtp_encryption') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo sval('smtp_encryption') === 'none' ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-credit-card"></i> Paystack Keys</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Public Key</label>
                                <input type="text" name="paystack_public_key" class="form-control" value="<?php echo htmlspecialchars(sval('paystack_public_key', '')); ?>" placeholder="pk_live_xxxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Secret Key</label>
                                <input type="password" name="paystack_secret_key" class="form-control" value="<?php echo htmlspecialchars(sval('paystack_secret_key', '')); ?>" placeholder="sk_live_xxxxxxxxx">
                                <small class="text-muted">Key is masked for security</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-chat-dots"></i> Termii SMS Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Termii API Key</label>
                                <input type="password" name="termii_api_key" class="form-control" value="<?php echo htmlspecialchars(sval('termii_api_key', '')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sender ID</label>
                                <input type="text" name="termii_sender_id" class="form-control" value="<?php echo htmlspecialchars(sval('termii_sender_id', '')); ?>" placeholder="FFB Hotel">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" name="save_settings" class="btn btn-primary btn-lg"><i class="bi bi-save"></i> Save All Settings</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
