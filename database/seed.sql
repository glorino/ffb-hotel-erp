-- ============================================================
-- FFB Hotel ERP - Seed Data (PostgreSQL)
-- ============================================================
-- Password hash for 'password' used for all seed users:
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================

-- -----------------------------------------------------------
-- Roles
-- -----------------------------------------------------------
INSERT INTO roles (id, name, slug, description) VALUES
(1,  'Business Owner',   'business_owner',   'Full access to all modules and branches'),
(2,  'Admin',            'admin',            'System administrator with elevated privileges'),
(3,  'Branch Manager',   'branch_manager',   'Manages day-to-day operations of a branch'),
(4,  'Receptionist',     'receptionist',     'Handles bookings, check-ins, and check-outs'),
(5,  'Kitchen Chef',     'kitchen_chef',     'Manages kitchen operations and food preparation'),
(6,  'Waiter',           'waiter',           'Takes orders and serves customers'),
(7,  'Inventory Manager','inventory_manager','Manages stock and supplies'),
(8,  'Housekeeping',     'housekeeping',     'Manages room cleaning and maintenance'),
(9,  'Accountant',       'accountant',       'Handles payments, invoices, and financial records'),
(10, 'Customer',         'customer',         'Front-end user with limited access');

-- -----------------------------------------------------------
-- Branch
-- -----------------------------------------------------------
INSERT INTO branches (id, name, slug, address, city, state, phone, email, status) VALUES
(1, 'FFB Luxury Hotel', 'ffb-luxury-hotel', '123 Hospitality Avenue', 'Victoria Island', 'Lagos', '+2348009999999', 'info@ffbhotel.com', 'active');

