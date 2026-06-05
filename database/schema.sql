-- ============================================================
-- FFB Hotel ERP - PostgreSQL Schema (Neon)
-- ============================================================

CREATE EXTENSION IF NOT EXISTS "pgcrypto";
SET statement_timeout = 0;

DROP TABLE IF EXISTS coupon_usages CASCADE;
DROP TABLE IF EXISTS payments CASCADE;
DROP TABLE IF EXISTS invoices CASCADE;
DROP TABLE IF EXISTS receipts CASCADE;
DROP TABLE IF EXISTS refunds CASCADE;
DROP TABLE IF EXISTS paystack_transactions CASCADE;
DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS live_chat_messages CASCADE;
DROP TABLE IF EXISTS live_chat_sessions CASCADE;
DROP TABLE IF EXISTS chat_messages CASCADE;
DROP TABLE IF EXISTS chat_sessions CASCADE;
DROP TABLE IF EXISTS food_order_items CASCADE;
DROP TABLE IF EXISTS food_orders CASCADE;
DROP TABLE IF EXISTS food_items CASCADE;
DROP TABLE IF EXISTS food_categories CASCADE;
DROP TABLE IF EXISTS reservations CASCADE;
DROP TABLE IF EXISTS bookings CASCADE;
DROP TABLE IF EXISTS customers CASCADE;
DROP TABLE IF EXISTS rooms CASCADE;
DROP TABLE IF EXISTS room_types CASCADE;
DROP TABLE IF EXISTS services CASCADE;
DROP TABLE IF EXISTS gallery_items CASCADE;
DROP TABLE IF EXISTS expenses CASCADE;
DROP TABLE IF EXISTS invoices CASCADE;
DROP TABLE IF EXISTS stock_movements CASCADE;
DROP TABLE IF EXISTS inventory_items CASCADE;
DROP TABLE IF EXISTS suppliers CASCADE;
DROP TABLE IF EXISTS branch_transfers CASCADE;
DROP TABLE IF EXISTS kitchen_requests CASCADE;
DROP TABLE IF EXISTS housekeeping_logs CASCADE;
DROP TABLE IF EXISTS housekeeping_supply_usage CASCADE;
DROP TABLE IF EXISTS housekeeping_requests CASCADE;
DROP TABLE IF EXISTS maintenance_requests CASCADE;
DROP TABLE IF EXISTS room_supply_allocations CASCADE;
DROP TABLE IF EXISTS room_supply_requests CASCADE;
DROP TABLE IF EXISTS inventory_requests CASCADE;
DROP TABLE IF EXISTS staff_duty CASCADE;
DROP TABLE IF EXISTS customer_issues CASCADE;
DROP TABLE IF EXISTS customer_requests CASCADE;
DROP TABLE IF EXISTS password_resets CASCADE;
DROP TABLE IF EXISTS coupons CASCADE;
DROP TABLE IF EXISTS settings CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS branches CASCADE;
DROP TABLE IF EXISTS roles CASCADE;

-- -----------------------------------------------------------
-- 1. Roles
-- -----------------------------------------------------------
CREATE TABLE roles (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (slug)
);

-- -----------------------------------------------------------
-- 2. Branches
-- -----------------------------------------------------------
CREATE TABLE branches (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    name        VARCHAR(200) NOT NULL,
    slug        VARCHAR(200) NOT NULL,
    address     TEXT,
    city        VARCHAR(100),
    state       VARCHAR(100),
    phone       VARCHAR(50),
    email       VARCHAR(100),
    status      VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (slug)
);

