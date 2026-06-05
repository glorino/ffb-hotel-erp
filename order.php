<?php
$page_title = 'Food Order - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

// Fetch food categories
$stmt_cat = $db->prepare("SELECT * FROM food_categories WHERE status = 'active' ORDER BY name");
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll();

// Fetch food items
$stmt_items = $db->prepare("SELECT fi.*, fc.name AS category_name FROM food_items fi JOIN food_categories fc ON fi.category_id = fc.id WHERE fi.status = 'active' AND fc.status = 'active' ORDER BY fc.name, fi.name");
$stmt_items->execute();
$food_items = $stmt_items->fetchAll();

$has_categories = !empty($categories);
$has_items = !empty($food_items);
?>
<!-- Page Hero -->
<section class="page-hero" style="min-height: 35vh;">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Order Food</span>
        </nav>
        <h1 class="page-hero-title">Food &amp; Beverage</h1>
        <p class="hero-subtitle">Order from our exquisite menu</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <!-- Category Tabs -->
                <div class="food-category-tabs" id="categoryTabs">
                    <button class="food-tab active" data-category="all">All Items</button>
                    <?php if ($has_categories): ?>
                        <?php foreach ($categories as $cat): ?>
                        <button class="food-tab" data-category="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <button class="food-tab" data-category="main-course">Main Course</button>
                        <button class="food-tab" data-category="appetizers">Appetizers</button>
                        <button class="food-tab" data-category="desserts">Desserts</button>
                        <button class="food-tab" data-category="beverages">Beverages</button>
                        <button class="food-tab" data-category="specials">African Specials</button>
                    <?php endif; ?>
                </div>

                <!-- Food Grid -->
                <div class="row g-4" id="foodGrid">
                    <?php if ($has_items): ?>
                        <?php foreach ($food_items as $item): ?>
                        <div class="col-md-6 food-item-col" data-category="<?php echo $item['category_id']; ?>">
                            <div class="food-item-card">
                                <div class="food-item-img">
                                    <?php if ($item['image'] && file_exists(__DIR__ . '/assets/images/food/' . $item['image'])): ?>
                                    <img src="<?php echo BASE_URL; ?>assets/images/food/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                    <i class="bi bi-egg-fried"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="food-item-body">
                                    <h4 class="food-item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p class="food-item-desc"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                    <div class="food-item-footer">
                                        <div>
                                            <div class="food-item-price"><?php echo formatMoney($item['price']); ?></div>
                                            <?php if ($item['preparation_time']): ?>
                                            <div class="food-item-prep"><i class="bi bi-clock"></i> <?php echo htmlspecialchars($item['preparation_time']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-gold btn-sm add-to-cart"
                                                data-id="<?php echo $item['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-price="<?php echo $item['price']; ?>">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Static Food Items -->
                        <div class="col-md-6 food-item-col" data-category="main-course">
                            <div class="food-item-card">
                                <div class="food-item-img"><i class="bi bi-egg-fried"></i></div>
                                <div class="food-item-body">
                                    <h4 class="food-item-name">Jollof Rice</h4>
                                    <p class="food-item-desc">Classic Nigerian jollof rice with spiced tomato sauce</p>
                                    <div class="food-item-footer">
                                        <div>
                                            <div class="food-item-price"><?php echo CURRENCY_SYMBOL; ?>4,500</div>
                                            <div class="food-item-prep"><i class="bi bi-clock"></i> 20 mins</div>
                                        </div>
                                        <button type="button" class="btn btn-gold btn-sm add-to-cart" data-id="1" data-name="Jollof Rice" data-price="4500"><i class="bi bi-plus-lg"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 food-item-col" data-category="main-course">
                            <div class="food-item-card">
                                <div class="food-item-img"><i class="bi bi-egg-fried"></i></div>
                                <div class="food-item-body">
                                    <h4 class="food-item-name">Grilled Chicken</h4>
                                    <p class="food-item-desc">Pan-seared herb-marinated chicken thigh</p>
                                    <div class="food-item-footer">
                                        <div>
                                            <div class="food-item-price"><?php echo CURRENCY_SYMBOL; ?>6,500</div>
                                            <div class="food-item-prep"><i class="bi bi-clock"></i> 30 mins</div>
                                        </div>
                                        <button type="button" class="btn btn-gold btn-sm add-to-cart" data-id="2" data-name="Grilled Chicken" data-price="6500"><i class="bi bi-plus-lg"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 food-item-col" data-category="appetizers">
                            <div class="food-item-card">
                                <div class="food-item-img"><i class="bi bi-egg-fried"></i></div>
                                <div class="food-item-body">
                                    <h4 class="food-item-name">Spring Rolls</h4>
                                    <p class="food-item-desc">Crispy vegetable spring rolls with sweet chili dip</p>
                                    <div class="food-item-footer">
                                        <div>
                                            <div class="food-item-price"><?php echo CURRENCY_SYMBOL; ?>3,500</div>
                                            <div class="food-item-prep"><i class="bi bi-clock"></i> 15 mins</div>
                                        </div>
                                        <button type="button" class="btn btn-gold btn-sm add-to-cart" data-id="3" data-name="Spring Rolls" data-price="3500"><i class="bi bi-plus-lg"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 food-item-col" data-category="desserts">
                            <div class="food-item-card">
                                <div class="food-item-img"><i class="bi bi-egg-fried"></i></div>
                                <div class="food-item-body">
                                    <h4 class="food-item-name">Chocolate Cake</h4>
                                    <p class="food-item-desc">Rich layered chocolate cake with ganache</p>
                                    <div class="food-item-footer">
                                        <div>
                                            <div class="food-item-price"><?php echo CURRENCY_SYMBOL; ?>4,000</div>
                                            <div class="food-item-prep"><i class="bi bi-clock"></i> 15 mins</div>
                                        </div>
                                        <button type="button" class="btn btn-gold btn-sm add-to-cart" data-id="4" data-name="Chocolate Cake" data-price="4000"><i class="bi bi-plus-lg"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 food-item-col" data-category="beverages">
                            <div class="food-item-card">
                                <div class="food-item-img"><i class="bi bi-cup-straw"></i></div>
                                <div class="food-item-body">
                                    <h4 class="food-item-name">Zobo Drink</h4>
                                    <p class="food-item-desc">Hibiscus-based traditional Nigerian refreshment</p>
                                    <div class="food-item-footer">
                                        <div>
                                            <div class="food-item-price"><?php echo CURRENCY_SYMBOL; ?>1,500</div>
                                            <div class="food-item-prep"><i class="bi bi-clock"></i> 5 mins</div>
                                        </div>
                                        <button type="button" class="btn btn-gold btn-sm add-to-cart" data-id="5" data-name="Zobo Drink" data-price="1500"><i class="bi bi-plus-lg"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 food-item-col" data-category="specials">
                            <div class="food-item-card">
                                <div class="food-item-img"><i class="bi bi-egg-fried"></i></div>
                                <div class="food-item-body">
                                    <h4 class="food-item-name">Suya Plate</h4>
                                    <p class="food-item-desc">Spiced grilled beef suya with onions and tomatoes</p>
                                    <div class="food-item-footer">
                                        <div>
                                            <div class="food-item-price"><?php echo CURRENCY_SYMBOL; ?>5,000</div>
                                            <div class="food-item-prep"><i class="bi bi-clock"></i> 20 mins</div>
                                        </div>
                                        <button type="button" class="btn btn-gold btn-sm add-to-cart" data-id="6" data-name="Suya Plate" data-price="5000"><i class="bi bi-plus-lg"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cart Sidebar -->
            <div class="col-lg-4">
                <div class="cart-sidebar" id="cartSidebar">
                    <div class="cart-title">
                        <span><i class="bi bi-bag me-2" style="color: var(--gold);"></i>Your Order</span>
                        <span class="badge bg-gold" id="cartCount" style="background: var(--gold); color: var(--charcoal);">0</span>
                    </div>

                    <div class="cart-items" id="cartItems">
                        <p style="color: var(--mid-gray); text-align: center; padding: 20px 0; font-size: 0.9rem;">
                            <i class="bi bi-bag-x" style="display: block; font-size: 2rem; margin-bottom: 8px; color: var(--light-gray);"></i>
                            Your cart is empty
                        </p>
                    </div>

                    <div class="cart-total">
                        <span>Total</span>
                        <span class="cart-total-amount" id="cartTotal"><?php echo CURRENCY_SYMBOL; ?>0</span>
                    </div>

                    <button type="button" class="btn btn-gold w-100 mt-3" id="checkoutBtn" data-bs-toggle="modal" data-bs-target="#checkoutModal" disabled>
                        <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                    </button>

                    <div class="mt-2">
                        <div class="coupon-input-group">
                            <input type="text" class="form-control form-control-sm" id="foodCouponCode" placeholder="Coupon code">
                            <button type="button" class="btn btn-outline-gold btn-sm" id="foodApplyCoupon">Apply</button>
                        </div>
                        <div id="foodCouponMessage" class="mt-1" style="font-size: 0.8rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--radius-lg); border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--charcoal), var(--charcoal-2)); color: var(--white); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                <h5 class="modal-title" style="font-family: var(--font-serif);">
                    <i class="bi bi-credit-card me-2" style="color: var(--gold);"></i>Complete Your Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="<?php echo BASE_URL; ?>modules/food-order/process.php" method="POST" id="checkoutForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_items" id="orderItemsInput">
                    <input type="hidden" name="total_amount" id="orderTotalInput">
                    <input type="hidden" name="coupon_code" id="orderCouponInput">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" placeholder="John Doe" required
                                   style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="john@example.com"
                                   style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" placeholder="+1 234 567 8900" required
                                   style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Order Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="order_type" id="orderTypeSelect" required
                                    style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                <option value="dine_in">Dine In</option>
                                <option value="takeaway">Takeaway</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="tableNumberGroup">
                            <label class="form-label" style="font-weight: 500;">Table Number</label>
                            <input type="text" class="form-control" name="table_number" placeholder="e.g., 12"
                                   style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-weight: 500;">Special Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Any special instructions?"
                                      style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px; resize: vertical;"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="payment-option selected">
                                <div class="payment-radio"></div>
                                <div class="payment-info">
                                    <div class="payment-name">Paystack Online</div>
                                    <div class="payment-desc">Pay securely with card, bank transfer, or USSD</div>
                                </div>
                                <input type="radio" name="payment_method" value="paystack" checked hidden>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--off-white); padding: 16px 24px;">
                <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-gold" form="checkoutForm">
                    <i class="bi bi-check-circle me-2"></i>Place Order
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var cart = {};
    var foodCouponDiscount = 0;
    var foodCouponLabel = '';

    // Category Filtering
    var tabs = document.querySelectorAll('.food-tab');
    var items = document.querySelectorAll('.food-item-col');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var cat = this.getAttribute('data-category');
            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');

            items.forEach(function(item) {
                if (cat === 'all' || item.getAttribute('data-category') === cat) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Add to Cart
    document.querySelectorAll('.add-to-cart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var name = this.getAttribute('data-name');
            var price = parseFloat(this.getAttribute('data-price'));

            if (cart[id]) {
                cart[id].qty += 1;
            } else {
                cart[id] = { name: name, price: price, qty: 1 };
            }

            updateCart();
        });
    });

    function updateCart() {
        var container = document.getElementById('cartItems');
        var total = 0;
        var count = 0;
        var html = '';

        var ids = Object.keys(cart);
        if (ids.length === 0) {
            html = '<p style="color: var(--mid-gray); text-align: center; padding: 20px 0; font-size: 0.9rem;">' +
                   '<i class="bi bi-bag-x" style="display: block; font-size: 2rem; margin-bottom: 8px; color: var(--light-gray);"></i>' +
                   'Your cart is empty</p>';
            document.getElementById('checkoutBtn').disabled = true;
        } else {
            ids.forEach(function(id) {
                var item = cart[id];
                var itemTotal = item.price * item.qty;
                total += itemTotal;
                count += item.qty;

                html += '<div class="cart-item">' +
                        '<div class="cart-item-info">' +
                        '<div class="cart-item-name">' + item.name + '</div>' +
                        '<div class="cart-item-price"><?php echo CURRENCY_SYMBOL; ?>' + item.price.toLocaleString() + '</div>' +
                        '</div>' +
                        '<div class="cart-item-qty">' +
                        '<button class="qty-btn qty-decrease" data-id="' + id + '">-</button>' +
                        '<span class="qty-value">' + item.qty + '</span>' +
                        '<button class="qty-btn qty-increase" data-id="' + id + '">+</button>' +
                        '</div></div>';
            });
            document.getElementById('checkoutBtn').disabled = false;
        }

        container.innerHTML = html;

        var finalTotal = Math.max(0, total - foodCouponDiscount);
        document.getElementById('cartCount').textContent = count;
        document.getElementById('cartTotal').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + finalTotal.toLocaleString();
        document.getElementById('orderTotalInput').value = finalTotal;
        document.getElementById('orderItemsInput').value = JSON.stringify(cart);

        // Attach qty button events
        document.querySelectorAll('.qty-decrease').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                if (cart[id]) {
                    cart[id].qty -= 1;
                    if (cart[id].qty <= 0) delete cart[id];
                    updateCart();
                }
            });
        });

        document.querySelectorAll('.qty-increase').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                if (cart[id]) {
                    cart[id].qty += 1;
                    updateCart();
                }
            });
        });
    }

    // Order Type toggle
    var orderTypeSelect = document.getElementById('orderTypeSelect');
    var tableNumberGroup = document.getElementById('tableNumberGroup');

    if (orderTypeSelect && tableNumberGroup) {
        orderTypeSelect.addEventListener('change', function() {
            tableNumberGroup.style.display = this.value === 'dine_in' ? 'block' : 'none';
        });
    }

    // Food coupon
    var foodApplyBtn = document.getElementById('foodApplyCoupon');
    var foodCouponInput = document.getElementById('foodCouponCode');
    var foodCouponMsg = document.getElementById('foodCouponMessage');

    if (foodApplyBtn && foodCouponInput) {
        foodApplyBtn.addEventListener('click', function() {
            var code = foodCouponInput.value.trim();
            if (!code) {
                foodCouponMsg.innerHTML = '<span style="color:#dc3545;">Enter a coupon code.</span>';
                return;
            }

            var total = 0;
            var ids = Object.keys(cart);
            ids.forEach(function(id) { total += cart[id].price * cart[id].qty; });

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo BASE_URL; ?>modules/food-order/validate-coupon.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            foodCouponMsg.innerHTML = '<span style="color:#28a745;"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
                            if (data.discount_type === 'percentage') {
                                foodCouponDiscount = total * (data.discount_value / 100);
                            } else {
                                foodCouponDiscount = data.discount_value;
                            }
                            document.getElementById('orderCouponInput').value = code;
                            updateCart();
                        } else {
                            foodCouponMsg.innerHTML = '<span style="color:#dc3545;">' + data.message + '</span>';
                            foodCouponDiscount = 0;
                            updateCart();
                        }
                    } catch(e) {
                        foodCouponMsg.innerHTML = '<span style="color:#dc3545;">Error validating coupon.</span>';
                    }
                }
            };
            xhr.send('code=' + encodeURIComponent(code) + '&amount=' + total);
        });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
