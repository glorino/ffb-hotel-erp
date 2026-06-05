<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Website Content Management';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../sidebars/admin-sidebar.php';

$db = getDB();

$upload_dir = __DIR__ . '/../assets/uploads/content/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

if (isset($_POST['save_content']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    try {
        $keys = ['hero_title', 'hero_subtitle', 'hero_cta_text', 'about_title', 'about_content', 'services_title', 'services_content', 'contact_email', 'contact_phone', 'contact_address', 'footer_text', 'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value");
                $stmt->execute([$key, $_POST[$key]]);
            }
        }

        $image_keys = ['hero_bg', 'about_image', 'logo'];
        foreach ($image_keys as $img_key) {
            if (!empty($_FILES[$img_key]['name'])) {
                $ext = strtolower(pathinfo($_FILES[$img_key]['name'], PATHINFO_EXTENSION));
                $filename = $img_key . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES[$img_key]['tmp_name'], $upload_dir . $filename);
                $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value");
                $stmt->execute([$img_key, 'assets/uploads/content/' . $filename]);
            }
        }

        log_audit('update', 'website_content', 0, null, ['updated' => true]);
        echo '<div class="alert alert-success alert-dismissible fade show">Website content updated successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

$content = [];
try {
    $stmt = $db->query("SELECT key, value FROM settings WHERE key LIKE 'hero_%' OR key LIKE 'about_%' OR key LIKE 'services_%' OR key LIKE 'contact_%' OR key LIKE 'footer_%' OR key LIKE '%_url' OR key IN ('logo')");
    while ($row = $stmt->fetch()) {
        $content[$row['key']] = $row['value'];
    }
} catch (Exception $e) {}

function get_content($key, $default = '') {
    global $content;
    return $content[$key] ?? $default;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Website Content</li>
        </ol>
    </nav>

    <h4 class="fw-semibold mb-4">Website Content Management</h4>

    <form method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-house-heart"></i> Hero Section</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Hero Title</label>
                                <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars(get_content('hero_title', 'Welcome to FFB Hotel')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CTA Button Text</label>
                                <input type="text" name="hero_cta_text" class="form-control" value="<?php echo htmlspecialchars(get_content('hero_cta_text', 'Book Now')); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Hero Subtitle</label>
                                <textarea name="hero_subtitle" class="form-control" rows="2"><?php echo htmlspecialchars(get_content('hero_subtitle', 'Experience luxury hospitality at its finest')); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hero Background Image</label>
                                <input type="file" name="hero_bg" class="form-control" accept="image/*">
                                <?php if (get_content('hero_bg')): ?>
                                <div class="mt-2"><img src="<?php echo $base_url . get_content('hero_bg'); ?>" class="img-thumbnail" style="max-height:80px;"></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Logo</label>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                                <?php if (get_content('logo')): ?>
                                <div class="mt-2"><img src="<?php echo $base_url . get_content('logo'); ?>" class="img-thumbnail" style="max-height:60px;"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-info-circle"></i> About Section</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">About Title</label>
                                <input type="text" name="about_title" class="form-control" value="<?php echo htmlspecialchars(get_content('about_title', 'About Us')); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">About Image</label>
                                <input type="file" name="about_image" class="form-control" accept="image/*">
                                <?php if (get_content('about_image')): ?>
                                <div class="mt-2"><img src="<?php echo $base_url . get_content('about_image'); ?>" class="img-thumbnail" style="max-height:60px;"></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">About Content</label>
                                <textarea name="about_content" class="form-control" rows="5"><?php echo htmlspecialchars(get_content('about_content', 'FFB Hotel Hospitality ERP offers a comprehensive solution for managing hotels, branches, rooms, food services, and more.')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-concierge-bell"></i> Services Section</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Services Title</label>
                                <input type="text" name="services_title" class="form-control" value="<?php echo htmlspecialchars(get_content('services_title', 'Our Services')); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Services Content</label>
                                <textarea name="services_content" class="form-control" rows="4"><?php echo htmlspecialchars(get_content('services_content', 'We offer premium hospitality services including room booking, restaurant dining, event hosting, and more.')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-envelope"></i> Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars(get_content('contact_email', 'info@ffbhotel.com')); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars(get_content('contact_phone', '+234 800 000 0000')); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Address</label>
                                <input type="text" name="contact_address" class="form-control" value="<?php echo htmlspecialchars(get_content('contact_address', 'Lagos, Nigeria')); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Text</label>
                                <textarea name="footer_text" class="form-control" rows="2"><?php echo htmlspecialchars(get_content('footer_text', '&copy; ' . date('Y') . ' FFB Hotel Hospitality ERP. All rights reserved.')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-share"></i> Social Media Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Facebook URL</label>
                                <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars(get_content('facebook_url', '#')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Twitter URL</label>
                                <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars(get_content('twitter_url', '#')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Instagram URL</label>
                                <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars(get_content('instagram_url', '#')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">LinkedIn URL</label>
                                <input type="url" name="linkedin_url" class="form-control" value="<?php echo htmlspecialchars(get_content('linkedin_url', '#')); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" name="save_content" class="btn btn-primary btn-lg"><i class="bi bi-save"></i> Save All Content</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
