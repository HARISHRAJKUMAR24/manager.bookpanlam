<?php
// manager.bookpanlam/public/seller/payment/payu-credentials.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

try {
    // Get PayU credentials from settings table - using same structure as Razorpay
    $sql = "SELECT 
                payu_merchant_key,
                payu_salt,
                payu_client_id,
                payu_client_secret
            FROM settings LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        echo json_encode([
            "payu_merchant_key" => "",
            "payu_salt" => "",
            "payu_client_id" => "",
            "payu_client_secret" => "",
            "payu_mode" => "test",
            "payu_endpoint" => "https://test.payu.in/_payment"
        ]);
        exit;
    }

    echo json_encode([
        "payu_merchant_key" => $settings['payu_merchant_key'] ?? "",
        "payu_salt" => $settings['payu_salt'] ?? "",
        "payu_client_id" => $settings['payu_client_id'] ?? "",
        "payu_client_secret" => $settings['payu_client_secret'] ?? "",
        "payu_mode" => "test", // Default to test mode
        "payu_endpoint" => "https://test.payu.in/_payment" // Default test endpoint
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "payu_merchant_key" => "",
        "payu_salt" => "",
        "payu_client_id" => "",
        "payu_client_secret" => "",
        "payu_mode" => "test",
        "payu_endpoint" => "https://test.payu.in/_payment",
        "error" => $e->getMessage()
    ]);
}
?>