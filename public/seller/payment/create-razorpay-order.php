<?php
// manager.bookpanlam/public/seller/payment/create-razorpay-order.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required data
$required = ['amount', 'currency', 'plan_id'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        echo json_encode([
            "success" => false,
            "message" => "Missing required field: $field"
        ]);
        exit;
    }
}

$amount = intval($data['amount']) * 100; // Convert to paise
$currency = $data['currency'];
$plan_id = intval($data['plan_id']);

// Get user email/phone for identification (optional)
$user_email = $data['user_email'] ?? '';
$user_phone = $data['user_phone'] ?? '';

// Get receipt ID - use plan_id and timestamp for uniqueness
$receipt = "receipt_" . time() . "_" . $plan_id;

// Get Razorpay credentials
$pdo = getDbConnection();
$sql = "SELECT razorpay_key_id, razorpay_key_secret FROM settings LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings || empty($settings['razorpay_key_id']) || empty($settings['razorpay_key_secret'])) {
    echo json_encode([
        "success" => false,
        "message" => "Razorpay credentials not configured"
    ]);
    exit;
}

$key_id = $settings['razorpay_key_id'];
$key_secret = $settings['razorpay_key_secret'];

// Create Razorpay order
$orderData = [
    'amount' => $amount,
    'currency' => $currency,
    'receipt' => $receipt,
    'payment_capture' => 1,
    'notes' => [
        'plan_id' => $plan_id,
        'user_email' => $user_email,
        'user_phone' => $user_phone
    ]
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Add timeout and SSL verification options
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    $order = json_decode($response, true);
    echo json_encode([
        "success" => true,
        "order" => $order,
        "receipt_id" => $receipt
    ]);
} else {
    error_log("Razorpay order creation failed. HTTP Code: $httpCode, Response: $response, cURL Error: $curlError");
    
    $errorResponse = json_decode($response, true);
    $errorMessage = "Failed to create Razorpay order";
    
    if (isset($errorResponse['error'])) {
        $errorMessage = $errorResponse['error']['description'] ?? $errorResponse['error']['code'] ?? $errorMessage;
    }
    
    echo json_encode([
        "success" => false,
        "message" => $errorMessage,
        "http_code" => $httpCode,
        "curl_error" => $curlError
    ]);
}