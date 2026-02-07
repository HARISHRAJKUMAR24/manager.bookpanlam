<?php
// manager.bookpanlam/public/seller/settings/get-payment-availability.php

$allowed_origins = [
    "http://localhost:3000",
    "http://localhost:3001",
    "http://127.0.0.1:3000",
    "http://127.0.0.1:3001"
];

$origin = $_SERVER["HTTP_ORIGIN"] ?? "";

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:3001");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$user_id = $_GET["user_id"] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID is required"
    ]);
    exit();
}

try {
    $pdo = getDbConnection();

    // Get user's plan info
    $stmt = $pdo->prepare("
        SELECT u.plan_id, 
               sp.manual_payment_methods_limit, 
               sp.upi_payment_methods_limit,
               sp.coupons_limit
        FROM users u 
        LEFT JOIN subscription_plans sp ON u.plan_id = sp.id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get current counts
    $manual_count = 0;
    $upi_count = 0;


    // ✅ FIXED: Count from customer_payment table, not manual_payment_methods
    $stmt = $pdo->prepare("SELECT 
    COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) as cash_count,
    COUNT(CASE WHEN payment_method = 'upi' THEN 1 END) as upi_count
    FROM customer_payment 
    WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    $manual_count = $counts['cash_count'] ?? 0;
    $upi_count = $counts['upi_count'] ?? 0;

    // Check availability
    $availability = [
        'manual_payments' => [
            'allowed' => true,
            'limit' => $plan['manual_payment_methods_limit'] ?? 'unlimited',
            'current' => $manual_count,
            'available' => true
        ],
        'upi_payments' => [
            'allowed' => true,
            'limit' => $plan['upi_payment_methods_limit'] ?? 'unlimited',
            'current' => $upi_count,
            'available' => true
        ]
    ];

    // Check limits for manual payments
    if ($plan && $plan['manual_payment_methods_limit'] !== 'unlimited') {
        $limit = (int)$plan['manual_payment_methods_limit'];
        $availability['manual_payments']['available'] = $manual_count < $limit;
        $availability['manual_payments']['allowed'] = $manual_count < $limit;
    }

    // Check limits for UPI payments
    if ($plan && $plan['upi_payment_methods_limit'] !== 'unlimited') {
        $limit = (int)$plan['upi_payment_methods_limit'];
        $availability['upi_payments']['available'] = $upi_count < $limit;
        $availability['upi_payments']['allowed'] = $upi_count < $limit;
    }

    echo json_encode([
        "success" => true,
        "data" => $availability
    ]);
} catch (Exception $e) {
    error_log("Payment availability error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}
