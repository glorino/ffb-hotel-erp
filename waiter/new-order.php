<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['waiter']);

$page_title = 'New Order';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$selected_table = $_GET['table'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $table_number = trim($_POST['table_number'] ?? '');
    $order_type = $_POST['order_type'] ?? 'dine_in';
    $notes = trim($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $item_notes = $_POST['item_notes'] ?? [];
    $coupon_code = trim($_POST['coupon_code'] ?? '');

    if (empty($items)) {
        set_flash('danger', 'Please add at least one item to the order.');
    } else {
        try {
            $db->beginTransaction();
            $ref = 'ORD-' . strtoupper(uniqid());
            $total_amount = 0;
            $discount_amount = 0;
            $payable_amount = 0;
            $coupon_id = null;

            $order_items = [];
            foreach ($items as $index => $item_id) {
                $qty = max(1, (int)($quantities[$index] ?? 1));
                $note = trim($item_notes[$index] ?? '');

                $stmt = $db->prepare("SELECT id, name, price, is_available FROM food_items WHERE id = ? AND branch_id = ? AND status = 'active'");
                $stmt->execute([$item_id, $branch_id]);
                $food = $stmt->fetch();

                if ($food && $food['is_available']) {
                    $line_total = $food['price'] * $qty;
                    $total_amount += $line_total;
                    $order_items[] = [
                        'id' => $food['id'],
                        'qty' => $qty,
                        'price' => $food['price'],
                        'total' => $line_total,
                        'note' => $note
                    ];
                }
            }

            if (!empty($coupon_code)) {
                $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND branch_id = ? AND status = 'active' AND applicable_to IN ('food', 'all') AND start_date <= CURRENT_DATE AND end_date >= CURRENT_DATE");
                $stmt->execute([$coupon_code, $branch_id]);
                $coupon = $stmt->fetch();
                if ($coupon && $total_amount >= $coupon['minimum_spend']) {
                    $coupon_id = $coupon['id'];
                    if ($coupon['discount_type'] === 'percentage') {
                        $discount_amount = $total_amount * ($coupon['discount_value'] / 100);
                    } else {
                        $discount_amount = min($coupon['discount_value'], $total_amount);
                    }
                }
            }

            $payable_amount = $total_amount - $discount_amount;

            $stmt = $db->prepare("INSERT INTO food_orders (order_reference, branch_id, table_number, order_type, status, total_amount, discount_amount, payable_amount, payment_status, coupon_id, waiter_id, notes, created_at) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, 'pending', ?, ?, ?, NOW())");
            $stmt->execute([$ref, $branch_id, $table_number, $order_type, $total_amount, $discount_amount, $payable_amount, $coupon_id, $user_id, $notes]);
            $order_id = $db->lastInsertId();

            $item_stmt = $db->prepare("INSERT INTO food_order_items (order_id, food_item_id, quantity, unit_price, total_price, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            foreach ($order_items as $oi) {
                $item_stmt->execute([$order_id, $oi['id'], $oi['qty'], $oi['price'], $oi['total'], $oi['note']]);
            }

            if ($coupon_id) {
                $stmt = $db->prepare("INSERT INTO coupon_usages (coupon_id, customer_id, order_id, discount_amount, created_at) VALUES (?, NULL, ?, ?, NOW())");
                $stmt->execute([$coupon_id, $order_id, $discount_amount]);

                $stmt = $db->prepare("UPDATE food_orders SET coupon_id = ? WHERE id = ?");
                $stmt->execute([$coupon_id, $order_id]);
            }

            $db->commit();

            if (!empty($table_number)) {
                try {
                    $stmt = $db->prepare("UPDATE restaurant_tables SET status = 'occupied' WHERE branch_id = ? AND table_number = ?");
                    $stmt->execute([$branch_id, $table_number]);
                } catch (Exception $e) {}
            }

            set_flash('success', "Order <strong>{$ref}</strong> created successfully.");
            header('Location: active-orders.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            error_log('New order error: ' . $e->getMessage());
            set_flash('danger', 'Failed to create order: ' . $e->getMessage());
        }
    }
    header('Location: new-order.php');
    exit;
}

$categories = [];
try {
    $stmt = $db->prepare("SELECT * FROM food_categories WHERE branch_id = ? AND status = 'active' ORDER BY name");
    $stmt->execute([$branch_id]);
    $categories = $stmt->fetchAll();
} catch (Exception $e) {}

$coupons = [];
try {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE branch_id = ? AND status = 'active' AND applicable_to IN ('food', 'all') AND start_date <= CURRENT_DATE AND end_date >= CURRENT_DATE ORDER BY code");
    $stmt->execute([$branch_id]);
    $coupons = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">New Order</li>
        </ol>
    </nav>

    <form method="POST" id="orderForm">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <h5 class="card-title mb-0 fw-semibold">Menu Items</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <p class="text-muted text-center py-3">No menu categories available.</p>
                        <?php else: ?>
                            <ul class="nav nav-pills mb-3 flex-wrap gap-1" id="categoryTabs">
                                <?php foreach ($categories as $i => $cat): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $i === 0 ? 'active' : ''; ?>" href="#cat<?php echo $cat['id']; ?>" data-bs-toggle="tab">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="tab-content">
                                <?php foreach ($categories as $i => $cat): ?>
                                <div class="tab-pane fade <?php echo $i === 0 ? 'show active' : ''; ?>" id="cat<?php echo $cat['id']; ?>">
                                    <div class="row g-2">
                                        <?php
                                        $stmt = $db->prepare("SELECT * FROM food_items WHERE branch_id = ? AND category_id = ? AND status = 'active' AND is_available = 1 ORDER BY name");
                                        $stmt->execute([$branch_id, $cat['id']]);
                                        $foods = $stmt->fetchAll();
                                        foreach ($foods as $food):
                                        ?>
                                        <div class="col-6 col-md-4">
                                            <div class="card border menu-item-card h-100" data-id="<?php echo $food['id']; ?>" data-name="<?php echo htmlspecialchars($food['name']); ?>" data-price="<?php echo $food['price']; ?>">
                                                <div class="card-body text-center p-3">
                                                    <div class="menu-item-icon mb-2">
                                                        <i class="bi bi-egg-fried fs-2 text-muted"></i>
                                                    </div>
                                                    <h6 class="mb-1 small fw-semibold"><?php echo htmlspecialchars($food['name']); ?></h6>
                                                    <p class="text-primary fw-bold mb-1"><?php echo formatMoney($food['price']); ?></p>
                                                    <small class="text-muted"><?php echo htmlspecialchars($food['preparation_time'] ?? ''); ?></small>
                                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2 add-to-cart-btn">
                                                        <i class="bi bi-cart-plus"></i> Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <h5 class="card-title mb-0 fw-semibold">Order Cart</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Order Type</label>
                            <div class="d-flex gap-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="order_type" value="dine_in" id="otDineIn" checked>
                                    <label class="form-check-label" for="otDineIn">Dine In</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="order_type" value="takeaway" id="otTakeaway">
                                    <label class="form-check-label" for="otTakeaway">Takeaway</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="order_type" value="delivery" id="otDelivery">
                                    <label class="form-check-label" for="otDelivery">Delivery</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="tableNumberGroup">
                            <label class="form-label small fw-semibold">Table Number</label>
                            <input type="text" name="table_number" class="form-control" placeholder="e.g. T5" value="<?php echo htmlspecialchars($selected_table); ?>">
                        </div>

                        <div id="cartItems">
                            <div class="text-center py-4 text-muted empty-cart">
                                <i class="bi bi-cart fs-2 d-block mb-2"></i>
                                <small>No items added yet. Click "Add" on a menu item.</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Coupon Code</label>
                            <div class="input-group">
                                <input type="text" name="coupon_code" class="form-control" placeholder="Enter code" id="couponInput" list="couponList">
                                <datalist id="couponList">
                                    <?php foreach ($coupons as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['code']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <button type="button" class="btn btn-outline-secondary" id="applyCoupon">Apply</button>
                            </div>
                            <div id="couponResult" class="mt-1"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Order Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions for kitchen..."></textarea>
                        </div>

                        <div class="border-top pt-3 mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Subtotal:</span>
                                <span id="cartSubtotal"><?php echo formatMoney(0); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 text-success" id="discountRow" style="display:none;">
                                <span>Discount:</span>
                                <span id="cartDiscount"><?php echo formatMoney(0); ?></span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span id="cartTotal"><?php echo formatMoney(0); ?></span>
                            </div>
                        </div>

                        <button type="submit" name="submit_order" class="btn btn-primary w-100 btn-lg" id="submitOrderBtn" disabled>
                            <i class="bi bi-send"></i> Submit Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>

<script>
let cartItems = [];

$(document).on('click', '.add-to-cart-btn', function() {
    const card = $(this).closest('.menu-item-card');
    const id = card.data('id');
    const name = card.data('name');
    const price = card.data('price');

    const existing = cartItems.find(i => i.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cartItems.push({ id, name, price, qty: 1, note: '' });
    }
    renderCart();
});

function renderCart() {
    const container = $('#cartItems');
    const empty = container.find('.empty-cart');

    if (cartItems.length === 0) {
        container.html('<div class="text-center py-4 text-muted empty-cart"><i class="bi bi-cart fs-2 d-block mb-2"></i><small>No items added yet.</small></div>');
        $('#submitOrderBtn').prop('disabled', true);
        updateTotals();
        return;
    }

    let html = '';
    cartItems.forEach((item, idx) => {
        html += `
            <div class="cart-item border rounded p-2 mb-2" data-index="${idx}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="small">${item.name}</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="${idx}"><i class="bi bi-trash"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width:100px;">
                        <button type="button" class="btn btn-outline-secondary qty-minus" data-index="${idx}">-</button>
                        <input type="hidden" name="items[]" value="${item.id}">
                        <input type="number" name="quantities[]" class="form-control text-center qty-input" value="${item.qty}" min="1" data-index="${idx}" style="width:40px;">
                        <button type="button" class="btn btn-outline-secondary qty-plus" data-index="${idx}">+</button>
                    </div>
                    <span class="small text-muted">@ ${formatNum(item.price)}</span>
                    <span class="small fw-bold">${formatNum(item.price * item.qty)}</span>
                </div>
                <input type="text" name="item_notes[]" class="form-control form-control-sm mt-1" placeholder="Special note for this item..." value="${item.note}">
            </div>
        `;
    });
    container.html(html);
    $('#submitOrderBtn').prop('disabled', false);
    updateTotals();
}

$(document).on('click', '.remove-item', function() {
    const idx = $(this).data('index');
    cartItems.splice(idx, 1);
    renderCart();
});

$(document).on('click', '.qty-plus', function() {
    const idx = $(this).data('index');
    cartItems[idx].qty++;
    renderCart();
});

$(document).on('click', '.qty-minus', function() {
    const idx = $(this).data('index');
    if (cartItems[idx].qty > 1) {
        cartItems[idx].qty--;
    } else {
        cartItems.splice(idx, 1);
    }
    renderCart();
});

$(document).on('change', '.qty-input', function() {
    const idx = $(this).data('index');
    const val = parseInt($(this).val()) || 1;
    cartItems[idx].qty = Math.max(1, val);
    renderCart();
});

$(document).on('change', 'input[name="item_notes[]"]', function() {
    const idx = $(this).closest('.cart-item').data('index');
    cartItems[idx].note = $(this).val();
});

function updateTotals() {
    let subtotal = 0;
    cartItems.forEach(i => { subtotal += i.price * i.qty; });
    $('#cartSubtotal').text(formatNum(subtotal));
    $('#cartTotal').text(formatNum(subtotal));
}

function formatNum(amount) {
    return '<?php echo CURRENCY_SYMBOL; ?>' + Number(amount).toFixed(2);
}

$('input[name="order_type"]').on('change', function() {
    if ($(this).val() === 'dine_in') {
        $('#tableNumberGroup').show();
    } else {
        $('#tableNumberGroup').hide();
        $('input[name="table_number"]').val('');
    }
});
</script>
