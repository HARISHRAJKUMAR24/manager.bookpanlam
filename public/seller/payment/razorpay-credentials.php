<?php
// manager.bookpanlam/public/seller/payment/razorpay-credentials.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// Get Razorpay credentials from settings table
$sql = "SELECT razorpay_key_id, razorpay_key_secret FROM settings LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    // Return default test credentials
    echo json_encode([
        "razorpay_key_id" => "",
        "razorpay_key_secret" => ""
    ]);
    exit;
}

echo json_encode([
    "razorpay_key_id" => $settings['razorpay_key_id'] ?? "",
    "razorpay_key_secret" => $settings['razorpay_key_secret'] ?? ""
]);