<?php
// manager.bookpanlam/public/seller/payment/create-payu-order.php

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

try {
    $amount = (float) ($data['amount'] ?? 0);
    $currency = strtoupper($data['currency'] ?? 'INR');
    $plan_id = $data['plan_id'] ?? null;
    $user_email = trim($data['user_email'] ?? '');
    $user_phone = trim($data['user_phone'] ?? '');
    $user_name = trim($data['user_name'] ?? 'Customer');
    $billing_data = $data['billing_data'] ?? [];
    $plan_data = $data['plan_data'] ?? [];

    // Get PayU credentials
    $stmt = $pdo->prepare("SELECT payu_merchant_key, payu_salt FROM settings LIMIT 1");
    $stmt->execute();
    $credentials = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$credentials || empty($credentials['payu_merchant_key'])) {
        throw new Exception("PayU not configured");
    }

    $merchantKey = trim($credentials['payu_merchant_key']);
    $salt = trim($credentials['payu_salt']);

    // Generate transaction ID
    $txnid = substr(hash('sha256', uniqid(mt_rand(), true)), 0, 20);

    // Prepare product info
    $productinfo = $plan_data['plan_name'] ?? 'Subscription Plan';
    $productinfo = substr(str_replace('|', '', $productinfo), 0, 100); // Limit length
    
    // Format amount - PayU needs amount in smallest unit (paisa/cents)
    if ($currency === 'INR') {
        $amountPayU = $amount * 100; // Convert to paisa
    } else {
        $amountPayU = $amount * 100; // Convert to cents for USD
    }
    
    $amountFormatted = number_format($amount, 2, '.', '');

    // UDF fields - pass all data needed for success.php
    $udf1 = (string)($plan_id ?? ''); // Plan ID
    $udf2 = (string)($billing_data['user_id'] ?? ''); // User ID
    $udf3 = (string)($plan_data['amount'] ?? $amount); // Total amount
    $udf4 = (string)($plan_data['gst_amount'] ?? '0'); // GST amount
    $udf5 = (string)$currency; // Currency code
    $udf6 = (string)($billing_data['gstin'] ?? ''); // GSTIN

    // Generate hash
    $hashString = $merchantKey . '|' . $txnid . '|' . $amountFormatted . '|' . 
                  $productinfo . '|' . $user_name . '|' . $user_email . '|' . 
                  $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . 
                  $udf5 . '||||||' . $salt;

    $hash = strtolower(hash('sha512', $hashString));

    // URLs
    $surl = "http://localhost/manager.bookpanlam/public/seller/payment/payu-success.php";
    $furl = "http://localhost/manager.bookpanlam/public/seller/payment/payu-failure.php";
    
    // For now, use India endpoint only
    $payuEndpoint = "https://test.payu.in/_payment";

    echo json_encode([
        "success" => true,
        "endpoint" => $payuEndpoint,
        "key" => $merchantKey,
        "txnid" => $txnid,
        "amount" => $amountFormatted,
        "productinfo" => $productinfo,
        "firstname" => $user_name,
        "email" => $user_email,
        "phone" => $user_phone,
        "surl" => $surl,
        "furl" => $furl,
        "hash" => $hash,
        "service_provider" => "payu",
        "currency" => $currency,
        "udf1" => $udf1,
        "udf2" => $udf2,
        "udf3" => $udf3,
        "udf4" => $udf4,
        "udf5" => $udf5,
        "udf6" => $udf6
    ]);

} catch (Exception $e) {
    error_log("PayU CREATE ERROR: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}