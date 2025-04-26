<?php
/**
 * GuruSinergi - Payment Callback
 * 
 * File ini menangani callback dari payment gateway (Tripay)
 */

// Include file konfigurasi
require_once 'config/config.php';

// Include file database
require_once 'config/database.php';

// Include file functions
require_once 'includes/functions.php';

// Include file payment functions
require_once 'includes/payment-functions.php';

// Log callback untuk debugging
$logFile = 'tripay_callback.log';
$raw_data = file_get_contents("php://input");
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $raw_data . PHP_EOL, FILE_APPEND);

// Verifikasi signature
$callbackSignature = isset($_SERVER['HTTP_X_CALLBACK_SIGNATURE']) ? $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] : '';
$privateKey = config('tripay_merchant_code');

if ($callbackSignature !== hash_hmac('sha256', $raw_data, $privateKey)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid signature'
    ]);
    exit;
}

// Proses data callback
if (process_payment_callback($raw_data)) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Callback processed successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process callback'
    ]);
}