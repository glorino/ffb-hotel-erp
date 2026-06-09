<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
$uri = str_replace('.php', '', $uri);
if ($uri === '' || $uri === '/index') $uri = '/';

$file_map = [
    '/'                                                   => 'index.php',
    '/about'                                              => 'about.php',
    '/rooms'                                              => 'rooms.php',
    '/suites'                                             => 'suites.php',
    '/services'                                           => 'services.php',
    '/gallery'                                            => 'gallery.php',
    '/booking'                                            => 'booking.php',
    '/order'                                              => 'order.php',
    '/contact'                                            => 'contact.php',
    '/reservation'                                        => 'reservation.php',
    '/login'                                              => 'login.php',
    '/register'                                           => 'register.php',
    '/forgot-password'                                    => 'forgot-password.php',
    '/reset-password'                                     => 'reset-password.php',
    '/logout'                                             => 'logout.php',
    '/403'                                                => '403.php',
    '/404'                                                => '404.php',
    '/500'                                                => '500.php',
    '/auth/login-handler'                                 => 'auth/login-handler.php',
    '/auth/logout-handler'                                => 'auth/logout-handler.php',
    '/auth/register-handler'                              => 'auth/register-handler.php',
    '/auth/password-reset-request'                        => 'auth/password-reset-request.php',
    '/auth/password-reset-confirm'                        => 'auth/password-reset-confirm.php',
    '/ajax/validate-coupon'                               => 'ajax/validate-coupon.php',
    '/ajax/room-calendar'                                 => 'ajax/room-calendar.php',
    '/ajax/seed-demo'                                      => 'ajax/seed-demo.php',
    '/ajax/migrate-rooms'                                  => 'ajax/migrate-rooms.php',
    '/modules/bookings/check-availability'                => 'modules/bookings/check-availability.php',
    '/modules/bookings/create-booking'                    => 'modules/bookings/create-booking.php',
    '/modules/bookings/confirm-booking'                   => 'modules/bookings/confirm-booking.php',
    '/modules/bookings/cancel-booking'                    => 'modules/bookings/cancel-booking.php',
    '/modules/bookings/check-in-handler'                  => 'modules/bookings/check-in-handler.php',
    '/modules/bookings/check-out-handler'                 => 'modules/bookings/check-out-handler.php',
    '/modules/booking/get-rooms'                          => 'modules/booking/get-rooms.php',
    '/modules/booking/get-services'                       => 'modules/booking/get-services.php',
    '/modules/booking/process'                            => 'modules/booking/process.php',
    '/modules/payments/paystack-initialize'               => 'modules/payments/paystack-initialize.php',
    '/modules/payments/paystack-callback'                 => 'modules/payments/paystack-callback.php',
    '/modules/payments/paystack-verify'                   => 'modules/payments/paystack-verify.php',
    '/modules/payments/paystack-webhook'                  => 'modules/payments/paystack-webhook.php',
    '/modules/payments/flutterwave-callback'              => 'modules/payments/flutterwave-callback.php',
    '/modules/payments/flutterwave-webhook'               => 'modules/payments/flutterwave-webhook.php',
    '/modules/payments/offline-payment-handler'           => 'modules/payments/offline-payment-handler.php',
    '/modules/payments/receipt-generator'                 => 'modules/payments/receipt-generator.php',
    '/modules/coupons/create-coupon'                      => 'modules/coupons/create-coupon.php',
    '/modules/coupons/validate-coupon'                    => 'modules/coupons/validate-coupon.php',
    '/modules/coupons/apply-coupon'                       => 'modules/coupons/apply-coupon.php',
    '/modules/coupons/coupon-usage'                       => 'modules/coupons/coupon-usage.php',
    '/modules/live-chat/start-session'                    => 'modules/live-chat/start-session.php',
    '/modules/live-chat/send-message'                     => 'modules/live-chat/send-message.php',
    '/modules/live-chat/fetch-messages'                   => 'modules/live-chat/fetch-messages.php',
    '/modules/live-chat/assign-receptionist'              => 'modules/live-chat/assign-receptionist.php',
    '/modules/live-chat/close-session'                    => 'modules/live-chat/close-session.php',
    '/modules/notifications/send-email'                   => 'modules/notifications/send-email.php',
    '/modules/notifications/send-sms'                     => 'modules/notifications/send-sms.php',
    '/modules/notifications/booking-email'                => 'modules/notifications/booking-email.php',
    '/modules/notifications/booking-sms'                  => 'modules/notifications/booking-sms.php',
    '/modules/notifications/payment-email'                => 'modules/notifications/payment-email.php',
    '/modules/notifications/payment-sms'                  => 'modules/notifications/payment-sms.php',
    '/modules/reports/sales-report'                       => 'modules/reports/sales-report.php',
    '/modules/reports/booking-report'                     => 'modules/reports/booking-report.php',
    '/modules/reports/branch-report'                      => 'modules/reports/branch-report.php',
    '/modules/reports/occupancy-report'                   => 'modules/reports/occupancy-report.php',
    '/modules/reports/inventory-report'                   => 'modules/reports/inventory-report.php',
];