-- -----------------------------------------------------------
-- Users (password hash for 'password')
-- -----------------------------------------------------------
INSERT INTO users (id, branch_id, role_id, full_name, email, phone, password, status) VALUES
(1,  1, 1,  'Business Owner',    'owner@ffbhotel.com',       '+2348010000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(2,  1, 2,  'System Admin',      'admin@ffbhotel.com',       '+2348010000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(3,  1, 3,  'Branch Manager',    'manager@ffbhotel.com',     '+2348010000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(4,  1, 4,  'Receptionist',      'reception@ffbhotel.com',   '+2348010000004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(5,  1, 5,  'Kitchen Chef',      'kitchen@ffbhotel.com',     '+2348010000005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(6,  1, 6,  'Waiter',            'waiter@ffbhotel.com',      '+2348010000006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(7,  1, 7,  'Inventory Manager', 'inventory@ffbhotel.com',   '+2348010000007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(8,  1, 8,  'Housekeeping',      'housekeeping@ffbhotel.com','+2348010000008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(9,  1, 9,  'Accountant',        'accountant@ffbhotel.com',  '+2348010000009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
(10, 1, 10, 'Customer User',     'customer@ffbhotel.com',    '+2348010000010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');

-- -----------------------------------------------------------
-- Room Types
-- -----------------------------------------------------------
INSERT INTO room_types (id, name, description, base_price, max_guests, amenities) VALUES
(1, 'Deluxe Room',       'Spacious room with premium furnishings and city view',        120000.00, 2, 'WiFi, Air Conditioning, Mini Bar, Flat-screen TV, Safe, Work Desk'),
(2, 'Executive Room',    'Executive-level room with lounge access and business amenities', 180000.00, 2, 'WiFi, Air Conditioning, Mini Bar, TV, Lounge Access, Coffee Machine, Work Desk'),
(3, 'Luxury Suite',      'Elegant suite with separate living area and premium amenities',  350000.00, 3, 'WiFi, Air Conditioning, Full Bar, Living Room, Jacuzzi, TV, Butler Service'),
(4, 'Presidential Suite','Top-tier suite with panoramic views, private dining, and butler', 600000.00, 4, 'WiFi, Air Conditioning, Private Dining, Butler Service, Jacuzzi, Living Room, Study'),
(5, 'Penthouse',         'Ultra-luxury penthouse with rooftop terrace and 24/7 concierge',  950000.00, 6, 'WiFi, Air Conditioning, Rooftop Terrace, Private Pool, Concierge, Full Kitchen, Cinema');

-- -----------------------------------------------------------
-- Rooms
-- -----------------------------------------------------------
INSERT INTO rooms (id, branch_id, room_type_id, room_number, floor, price_per_night, status) VALUES
(1,  1, 1, 'Amber',   '1', 120000.00, 'available'),
(2,  1, 1, 'Jade',    '1', 120000.00, 'available'),
(3,  1, 1, 'Ivory',   '1', 120000.00, 'available'),
(4,  1, 1, 'Coral',   '1', 120000.00, 'available'),
(5,  1, 1, 'Pearl',   '1', 120000.00, 'available'),
(6,  1, 2, 'Sapphire','2', 180000.00, 'available'),
(7,  1, 2, 'Onyx',    '2', 180000.00, 'available'),
(8,  1, 2, 'Opal',    '2', 180000.00, 'available'),
(9,  1, 2, 'Ruby',    '2', 180000.00, 'available'),
(10, 1, 2, 'Topaz',   '2', 180000.00, 'available'),
(11, 1, 3, 'Emerald Suite',  '3', 350000.00, 'available'),
(12, 1, 3, 'Sapphire Suite', '3', 350000.00, 'available'),
(13, 1, 3, 'Diamond Suite',  '3', 350000.00, 'available'),
(14, 1, 4, 'The Presidency', '4', 600000.00, 'available'),
(15, 1, 5, 'The Crown Penthouse', '5', 950000.00, 'available');

-- -----------------------------------------------------------
-- Food Categories
-- -----------------------------------------------------------
INSERT INTO food_categories (id, branch_id, name, description, status) VALUES
(1, 1, 'Main Course',    'Hearty main dishes and entrees',       'active'),
(2, 1, 'Appetizers',     'Light starters and small plates',      'active'),
(3, 1, 'Desserts',       'Sweet treats and confections',         'active'),
(4, 1, 'Beverages',      'Drinks, juices, and refreshments',     'active'),
(5, 1, 'African Specials','Traditional African cuisine specials', 'active');

-- -----------------------------------------------------------
-- Food Items
-- -----------------------------------------------------------
INSERT INTO food_items (id, branch_id, category_id, name, description, price, preparation_time, is_available) VALUES
    (1,  1, 1, 'Jollof Rice',         'Classic Nigerian jollof rice with spiced tomato sauce',                   4500.00,  '20 mins', TRUE),
(2,  1, 1, 'Fried Rice',          'Nigerian fried rice with mixed vegetables and liver',                   4500.00,  '20 mins', TRUE),
(3,  1, 1, 'Grilled Chicken',     'Pan-seared herb-marinated chicken thigh',                              6500.00,  '30 mins', TRUE),
(4,  1, 1, 'Pepper Soup',         'Spiced traditional pepper soup with assorted meat',                     5500.00,  '25 mins', TRUE),
(5,  1, 1, 'Egusi Soup & Pounded Yam', 'Ground melon seed soup served with smooth pounded yam',           6000.00,  '35 mins', TRUE),
(6,  1, 1, 'Beef Steak',          'Tender grilled beef steak with peppercorn sauce',                      12000.00, '30 mins', TRUE),
(7,  1, 2, 'Spring Rolls',        'Crispy vegetable spring rolls with sweet chili dip',                    3500.00,  '15 mins', TRUE),
(8,  1, 2, 'Chicken Wings',       'Spicy grilled chicken wings with ranch dip',                            5000.00,  '20 mins', TRUE),
(9,  1, 2, 'Samosa',              'Fried pastry filled with spiced meat and herbs',                         3000.00,  '15 mins', TRUE),
(10, 1, 2, 'Shrimp Cocktail',     'Chilled prawns with zesty cocktail sauce',                               7500.00,  '10 mins', TRUE),
(11, 1, 3, 'Chocolate Cake',      'Rich layered chocolate cake with ganache',                              4000.00,  '15 mins', TRUE),
(12, 1, 3, 'Fruit Parfait',       'Layered yoghurt parfait with fresh seasonal fruits',                    3500.00,  '10 mins', TRUE),
(13, 1, 3, 'Ice Cream Sundae',    'Vanilla ice cream with hot fudge, nuts, and cherry',                     3000.00,  '5 mins',  TRUE),
(14, 1, 3, 'Panna Cotta',         'Italian cream dessert with berry coulis',                                4500.00,  '10 mins', TRUE),
(15, 1, 4, 'Zobo Drink',          'Hibiscus-based traditional Nigerian refreshment',                         1500.00,  '5 mins',  TRUE),
(16, 1, 4, 'Chapman Cocktail',    'Nigerian classic non-alcoholic cocktail',                                2500.00,  '5 mins',  TRUE),
(17, 1, 4, 'Fresh Orange Juice',  'Freshly squeezed orange juice',                                          2000.00,  '5 mins',  TRUE),
(18, 1, 4, 'Coffee',              'Premium brewed coffee (espresso, latte, or cappuccino)',                  2000.00,  '5 mins',  TRUE),
(19, 1, 5, 'Pepper Soup & Catfish','Spiced catfish pepper soup with traditional herbs',                     6500.00,  '30 mins', TRUE),
(20, 1, 5, 'Afang Soup',          'Traditional Efik vegetable soup served with fufu',                        6500.00,  '35 mins', TRUE),
(21, 1, 5, 'Suya Plate',          'Spiced grilled beef suya served with sliced onions and tomatoes',        5000.00,  '20 mins', TRUE),
(22, 1, 5, 'Ofada Rice & Sauce',  'Local ofada rice with spicy ayamase sauce',                              5500.00,  '30 mins', TRUE);

-- -----------------------------------------------------------
-- Coupons
-- -----------------------------------------------------------
INSERT INTO coupons (id, code, title, description, discount_type, discount_value, start_date, end_date, branch_id, applicable_to, minimum_spend, usage_limit, usage_per_customer, status) VALUES
(1, 'VALENTINE20', 'Valentine Special', '20% off on all room bookings for Valentine season',   'percentage', 20.00, '2026-02-01', '2026-02-28', 1, 'rooms',   50000.00, 100, 1, 'active'),
(2, 'CHRISTMAS25', 'Christmas Festive', '25% off food orders this Christmas',                  'percentage', 25.00, '2026-12-01', '2026-12-31', 1, 'food',    10000.00, 200, 3, 'active'),
(3, 'NEWYEAR15',   'New Year Celebration','15% off everything to start the new year',          'percentage', 15.00, '2026-01-01', '2026-01-15', 1, 'all',     20000.00, 150, 2, 'active'),
(4, 'WEEKEND5',    'Weekend Getaway', 'Flat NGN 5,000 off weekend room bookings',              'fixed',      5000.00,'2026-01-01', '2026-12-31', 1, 'rooms',   80000.00, 300, 1, 'active'),
(5, 'SUITE30',     'Suite Luxury',  '30% off Presidential and Luxury suite bookings',          'percentage', 30.00, '2026-01-01', '2026-12-31', 1, 'rooms',   200000.00,50, 1, 'active');

-- -----------------------------------------------------------
-- Gallery Items
-- -----------------------------------------------------------
INSERT INTO gallery_items (id, branch_id, title, description, image, category, status) VALUES
(1, 1, 'Luxury Lobby',     'Elegant lobby with modern African art and comfortable seating',    'gallery/lobby.jpg',     'lobby',    'active'),
(2, 1, 'Deluxe Room',      'Premium deluxe room with city skyline views',                      'gallery/deluxe.jpg',    'rooms',    'active'),
(3, 1, 'Presidential Suite','Expansive presidential suite with panoramic views',               'gallery/presidential.jpg','rooms',  'active'),
(4, 1, 'Fine Dining Restaurant','Elegant restaurant serving local and international cuisine',  'gallery/restaurant.jpg','restaurant','active'),
(5, 1, 'Swimming Pool',    'Infinity pool with ocean views and poolside bar',                  'gallery/pool.jpg',      'exterior', 'active'),
(6, 1, 'Executive Lounge', 'Private lounge for executive guests with complimentary refreshments','gallery/lounge.jpg',   'other',    'active');

-- -----------------------------------------------------------
-- Settings
-- -----------------------------------------------------------
INSERT INTO settings (key, value) VALUES
('app_name',              'FFB Hotel ERP'),
('app_logo',              'assets/img/logo.png'),
('app_favicon',           'assets/img/favicon.ico'),
('app_timezone',          'Africa/Lagos'),
('app_currency',          'NGN'),
('app_currency_symbol',   'NGN'),
('app_date_format',       'Y-m-d'),
('app_datetime_format',   'Y-m-d H:i:s'),
('smtp_host',             'smtp.hostinger.com'),
('smtp_port',             '465'),
('smtp_username',         'noreply@ffbhotel.com'),
('smtp_from_email',       'noreply@ffbhotel.com'),
('smtp_from_name',        'FFB Hotel'),
('smtp_secure',           'ssl'),
('paystack_public_key',   'pk_test_xxxxxxxxxxxxx'),
('paystack_secret_key',   'sk_test_xxxxxxxxxxxxx'),
('paystack_currency',     'NGN'),
('termii_api_key',        'your_termii_api_key_here'),
('termii_sender_id',      'FFB Hotel'),
('check_in_time',         '14:00'),
('check_out_time',        '12:00'),
('max_guests_per_room',   '6'),
('default_booking_status','pending'),
('maintenance_mode',      '0'),
('invoice_prefix',        'INV-'),
('booking_prefix',        'BK-'),
('order_prefix',          'ORD-'),
('payment_prefix',        'PAY-'),
('reservation_prefix',    'RES-'),
('loyalty_points_enabled','1'),
('points_per_naira',      '1'),
('minimum_points_redeem', '500');

-- -----------------------------------------------------------
-- Sync sequences after explicit ID inserts
-- -----------------------------------------------------------
SELECT setval(pg_get_serial_sequence('roles', 'id'), COALESCE((SELECT MAX(id) FROM roles), 0));
SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 0));
SELECT setval(pg_get_serial_sequence('room_types', 'id'), COALESCE((SELECT MAX(id) FROM room_types), 0));
SELECT setval(pg_get_serial_sequence('rooms', 'id'), COALESCE((SELECT MAX(id) FROM rooms), 0));
SELECT setval(pg_get_serial_sequence('food_categories', 'id'), COALESCE((SELECT MAX(id) FROM food_categories), 0));
SELECT setval(pg_get_serial_sequence('food_items', 'id'), COALESCE((SELECT MAX(id) FROM food_items), 0));
SELECT setval(pg_get_serial_sequence('coupons', 'id'), COALESCE((SELECT MAX(id) FROM coupons), 0));
SELECT setval(pg_get_serial_sequence('gallery_items', 'id'), COALESCE((SELECT MAX(id) FROM gallery_items), 0));
