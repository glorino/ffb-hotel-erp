<?php

define('ROOM_STATUSES', json_encode([
    'available',
    'reserved',
    'occupied',
    'cleaning',
    'maintenance',
    'out_of_service',
]));

define('BOOKING_STATUSES', json_encode([
    'pending',
    'confirmed',
    'checked_in',
    'checked_out',
    'cancelled',
    'no_show',
]));

define('PAYMENT_STATUSES', json_encode([
    'pending',
    'paid',
    'partially_paid',
    'failed',
    'refunded',
]));

define('PAYMENT_METHODS', json_encode([
    'flutterwave',
    'cash',
    'pos',
    'bank_transfer',
    'split_payment',
]));

define('BOOKING_SOURCES', json_encode([
    'online',
    'walk_in',
    'reception',
    'admin',
]));

define('USER_ROLES', json_encode([
    'business_owner' => 'Business Owner',
    'admin'          => 'Admin',
    'branch_manager' => 'Branch Manager',
    'receptionist'   => 'Receptionist',
    'kitchen_chef'   => 'Kitchen Chef',
    'waiter'         => 'Waiter',
    'inventory_manager' => 'Inventory Manager',
    'housekeeping'   => 'Housekeeping',
    'accountant'     => 'Accountant',
    'customer'       => 'Customer',
]));

define('COUPON_DISCOUNT_TYPES', json_encode([
    'percentage',
    'fixed',
]));

define('COUPON_APPLICABLE_TO', json_encode([
    'rooms',
    'food',
    'services',
    'all',
]));

define('ORDER_TYPES', json_encode([
    'dine_in',
    'takeaway',
    'delivery',
]));

define('ORDER_STATUSES', json_encode([
    'pending',
    'preparing',
    'ready',
    'completed',
    'cancelled',
]));

define('RESERVATION_STATUSES', json_encode([
    'pending',
    'confirmed',
    'seated',
    'cancelled',
]));

define('STOCK_MOVEMENT_TYPES', json_encode([
    'in',
    'out',
    'transfer',
]));

define('CHAT_SENDER_TYPES', json_encode([
    'customer',
    'visitor',
    'receptionist',
    'system',
]));

define('NOTIFICATION_TYPES', json_encode([
    'email',
    'sms',
    'system',
]));

define('PAYMENT_CHANNELS', json_encode([
    'online',
    'offline',
]));

define('INVOICE_STATUSES', json_encode([
    'paid',
    'unpaid',
    'overdue',
    'partially_paid',
]));

define('EXPENSE_CATEGORIES', json_encode([
    'utilities',
    'food_beverages',
    'cleaning_supplies',
    'maintenance',
    'salaries',
    'marketing',
    'taxes',
    'miscellaneous',
]));

define('GALLERY_CATEGORIES', json_encode([
    'rooms',
    'restaurant',
    'lobby',
    'exterior',
    'events',
    'other',
]));