$role_prefixes = ['owner', 'admin', 'branch-manager', 'reception', 'kitchen', 'waiter', 'inventory', 'housekeeping', 'accountant', 'customer'];

foreach ($role_prefixes as $role) {
    $file_map["/$role/dashboard"]                            = "$role/dashboard.php";
    $file_map["/$role/dashboard.php"]                        = "$role/dashboard.php";
}

$file_map['/admin/branches']                           = 'admin/branches.php';
$file_map['/admin/rooms']                              = 'admin/rooms.php';
$file_map['/admin/services']                           = 'admin/services.php';
$file_map['/admin/food-menu']                          = 'admin/food-menu.php';
$file_map['/admin/staff']                              = 'admin/staff.php';
$file_map['/admin/bookings']                           = 'admin/bookings.php';
$file_map['/admin/orders']                             = 'admin/orders.php';
$file_map['/admin/customers']                          = 'admin/customers.php';
$file_map['/admin/coupons']                            = 'admin/coupons.php';
$file_map['/admin/gallery']                            = 'admin/gallery.php';
$file_map['/admin/settings']                           = 'admin/settings.php';
$file_map['/admin/website-content']                    = 'admin/website-content.php';
$file_map['/admin/reports']                            = 'admin/reports.php';
$file_map['/branch-manager/bookings']                  = 'branch-manager/bookings.php';
$file_map['/branch-manager/orders']                    = 'branch-manager/orders.php';
$file_map['/branch-manager/rooms']                     = 'branch-manager/rooms.php';
$file_map['/branch-manager/inventory']                 = 'branch-manager/inventory.php';
$file_map['/branch-manager/daily-sales']               = 'branch-manager/daily-sales.php';
$file_map['/branch-manager/customer-issues']           = 'branch-manager/customer-issues.php';
$file_map['/branch-manager/reports']                   = 'branch-manager/reports.php';
$file_map['/branch-manager/staff-on-duty']             = 'branch-manager/staff-on-duty.php';
$file_map['/reception/room-availability']              = 'reception/room-availability.php';
$file_map['/reception/walk-in-booking']                = 'reception/walk-in-booking.php';
$file_map['/reception/online-bookings']                = 'reception/online-bookings.php';
$file_map['/reception/check-in']                       = 'reception/check-in.php';
$file_map['/reception/check-out']                      = 'reception/check-out.php';
$file_map['/reception/guest-records']                  = 'reception/guest-records.php';
$file_map['/reception/receipts']                       = 'reception/receipts.php';
$file_map['/reception/payments']                       = 'reception/payments.php';
$file_map['/reception/coupons']                        = 'reception/coupons.php';
$file_map['/reception/live-chat']                      = 'reception/live-chat.php';
$file_map['/kitchen/incoming-orders']                  = 'kitchen/incoming-orders.php';
$file_map['/kitchen/preparing-orders']                 = 'kitchen/preparing-orders.php';
$file_map['/kitchen/ready-orders']                     = 'kitchen/ready-orders.php';
$file_map['/kitchen/completed-orders']                 = 'kitchen/completed-orders.php';
$file_map['/kitchen/order-history']                    = 'kitchen/order-history.php';
$file_map['/kitchen/inventory-requests']               = 'kitchen/inventory-requests.php';
$file_map['/kitchen/unavailable-items']                = 'kitchen/unavailable-items.php';
$file_map['/waiter/tables']                            = 'waiter/tables.php';
$file_map['/waiter/new-order']                         = 'waiter/new-order.php';
$file_map['/waiter/bills']                             = 'waiter/bills.php';
$file_map['/waiter/payments']                          = 'waiter/payments.php';
$file_map['/waiter/customer-requests']                 = 'waiter/customer-requests.php';
$file_map['/waiter/kitchen-status']                    = 'waiter/kitchen-status.php';
$file_map['/waiter/active-orders']                     = 'waiter/active-orders.php';
$file_map['/inventory/stock-items']                    = 'inventory/stock-items.php';
$file_map['/inventory/stock-in']                       = 'inventory/stock-in.php';
$file_map['/inventory/stock-out']                      = 'inventory/stock-out.php';
$file_map['/inventory/low-stock-alerts']               = 'inventory/low-stock-alerts.php';
$file_map['/inventory/suppliers']                      = 'inventory/suppliers.php';
$file_map['/inventory/room-supplies']                  = 'inventory/room-supplies.php';
$file_map['/inventory/branch-transfers']               = 'inventory/branch-transfers.php';
$file_map['/inventory/reports']                        = 'inventory/reports.php';
$file_map['/inventory/kitchen-requests']               = 'inventory/kitchen-requests.php';
$file_map['/housekeeping/rooms-to-clean']              = 'housekeeping/rooms-to-clean.php';
$file_map['/housekeeping/cleaned-rooms']               = 'housekeeping/cleaned-rooms.php';
$file_map['/housekeeping/occupied-rooms']              = 'housekeeping/occupied-rooms.php';
$file_map['/housekeeping/room-supplies']               = 'housekeeping/room-supplies.php';
$file_map['/housekeeping/maintenance-requests']        = 'housekeeping/maintenance-requests.php';
$file_map['/housekeeping/reports']                     = 'housekeeping/reports.php';
$file_map['/accountant/payments']                      = 'accountant/payments.php';
$file_map['/accountant/invoices']                      = 'accountant/invoices.php';
$file_map['/accountant/expenses']                      = 'accountant/expenses.php';
$file_map['/accountant/paystack-transactions']         = 'accountant/paystack-transactions.php';
$file_map['/accountant/offline-payments']              = 'accountant/offline-payments.php';
$file_map['/accountant/refunds']                       = 'accountant/refunds.php';
$file_map['/accountant/reports']                       = 'accountant/reports.php';
$file_map['/accountant/revenue']                       = 'accountant/revenue.php';
$file_map['/customer/my-bookings']                     = 'customer/my-bookings.php';
$file_map['/customer/my-orders']                       = 'customer/my-orders.php';
$file_map['/customer/my-reservations']                 = 'customer/my-reservations.php';
$file_map['/customer/payments']                        = 'customer/payments.php';
$file_map['/customer/profile']                         = 'customer/profile.php';
if (isset($file_map[$uri])) {
    $script_name = basename($file_map[$uri]);
    $current_page = $script_name;
    $GLOBALS['current_page'] = $current_page;

    $depth = substr_count($uri, '/');
    $redirect_path = str_repeat('../', $depth);

    $file_path = __DIR__ . '/../' . $file_map[$uri];
    if (file_exists($file_path)) {
        require $file_path;
        exit;
    }
}

http_response_code(404);
require __DIR__ . '/../404.php';
