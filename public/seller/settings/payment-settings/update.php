<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===============================
   CORS
================================ */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowedOrigins = [
    'http://localhost:3000'
];

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

/* ✅ PRE-FLIGHT EXIT (IMPORTANT) */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===============================
   INCLUDES
================================ */
require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   AUTH
================================ */
$headers = getallheaders();

/* 🔒 Handle header casing safely */
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

/* ✅ Extract & sanitize token */
$token = trim(substr($authHeader, 7));

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
   INPUT
================================ */
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid payload"
    ]);
    exit;
}

/* ===============================
   FIELD MAP
================================ */
$fieldMap = [
    "cashInHand"         => "cash_in_hand",
    "razorpayKeyId"      => "razorpay_key_id",
        "razorpaySecretKey"   => "razorpay_secret_key", // ✅ ADD HERE

    "phonepeSaltKey"     => "phonepe_salt_key",
    "phonepeSaltIndex"   => "phonepe_salt_index",
    "phonepeMerchantId"  => "phonepe_merchant_id",
    "payuApiKey"         => "payu_api_key",
    "payuSalt"           => "payu_salt",
];

$set = [];
$values = [];

foreach ($fieldMap as $frontendKey => $dbColumn) {
    if (array_key_exists($frontendKey, $data)) {
        $set[] = "$dbColumn = ?";
        $values[] = $data[$frontendKey];
    }
}

if (empty($set)) {
    echo json_encode([
        "success" => false,
        "message" => "No valid fields"
    ]);
    exit;
}

/* ===============================
   UPDATE
================================ */
$values[] = $user_id;

$sql = "
    UPDATE site_settings
    SET " . implode(", ", $set) . "
    WHERE user_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute($values);

echo json_encode([
    "success" => true,
    "message" => "Payment settings updated successfully"
]);
