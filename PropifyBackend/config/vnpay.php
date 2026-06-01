<?php

return [
    'tmn_code' => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),
    'payment_url' => env('VNPAY_PAYMENT_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'return_url' => env('VNPAY_RETURN_URL', rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/api/v1/payments/vnpay/return'),
    'bank_code' => env('VNPAY_BANK_CODE', ''),
];
