<?php

return [
    'merchant_id' => env('SEPAY_SANDBOX_MERCHANT_ID'),
    'merchant_secret' => env('SEPAY_SANDBOX_MERCHANT_SECRET'),
    'ipn_secret' => env('SEPAY_SANDBOX_IPN_SECRET'),
    'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
    'test_account' => env('SEPAY_TEST_ACCOUNT'),
    'test_va' => env('SEPAY_TEST_VA'),
];