-- -----------------------------------------------------------
-- 3. Users
-- -----------------------------------------------------------
CREATE TABLE users (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id   INTEGER,
    role_id     INTEGER NOT NULL,
    full_name   VARCHAR(200) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    phone       VARCHAR(50),
    password    VARCHAR(255) NOT NULL,
    avatar      VARCHAR(255),
    status      VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','blocked')),
    last_login  TIMESTAMP,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (email),
    CONSTRAINT fk_users_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id)
        REFERENCES roles (id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX idx_users_branch_id ON users (branch_id);
CREATE INDEX idx_users_role_id ON users (role_id);

-- -----------------------------------------------------------
-- 4. Room Types
-- -----------------------------------------------------------
CREATE TABLE room_types (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    base_price  NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    max_guests  INTEGER NOT NULL DEFAULT 1,
    amenities   TEXT,
    status      VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- -----------------------------------------------------------
-- 5. Rooms
-- -----------------------------------------------------------
CREATE TABLE rooms (
    id              INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id       INTEGER NOT NULL,
    room_type_id    INTEGER NOT NULL,
    room_number     VARCHAR(20) NOT NULL,
    floor           VARCHAR(20),
    price_per_night NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    status          VARCHAR(20) NOT NULL DEFAULT 'available' CHECK (status IN ('available','reserved','occupied','cleaning','maintenance','out_of_service')),
    image           VARCHAR(255),
    description     TEXT,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (room_number, branch_id),
    CONSTRAINT fk_rooms_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rooms_room_type FOREIGN KEY (room_type_id)
        REFERENCES room_types (id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX idx_rooms_branch_id ON rooms (branch_id);
CREATE INDEX idx_rooms_room_type_id ON rooms (room_type_id);
CREATE INDEX idx_rooms_status ON rooms (status);

-- -----------------------------------------------------------
-- 6. Customers
-- -----------------------------------------------------------
CREATE TABLE customers (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    user_id     INTEGER,
    branch_id   INTEGER,
    full_name   VARCHAR(200) NOT NULL,
    email       VARCHAR(255),
    phone       VARCHAR(50),
    address     TEXT,
    city        VARCHAR(100),
    state       VARCHAR(100),
    id_type     VARCHAR(50),
    id_number   VARCHAR(100),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_customers_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_customers_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_customers_user_id ON customers (user_id);
CREATE INDEX idx_customers_branch_id ON customers (branch_id);
CREATE INDEX idx_customers_email ON customers (email);

-- -----------------------------------------------------------
-- 7. Bookings
-- -----------------------------------------------------------
CREATE TABLE bookings (
    id                INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    booking_reference VARCHAR(50) NOT NULL,
    customer_id       INTEGER NOT NULL,
    branch_id         INTEGER NOT NULL,
    room_id           INTEGER,
    check_in_date     DATE NOT NULL,
    check_out_date    DATE NOT NULL,
    guests            INTEGER NOT NULL DEFAULT 1,
    nights            INTEGER DEFAULT 0,
    total_amount      NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    discount_amount   NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    payable_amount    NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    payment_status    VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (payment_status IN ('pending','paid','partially_paid','failed','refunded')),
    booking_status    VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (booking_status IN ('pending','confirmed','checked_in','checked_out','cancelled','no_show')),
    source            VARCHAR(20) NOT NULL DEFAULT 'reception' CHECK (source IN ('online','walk_in','reception','admin')),
    special_request   TEXT,
    coupon_id         INTEGER,
    created_by        INTEGER,
    actual_check_in   TIMESTAMP,
    actual_check_out  TIMESTAMP,
    checked_in_by     INTEGER,
    checked_out_by    INTEGER,
    id_document       VARCHAR(50),
    id_number         VARCHAR(100),
    vehicle_info      VARCHAR(255),
    extra_charges     NUMERIC(12,2) DEFAULT 0.00,
    extra_charges_note TEXT,
    cancel_reason     TEXT,
    cancelled_at      TIMESTAMP,
    paystack_reference VARCHAR(100),
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (booking_reference),
    CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id)
        REFERENCES rooms (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_coupon FOREIGN KEY (coupon_id)
        REFERENCES coupons (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_created_by FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_bookings_customer_id ON bookings (customer_id);
CREATE INDEX idx_bookings_branch_id ON bookings (branch_id);
CREATE INDEX idx_bookings_room_id ON bookings (room_id);
CREATE INDEX idx_bookings_dates ON bookings (check_in_date, check_out_date);
CREATE INDEX idx_bookings_status ON bookings (booking_status);
CREATE INDEX idx_bookings_payment_status ON bookings (payment_status);
CREATE INDEX idx_bookings_coupon_id ON bookings (coupon_id);
CREATE INDEX idx_bookings_created_by ON bookings (created_by);

-- -----------------------------------------------------------
-- 8. Services
-- -----------------------------------------------------------
CREATE TABLE services (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id   INTEGER NOT NULL,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    price       NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    category    VARCHAR(100),
    image       VARCHAR(255),
    status      VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_services_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_services_branch_id ON services (branch_id);

-- -----------------------------------------------------------
-- 9. Food Categories
-- -----------------------------------------------------------
CREATE TABLE food_categories (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id   INTEGER NOT NULL,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    image       VARCHAR(255),
    status      VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_food_categories_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_food_categories_branch_id ON food_categories (branch_id);

-- -----------------------------------------------------------
-- 10. Food Items
-- -----------------------------------------------------------
CREATE TABLE food_items (
    id               INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id        INTEGER NOT NULL,
    category_id      INTEGER NOT NULL,
    name             VARCHAR(200) NOT NULL,
    description      TEXT,
    price            NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    preparation_time VARCHAR(50),
    image            VARCHAR(255),
    is_available     BOOLEAN NOT NULL DEFAULT TRUE,
    status           VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_food_items_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_food_items_category FOREIGN KEY (category_id)
        REFERENCES food_categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX idx_food_items_branch_id ON food_items (branch_id);
CREATE INDEX idx_food_items_category_id ON food_items (category_id);

-- -----------------------------------------------------------
-- 11. Food Orders
-- -----------------------------------------------------------
CREATE TABLE food_orders (
    id               INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    order_reference  VARCHAR(50) NOT NULL,
    customer_id      INTEGER,
    branch_id        INTEGER NOT NULL,
    table_number     VARCHAR(20),
    order_type       VARCHAR(20) NOT NULL DEFAULT 'dine_in' CHECK (order_type IN ('dine_in','takeaway','delivery')),
    status           VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','preparing','ready','completed','cancelled')),
    total_amount     NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    discount_amount  NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    payable_amount   NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    payment_status   VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (payment_status IN ('pending','paid','partially_paid','failed','refunded')),
    coupon_id        INTEGER,
    waiter_id        INTEGER,
    notes            TEXT,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (order_reference),
    CONSTRAINT fk_food_orders_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_food_orders_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_food_orders_coupon FOREIGN KEY (coupon_id)
        REFERENCES coupons (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_food_orders_waiter FOREIGN KEY (waiter_id)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_food_orders_customer_id ON food_orders (customer_id);
CREATE INDEX idx_food_orders_branch_id ON food_orders (branch_id);
CREATE INDEX idx_food_orders_coupon_id ON food_orders (coupon_id);
CREATE INDEX idx_food_orders_waiter_id ON food_orders (waiter_id);

-- -----------------------------------------------------------
-- 12. Food Order Items
-- -----------------------------------------------------------
CREATE TABLE food_order_items (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    order_id    INTEGER NOT NULL,
    food_item_id INTEGER NOT NULL,
    quantity    INTEGER NOT NULL DEFAULT 1,
    unit_price  NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    total_price NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    notes       TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_food_order_items_order FOREIGN KEY (order_id)
        REFERENCES food_orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_food_order_items_food_item FOREIGN KEY (food_item_id)
        REFERENCES food_items (id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX idx_food_order_items_order_id ON food_order_items (order_id);
CREATE INDEX idx_food_order_items_food_item_id ON food_order_items (food_item_id);

-- -----------------------------------------------------------
-- 13. Reservations
-- -----------------------------------------------------------
CREATE TABLE reservations (
    id                   INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    reservation_reference VARCHAR(50) NOT NULL,
    customer_id          INTEGER NOT NULL,
    branch_id            INTEGER NOT NULL,
    table_number         VARCHAR(20),
    reservation_date     DATE NOT NULL,
    reservation_time     TIME NOT NULL,
    guests               INTEGER NOT NULL DEFAULT 1,
    status               VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','confirmed','seated','cancelled')),
    special_request      TEXT,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (reservation_reference),
    CONSTRAINT fk_reservations_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_reservations_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX idx_reservations_customer_id ON reservations (customer_id);
CREATE INDEX idx_reservations_branch_id ON reservations (branch_id);
CREATE INDEX idx_reservations_date ON reservations (reservation_date);

-- -----------------------------------------------------------
-- 14. Coupons
-- -----------------------------------------------------------
CREATE TABLE coupons (
    id                   INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    code                 VARCHAR(50) NOT NULL,
    title                VARCHAR(200),
    description          TEXT,
    discount_type        VARCHAR(20) NOT NULL DEFAULT 'percentage' CHECK (discount_type IN ('percentage','fixed')),
    discount_value       NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    start_date           DATE NOT NULL,
    end_date             DATE NOT NULL,
    branch_id            INTEGER,
    applicable_to        VARCHAR(20) NOT NULL DEFAULT 'all' CHECK (applicable_to IN ('rooms','food','services','all')),
    minimum_spend        NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    usage_limit          INTEGER NOT NULL DEFAULT 0,
    usage_per_customer   INTEGER NOT NULL DEFAULT 0,
    used_count           INTEGER DEFAULT 0,
    status               VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','expired')),
    valid_from           DATE,
    valid_to             DATE,
    max_uses             INTEGER DEFAULT 0,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (code),
    CONSTRAINT fk_coupons_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_coupons_branch_id ON coupons (branch_id);
CREATE INDEX idx_coupons_status ON coupons (status);
CREATE INDEX idx_coupons_dates ON coupons (start_date, end_date);

-- -----------------------------------------------------------
-- 15. Coupon Usages
-- -----------------------------------------------------------
CREATE TABLE coupon_usages (
    id              INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    coupon_id       INTEGER NOT NULL,
    customer_id     INTEGER NOT NULL,
    booking_id      INTEGER,
    order_id        INTEGER,
    discount_amount NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_coupon_usages_coupon FOREIGN KEY (coupon_id)
        REFERENCES coupons (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_coupon_usages_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_coupon_usages_booking FOREIGN KEY (booking_id)
        REFERENCES bookings (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_coupon_usages_order FOREIGN KEY (order_id)
        REFERENCES food_orders (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_coupon_usages_coupon_id ON coupon_usages (coupon_id);
CREATE INDEX idx_coupon_usages_customer_id ON coupon_usages (customer_id);
CREATE INDEX idx_coupon_usages_booking_id ON coupon_usages (booking_id);
CREATE INDEX idx_coupon_usages_order_id ON coupon_usages (order_id);

-- -----------------------------------------------------------
-- 16. Suppliers
-- -----------------------------------------------------------
CREATE TABLE suppliers (
    id              INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    name            VARCHAR(200) NOT NULL,
    contact_person  VARCHAR(200),
    phone           VARCHAR(50),
    email           VARCHAR(255),
    address         TEXT,
    branch_id       INTEGER NOT NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_suppliers_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_suppliers_branch_id ON suppliers (branch_id);

-- -----------------------------------------------------------
-- 17. Inventory Items
-- -----------------------------------------------------------
CREATE TABLE inventory_items (
    id               INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id        INTEGER NOT NULL,
    name             VARCHAR(200) NOT NULL,
    category         VARCHAR(100),
    description      TEXT,
    unit             VARCHAR(50),
    quantity         NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    reorder_level    NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    price_per_unit   NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    supplier_id      INTEGER,
    status           VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (branch_id, name),
    CONSTRAINT fk_inventory_items_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_inventory_items_supplier FOREIGN KEY (supplier_id)
        REFERENCES suppliers (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_inventory_items_branch_id ON inventory_items (branch_id);
CREATE INDEX idx_inventory_items_supplier_id ON inventory_items (supplier_id);

-- -----------------------------------------------------------
-- 18. Stock Movements
-- -----------------------------------------------------------
CREATE TABLE stock_movements (
    id             INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    item_id        INTEGER NOT NULL,
    branch_id      INTEGER NOT NULL,
    type           VARCHAR(20) NOT NULL DEFAULT 'in' CHECK (type IN ('in','out','transfer')),
    quantity       NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    reason         VARCHAR(100),
    reference      VARCHAR(100),
    unit_price     NUMERIC(10,2),
    supplier_id    INTEGER,
    reference_type VARCHAR(100),
    reference_id   INTEGER,
    notes          TEXT,
    created_by     INTEGER,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_stock_movements_item FOREIGN KEY (item_id)
        REFERENCES inventory_items (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_stock_movements_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_stock_movements_created_by FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_stock_movements_item_id ON stock_movements (item_id);
CREATE INDEX idx_stock_movements_branch_id ON stock_movements (branch_id);
CREATE INDEX idx_stock_movements_created_by ON stock_movements (created_by);

-- -----------------------------------------------------------
-- 19. Live Chat Sessions
-- -----------------------------------------------------------
CREATE TABLE live_chat_sessions (
    id            INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    customer_id   INTEGER,
    visitor_name  VARCHAR(200),
    visitor_email VARCHAR(255),
    visitor_phone VARCHAR(50),
    assigned_to   INTEGER,
    status        VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','closed')),
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at     TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_live_chat_sessions_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_live_chat_sessions_assigned_to FOREIGN KEY (assigned_to)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_live_chat_sessions_customer_id ON live_chat_sessions (customer_id);
CREATE INDEX idx_live_chat_sessions_assigned_to ON live_chat_sessions (assigned_to);

-- -----------------------------------------------------------
-- 20. Live Chat Messages
-- -----------------------------------------------------------
CREATE TABLE live_chat_messages (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    session_id  INTEGER NOT NULL,
    sender_type VARCHAR(20) NOT NULL DEFAULT 'customer' CHECK (sender_type IN ('customer','visitor','receptionist','system')),
    sender_id   INTEGER,
    message     TEXT NOT NULL,
    is_read     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_live_chat_messages_session FOREIGN KEY (session_id)
        REFERENCES live_chat_sessions (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_live_chat_messages_sender FOREIGN KEY (sender_id)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_live_chat_messages_session_id ON live_chat_messages (session_id);
CREATE INDEX idx_live_chat_messages_sender_id ON live_chat_messages (sender_id);

-- -----------------------------------------------------------
-- 21. Chat Sessions (legacy)
-- -----------------------------------------------------------
CREATE TABLE chat_sessions (
    id            INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    customer_id   INTEGER,
    assigned_to   INTEGER,
    status        VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','closed')),
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_chat_sessions_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 22. Chat Messages (legacy)
-- -----------------------------------------------------------
CREATE TABLE chat_messages (
    id              INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    chat_session_id INTEGER NOT NULL,
    sender_type     VARCHAR(20) NOT NULL DEFAULT 'customer',
    sender_id       INTEGER,
    message         TEXT NOT NULL,
    is_read         BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_chat_messages_session FOREIGN KEY (chat_session_id)
        REFERENCES chat_sessions (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 23. Notifications
-- -----------------------------------------------------------
CREATE TABLE notifications (
    id             INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    user_id        INTEGER NOT NULL,
    title          VARCHAR(200) NOT NULL,
    message        TEXT,
    type           VARCHAR(20) NOT NULL DEFAULT 'system' CHECK (type IN ('email','sms','system')),
    reference_type VARCHAR(100),
    reference_id   INTEGER,
    is_read        BOOLEAN NOT NULL DEFAULT FALSE,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_notifications_user_id ON notifications (user_id);
CREATE INDEX idx_notifications_is_read ON notifications (is_read);

-- -----------------------------------------------------------
-- 24. Gallery Items
-- -----------------------------------------------------------
CREATE TABLE gallery_items (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id   INTEGER NOT NULL,
    title       VARCHAR(200),
    description TEXT,
    image       VARCHAR(255) NOT NULL,
    category    VARCHAR(100),
    status      VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_gallery_items_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_gallery_items_branch_id ON gallery_items (branch_id);

-- -----------------------------------------------------------
-- 25. Expenses
-- -----------------------------------------------------------
CREATE TABLE expenses (
    id            INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id     INTEGER NOT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    amount        NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    category      VARCHAR(100),
    receipt       VARCHAR(255),
    receipt_image VARCHAR(255),
    recorded_by   INTEGER,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_expenses_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_expenses_recorded_by FOREIGN KEY (recorded_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_expenses_branch_id ON expenses (branch_id);
CREATE INDEX idx_expenses_recorded_by ON expenses (recorded_by);
CREATE INDEX idx_expenses_category ON expenses (category);

-- -----------------------------------------------------------
-- 26. Invoices
-- -----------------------------------------------------------
CREATE TABLE invoices (
    id             INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    invoice_number VARCHAR(50) NOT NULL,
    customer_id    INTEGER NOT NULL,
    booking_id     INTEGER,
    order_id       INTEGER,
    total_amount   NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    paid_amount    NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    due_amount     NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    status         VARCHAR(20) NOT NULL DEFAULT 'unpaid' CHECK (status IN ('paid','unpaid','overdue','partially_paid')),
    due_date       DATE,
    invoice_date   DATE,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (invoice_number),
    CONSTRAINT fk_invoices_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_booking FOREIGN KEY (booking_id)
        REFERENCES bookings (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_order FOREIGN KEY (order_id)
        REFERENCES food_orders (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_invoices_customer_id ON invoices (customer_id);
CREATE INDEX idx_invoices_booking_id ON invoices (booking_id);
CREATE INDEX idx_invoices_order_id ON invoices (order_id);
CREATE INDEX idx_invoices_status ON invoices (status);

-- -----------------------------------------------------------
-- 27. Payments
-- -----------------------------------------------------------
CREATE TABLE payments (
    id                INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    payment_reference VARCHAR(100),
    customer_id       INTEGER,
    booking_id        INTEGER,
    order_id          INTEGER,
    reservation_id    INTEGER,
    amount            NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    method            VARCHAR(20) NOT NULL DEFAULT 'cash' CHECK (method IN ('paystack','cash','pos','bank_transfer','split_payment')),
    payment_method    VARCHAR(20),
    payment_category  VARCHAR(50),
    status            VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','paid','partially_paid','failed','refunded')),
    channel           VARCHAR(20) NOT NULL DEFAULT 'offline' CHECK (channel IN ('online','offline')),
    reference         VARCHAR(100),
    paystack_reference VARCHAR(100),
    gateway_response  TEXT,
    failure_reason    TEXT,
    receipt_number    VARCHAR(100),
    notes             TEXT,
    recorded_by       INTEGER,
    confirmed_by      INTEGER,
    confirmed_at      TIMESTAMP,
    verified_at       TIMESTAMP,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (payment_reference),
    CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id)
        REFERENCES bookings (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id)
        REFERENCES food_orders (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_payments_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_payments_recorded_by FOREIGN KEY (recorded_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_payments_customer_id ON payments (customer_id);
CREATE INDEX idx_payments_booking_id ON payments (booking_id);
CREATE INDEX idx_payments_order_id ON payments (order_id);
CREATE INDEX idx_payments_reservation_id ON payments (reservation_id);
CREATE INDEX idx_payments_recorded_by ON payments (recorded_by);

-- -----------------------------------------------------------
-- 28. Receipts
-- -----------------------------------------------------------
CREATE TABLE receipts (
    id             INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    booking_id     INTEGER NOT NULL,
    receipt_number VARCHAR(100) NOT NULL,
    amount         NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    generated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_receipts_booking FOREIGN KEY (booking_id)
        REFERENCES bookings (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 29. Refunds
-- -----------------------------------------------------------
CREATE TABLE refunds (
    id           INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    payment_id   INTEGER,
    customer_id  INTEGER,
    branch_id    INTEGER,
    amount       NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    reason       TEXT,
    status       VARCHAR(20) NOT NULL DEFAULT 'completed',
    processed_by INTEGER,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_refunds_payment FOREIGN KEY (payment_id)
        REFERENCES payments (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_refunds_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 30. Paystack Transactions
-- -----------------------------------------------------------
CREATE TABLE paystack_transactions (
    id                    INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    reference             VARCHAR(100) NOT NULL,
    amount                NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    status                VARCHAR(20) DEFAULT 'pending',
    reconciliation_status VARCHAR(20) DEFAULT 'pending',
    verified_at           TIMESTAMP,
    reconciled_at         TIMESTAMP,
    reconciled_by         INTEGER,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_paystack_transactions_reconciled_by FOREIGN KEY (reconciled_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 31. Audit Logs
-- -----------------------------------------------------------
CREATE TABLE audit_logs (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    user_id     INTEGER,
    action      VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id   INTEGER,
    old_values  TEXT,
    new_values  TEXT,
    ip_address  VARCHAR(50),
    branch_id   INTEGER,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_audit_logs_user_id ON audit_logs (user_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs (entity_type, entity_id);
CREATE INDEX idx_audit_logs_action ON audit_logs (action);

-- -----------------------------------------------------------
-- 32. Settings
-- -----------------------------------------------------------
CREATE TABLE settings (
    id         INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    key        VARCHAR(255) NOT NULL,
    value      TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (key)
);

-- -----------------------------------------------------------
-- 33. Password Resets
-- -----------------------------------------------------------
CREATE TABLE password_resets (
    id         INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    user_id    INTEGER NOT NULL,
    token      VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 34. Staff Duty
-- -----------------------------------------------------------
CREATE TABLE staff_duty (
    id         INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    user_id    INTEGER NOT NULL,
    branch_id  INTEGER NOT NULL,
    shift_date DATE NOT NULL,
    status     VARCHAR(20) NOT NULL DEFAULT 'on_duty' CHECK (status IN ('on_duty','absent','on_leave')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_staff_duty_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_staff_duty_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 35. Maintenance Requests
-- -----------------------------------------------------------
CREATE TABLE maintenance_requests (
    id           INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    room_id      INTEGER NOT NULL,
    branch_id    INTEGER NOT NULL,
    issue_type   VARCHAR(100),
    description  TEXT,
    priority     VARCHAR(20) NOT NULL DEFAULT 'medium' CHECK (priority IN ('low','medium','high','urgent')),
    reported_by  INTEGER,
    status       VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','in_progress','resolved','cancelled')),
    resolved_by  INTEGER,
    resolved_at  TIMESTAMP,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_maintenance_requests_room FOREIGN KEY (room_id)
        REFERENCES rooms (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_maintenance_requests_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_maintenance_requests_reported_by FOREIGN KEY (reported_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 36. Housekeeping Logs
-- -----------------------------------------------------------
CREATE TABLE housekeeping_logs (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    room_id     INTEGER NOT NULL,
    cleaned_by  INTEGER,
    cleaned_at  TIMESTAMP,
    notes       TEXT,
    verified_by INTEGER,
    verified_at TIMESTAMP,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_housekeeping_logs_room FOREIGN KEY (room_id)
        REFERENCES rooms (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_housekeeping_logs_cleaned_by FOREIGN KEY (cleaned_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 37. Housekeeping Supply Usage
-- -----------------------------------------------------------
CREATE TABLE housekeeping_supply_usage (
    id        INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    item_id   INTEGER NOT NULL,
    branch_id INTEGER NOT NULL,
    quantity  NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    notes     TEXT,
    used_by   INTEGER,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_housekeeping_supply_usage_item FOREIGN KEY (item_id)
        REFERENCES inventory_items (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 38. Housekeeping Requests
-- -----------------------------------------------------------
CREATE TABLE housekeeping_requests (
    id           INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    room_id      INTEGER NOT NULL,
    request_type VARCHAR(50),
    requested_by INTEGER,
    notes        TEXT,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_housekeeping_requests_room FOREIGN KEY (room_id)
        REFERENCES rooms (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 39. Kitchen Requests
-- -----------------------------------------------------------
CREATE TABLE kitchen_requests (
    id            INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    reference     VARCHAR(50),
    branch_id     INTEGER NOT NULL,
    item_id       INTEGER,
    item_name     VARCHAR(200),
    quantity      NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    unit          VARCHAR(50),
    urgency       VARCHAR(20) DEFAULT 'normal',
    notes         TEXT,
    requested_by  INTEGER,
    status        VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','fulfilled','rejected')),
    approved_by   INTEGER,
    approved_at   TIMESTAMP,
    fulfilled_by  INTEGER,
    fulfilled_at  TIMESTAMP,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_kitchen_requests_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 40. Branch Transfers
-- -----------------------------------------------------------
CREATE TABLE branch_transfers (
    id            INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    from_branch   INTEGER NOT NULL,
    to_branch     INTEGER NOT NULL,
    item_id       INTEGER NOT NULL,
    quantity      NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    notes         TEXT,
    requested_by  INTEGER,
    status        VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected','completed')),
    approved_by   INTEGER,
    approved_at   TIMESTAMP,
    completed_at  TIMESTAMP,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_branch_transfers_from_branch FOREIGN KEY (from_branch)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_branch_transfers_to_branch FOREIGN KEY (to_branch)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_branch_transfers_item FOREIGN KEY (item_id)
        REFERENCES inventory_items (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 41. Customer Issues
-- -----------------------------------------------------------
CREATE TABLE customer_issues (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id   INTEGER NOT NULL,
    customer_id INTEGER,
    issue_type  VARCHAR(100),
    description TEXT,
    priority    VARCHAR(20) DEFAULT 'medium',
    status      VARCHAR(20) NOT NULL DEFAULT 'open' CHECK (status IN ('open','in_progress','resolved','closed')),
    resolved_by INTEGER,
    resolved_at TIMESTAMP,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_customer_issues_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 42. Customer Requests
-- -----------------------------------------------------------
CREATE TABLE customer_requests (
    id               INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id        INTEGER NOT NULL,
    table_number     VARCHAR(20),
    request_type     VARCHAR(100),
    description      TEXT,
    status           VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','acknowledged','resolved','cancelled')),
    acknowledged_by  INTEGER,
    acknowledged_at  TIMESTAMP,
    resolved_by      INTEGER,
    resolved_at      TIMESTAMP,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_customer_requests_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 43. Inventory Requests
-- -----------------------------------------------------------
CREATE TABLE inventory_requests (
    id         INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    branch_id  INTEGER NOT NULL,
    item_name  VARCHAR(200),
    quantity   NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    unit       VARCHAR(50),
    status     VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','fulfilled','rejected')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_inventory_requests_branch FOREIGN KEY (branch_id)
        REFERENCES branches (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 44. Room Supply Allocations
-- -----------------------------------------------------------
CREATE TABLE room_supply_allocations (
    id          INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    item_id     INTEGER NOT NULL,
    branch_id   INTEGER NOT NULL,
    quantity    NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    notes       TEXT,
    allocated_by INTEGER,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_room_supply_allocations_item FOREIGN KEY (item_id)
        REFERENCES inventory_items (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- -----------------------------------------------------------
-- 45. Room Supply Requests
-- -----------------------------------------------------------
CREATE TABLE room_supply_requests (
    id           INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY,
    item_id      INTEGER NOT NULL,
    branch_id    INTEGER NOT NULL,
    quantity     NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    notes        TEXT,
    requested_by INTEGER,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_room_supply_requests_item FOREIGN KEY (item_id)
        REFERENCES inventory_items (id) ON DELETE CASCADE ON UPDATE CASCADE
);
