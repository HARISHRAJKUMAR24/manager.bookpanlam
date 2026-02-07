<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* =====================
   PUBLIC ACCESS
===================== */
$userId = (int)($_GET['user_id'] ?? 0);

if (!$userId) {
    echo json_encode([
        "success" => false,
        "data" => [],
        "message" => "user_id required"
    ]);
    exit;
}

/* =====================
   FETCH PAYMENT SETTINGS
===================== */
$stmt = $pdo->prepare("
    SELECT
        cash_in_hand,
        razorpay_key_id,
        razorpay_secret_key,
        phonepe_salt_key,
        phonepe_salt_index,
        phonepe_merchant_id,
        payu_api_key,
        payu_salt
    FROM site_settings
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $settings ?: []
]);
