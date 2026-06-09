<?php
define('FLW_PUBLIC_KEY', getenv('FLW_PUBLIC_KEY') ?: '');
define('FLW_SECRET_KEY', getenv('FLW_SECRET_KEY') ?: '');
define('FLW_ENCRYPTION_KEY', getenv('FLW_ENCRYPTION_KEY') ?: '');
define('FLW_CALLBACK_URL', APP_URL . '/modules/payments/flutterwave-callback');
define('FLW_WEBHOOK_URL', APP_URL . '/modules/payments/flutterwave-webhook');
define('FLW_CURRENCY', 'NGN');
