<?php
define('APP_NAME', 'FFB Hotel ERP');
define('APP_URL', getenv('APP_URL') ?: 'https://hotel-erp-php.vercel.app');
define('APP_ENV', 'production');
define('TIMEZONE', 'Africa/Lagos');
define('CURRENCY', 'NGN');
define('CURRENCY_SYMBOL', '₦');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
date_default_timezone_set(TIMEZONE);
