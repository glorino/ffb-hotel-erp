<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['admin']);

$page_title = 'Food Menu Management';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();

if (isset($_POST['save_category']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $db->prepare("INSERT INTO food_categories (branch_id, name, description, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute([$_POST['branch_id'], $_POST['name'], $_POST['description'] ?? null]);
        echo '<div class="alert alert-success">Category created.</div>';
    } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}

if (isset($_POST['save_item']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    try {
        $image = null;
        if (!empty($_FILES['food_image']['name'])) {
            $ext = pathinfo($_FILES['food_image']['name'], PATHINFO_EXTENSION);
            $image = 'uploads/food/' . uniqid('food_') . '.' . $ext;
            move_uploaded_file($_FILES['food_image']['tmp_name'], __DIR__ . '/../' . $image);
        }
        $stmt = $db->prepare("INSERT INTO food_items (branch_id, category_id, name, description, price, preparation_time, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['branch_id'], $_POST['category_id'], $_POST['name'], $_POST['description'] ?? null, $_POST['price'], $_POST['preparation_time'] ?? null, $image]);
        echo '<div class="alert alert-success">Menu item created.</div>';
    } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}

if (isset($_GET['toggle_item']) && ctype_digit($_GET['toggle_item'])) {
    try {
        $stmt = $db->prepare("SELECT is_available FROM food_items WHERE id = ?"); $stmt->execute([$_GET['toggle_item']]);
        $f = $stmt->fetch();
        if ($f) { $nv = $f['is_available'] ? false : true; $stmt = $db->prepare("UPDATE food_items SET is_available = ? WHERE id = ?"); $stmt->execute([$nv, $_GET['toggle_item']]); echo '<div class="alert alert-success">Availability toggled.</div>'; }
    } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}

if (isset($_GET['delete_category']) && ctype_digit($_GET['delete_category'])) {
    try { $stmt = $db->prepare("DELETE FROM food_categories WHERE id = ?"); $stmt->execute([$_GET['delete_category']]); echo '<div class="alert alert-success">Category deleted.</div>'; } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}

if (isset($_GET['delete_item']) && ctype_digit($_GET['delete_item'])) {
    try { $stmt = $db->prepare("DELETE FROM food_items WHERE id = ?"); $stmt->execute([$_GET['delete_item']]); echo '<div class="alert alert-success">Item deleted.</div>'; } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Food Menu</li>
        </ol>
    </nav>

    <h4 class="fw-semibold mb-4">Food Menu Management</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold">Add Category</h6></div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-2">
                            <select name="branch_id" class="form-select form-select-sm" required>
                                <option value="">Branch</option>
                                <?php
                                $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active'");
                                while ($b = $stmt->fetch()) { echo "<option value=\"{$b['id']}\">" . htmlspecialchars($b['name']) . "</option>"; }
                                ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Category name" required>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="description" class="form-control form-control-sm" placeholder="Description (optional)">
                        </div>
                        <button type="submit" name="save_category" class="btn btn-primary btn-sm w-100">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold">Add Menu Item</h6></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="mb-2">
                            <select name="branch_id" class="form-select form-select-sm" required>
                                <option value="">Branch</option>
                                <?php
                                $stmt = $db->query("SELECT id, name FROM branches WHERE status = 'active'");
                                while ($b = $stmt->fetch()) { echo "<option value=\"{$b['id']}\">" . htmlspecialchars($b['name']) . "</option>"; }
                                ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <select name="category_id" class="form-select form-select-sm" required>
                                <option value="">Category</option>
                                <?php
                                $stmt = $db->query("SELECT id, name FROM food_categories WHERE status = 'active'");
                                while ($c = $stmt->fetch()) { echo "<option value=\"{$c['id']}\">" . htmlspecialchars($c['name']) . "</option>"; }
                                ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Item name" required>
                        </div>
                        <div class="mb-2">
                            <textarea name="description" class="form-control form-control-sm" placeholder="Description" rows="1"></textarea>
                        </div>
                        <div class="mb-2">
                            <input type="number" step="0.01" name="price" class="form-control form-control-sm" placeholder="Price" required>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="preparation_time" class="form-control form-control-sm" placeholder="Prep time (e.g. 15 mins)">
                        </div>
                        <div class="mb-2">
                            <input type="file" name="food_image" class="form-control form-control-sm">
                        </div>
                        <button type="submit" name="save_item" class="btn btn-success btn-sm w-100">Add Menu Item</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold">Quick Stats</h6></div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <?php
                    $cat_count = $db->query("SELECT COUNT(*) FROM food_categories")->fetchColumn();
                    $item_count = $db->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
                    $avail_count = $db->query("SELECT COUNT(*) FROM food_items WHERE is_available = TRUE")->fetchColumn();
                    ?>
                    <p class="mb-1">Categories: <strong><?php echo $cat_count; ?></strong></p>
                    <p class="mb-1">Total Items: <strong><?php echo $item_count; ?></strong></p>
                    <p class="mb-0">Available: <strong><?php echo $avail_count; ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <?php
    try {
        $stmt = $db->query("
            SELECT fc.*, b.name as branch_name
            FROM food_categories fc
            JOIN branches b ON fc.branch_id = b.id
            ORDER BY fc.name
        ");
        $categories = $stmt->fetchAll();
        foreach ($categories as $cat):
    ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-semibold"><?php echo htmlspecialchars($cat['name']); ?></h5>
                <small class="text-muted"><?php echo htmlspecialchars($cat['branch_name']); ?> &middot; <?php echo htmlspecialchars($cat['description'] ?? ''); ?></small>
            </div>
            <a href="?delete_category=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category and all its items?')"><i class="bi bi-trash"></i></a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Price</th><th>Prep Time</th><th>Available</th><th>Image</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt2 = $db->prepare("SELECT * FROM food_items WHERE category_id = ? ORDER BY name");
                        $stmt2->execute([$cat['id']]);
                        $items = $stmt2->fetchAll();
                        foreach ($items as $item):
                        ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo formatMoney($item['price']); ?></td>
                            <td><small><?php echo htmlspecialchars($item['preparation_time'] ?? 'N/A'); ?></small></td>
                            <td>
                                <?php if ($item['is_available']): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo $base_url . htmlspecialchars($item['image']); ?>" style="width:50px;height:40px;object-fit:cover;" class="rounded">
                                <?php else: ?>
                                    <span class="text-muted">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="?toggle_item=<?php echo $item['id']; ?>" class="btn btn-outline-secondary"><i class="bi bi-toggle-on"></i></a>
                                    <a href="?delete_item=<?php echo $item['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete item?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No items in this category.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($categories)): ?>
    <div class="text-center py-4 text-muted">No food categories yet. Add one above.</div>
    <?php endif; ?>
    <?php } catch (Exception $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>'; } ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
