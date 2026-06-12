<?php
if (!defined('PAYSTACK_PUBLIC_KEY')) define('PAYSTACK_PUBLIC_KEY', '');
if (!defined('PAYSTACK_SECRET_KEY')) define('PAYSTACK_SECRET_KEY', '');
if (!defined('PAYSTACK_CALLBACK_URL')) define('PAYSTACK_CALLBACK_URL', APP_URL . '/modules/payments/paystack-callback');
if (!defined('PAYSTACK_WEBHOOK_URL')) define('PAYSTACK_WEBHOOK_URL', APP_URL . '/modules/payments/paystack-webhook');
if (!defined('PAYSTACK_CURRENCY')) define('PAYSTACK_CURRENCY', 'NGN');
