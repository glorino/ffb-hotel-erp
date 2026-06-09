<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Gallery Management';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();

$upload_dir = __DIR__ . '/../assets/uploads/gallery/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

if (isset($_POST['upload']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    if (!empty($_FILES['gallery_image']['name'])) {
        try {
            $ext = strtolower(pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) { throw new Exception('Invalid file type.'); }
            $filename = uniqid('gallery_') . '.' . $ext;
            $dest = $upload_dir . $filename;
            move_uploaded_file($_FILES['gallery_image']['tmp_name'], $dest);

            $stmt = $db->prepare("INSERT INTO gallery_items (branch_id, title, description, image, category, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([
                $_POST['branch_id'] ?: null,
                $_POST['title'] ?? 'Gallery Image',
                $_POST['description'] ?? null,
                'assets/uploads/gallery/' . $filename,
                $_POST['category'] ?? 'other'
            ]);
            echo '<div class="alert alert-success">Image uploaded successfully.</div>';
        } catch (Exception $e) { echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>'; }
    }
}

if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    try {
        $stmt = $db->prepare("SELECT image FROM gallery_items WHERE id = ?"); $stmt->execute([$_GET['delete']]);
        $img = $stmt->fetch();
        if ($img && $img['image']) {
            $filepath = __DIR__ . '/../' . $img['image'];
            if (file_exists($filepath)) { unlink($filepath); }
        }
        $stmt = $db->prepare("DELETE FROM gallery_items WHERE id = ?"); $stmt->execute([$_GET['delete']]);
        echo '<div class="alert alert-success">Image deleted.</div>';
    } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Gallery</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Gallery Management</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-cloud-upload"></i> Upload Image
        </button>
    </div>

    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Gallery Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Image <span class="text-danger">*</span></label>
                            <input type="file" name="gallery_image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Image title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <?php
                                $gallery_cats = json_decode(GALLERY_CATEGORIES);
                                foreach ($gallery_cats as $cat) {
                                    echo "<option value=\"$cat\">" . ucfirst($cat) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">All Branches</option>
                                <?php
                                $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active'");
                                while ($b = $stmt->fetch()) { echo "<option value=\"{$b['id']}\">" . htmlspecialchars($b['name']) . "</option>"; }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php
        try {
            $stmt = $db->query("SELECT g.*, b.name as branch_name FROM gallery_items g LEFT JOIN branches b ON g.branch_id = b.id ORDER BY g.created_at DESC");
            $images = $stmt->fetchAll();
            foreach ($images as $img):
        ?>
        <div class="col-xl-3 col-md-4 col-6">
            <div class="card border-0 shadow-sm h-100">
                <?php if ($img['image'] && file_exists(__DIR__ . '/../' . $img['image'])): ?>
                <img src="<?php echo $base_url . htmlspecialchars($img['image']); ?>" class="card-img-top" style="height:200px;object-fit:cover;" alt="<?php echo htmlspecialchars($img['title']); ?>">
                <?php else: ?>
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height:200px;">
                    <i class="bi bi-image text-muted" style="font-size:3rem;"></i>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($img['title'] ?? 'Untitled'); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($img['category'] ?? 'other'); ?>
                        <?php if ($img['branch_name']): ?> &middot; <?php echo htmlspecialchars($img['branch_name']); ?><?php endif; ?>
                    </small>
                    <p class="card-text small text-muted mt-1"><?php echo htmlspecialchars($img['description'] ?? ''); ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted"><?php echo timeAgo($img['created_at']); ?></small>
                        <a href="?delete=<?php echo $img['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this image?')"><i class="bi bi-trash"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($images)): ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-images display-3 d-block mb-3"></i>
            <p>No images in the gallery yet. Click "Upload Image" to get started.</p>
        </div>
        <?php endif; ?>
        <?php } catch (Exception $e) { echo '<div class="col-12 alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; } ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
