<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Authorization");
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* 🔐 Resolve user from Bearer token */
$headers = getallheaders();
$auth = $headers["Authorization"] ?? "";

if (strpos($auth, "Bearer ") !== 0) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$token = substr($auth, 7);

$stmt = $pdo->prepare("
    SELECT user_id 
    FROM users 
    WHERE api_token = ? 
    LIMIT 1
");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $user["user_id"]; // ✅ 52064 (THIS MATCHES site_settings)

/* 📦 Fetch settings */
$stmt = $pdo->prepare("
    SELECT 
        user_id,
        logo,
        favicon,
        email,
        phone,
        whatsapp,
        currency,
        country,
        state,
        address
    FROM site_settings
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

/* 🧼 Defaults if not exists */
if (!$settings) {
    $settings = [
        "user_id"  => $user_id,
        "logo"     => null,
        "favicon"  => null,
        "email"    => null,
        "phone"    => null,
        "whatsapp" => null,
        "currency" => "INR",
        "country"  => null,
        "state"    => null,
        "address"  => null
    ];
}

echo json_encode([
    "success" => true,
    "data" => $settings
]);
