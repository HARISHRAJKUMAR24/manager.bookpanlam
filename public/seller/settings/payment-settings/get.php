<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===============================
   CORS
================================ */
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

/* ✅ Preflight */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===============================
   INCLUDES
================================ */
require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

/* ✅ DB CONNECTION */
$pdo = getDbConnection();
if (!$pdo) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}

/* ===============================
   AUTH (Bearer Token)
================================ */
$headers = getallheaders();

$authHeader =
    $headers['Authorization']
    ?? $headers['authorization']
    ?? '';

if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ✅ Extract token safely */
$token = trim(substr($authHeader, 7));

/* ===============================
   RESOLVE USER
================================ */
$stmt = $pdo->prepare("
    SELECT user_id
    FROM users
    WHERE api_token = ?
    LIMIT 1
");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$user_id = $user['user_id'];

/* ===============================
   FETCH PAYMENT SETTINGS
================================ */
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
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===============================
   RESPONSE
================================ */
echo json_encode([
    "success" => true,
    "data" => [
        "cashInHand"          => (bool)($row['cash_in_hand'] ?? false),
        "razorpayKeyId"       => $row['razorpay_key_id'] ?? "",
        "razorpaySecretKey"   => $row['razorpay_secret_key'] ?? "",
        "phonepeSaltKey"      => $row['phonepe_salt_key'] ?? "",
        "phonepeSaltIndex"    => $row['phonepe_salt_index'] ?? "",
        "phonepeMerchantId"   => $row['phonepe_merchant_id'] ?? "",
        "payuApiKey"          => $row['payu_api_key'] ?? "",
        "payuSalt"            => $row['payu_salt'] ?? "",
    ]
]);
