<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Authorization");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* ------------------------------------------------
   1️⃣ READ TOKEN FROM HEADER
------------------------------------------------ */
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';

if (strpos($auth, 'Bearer ') !== 0) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$token = trim(substr($auth, 7));

/* ------------------------------------------------
   2️⃣ FETCH USER FROM TOKEN
------------------------------------------------ */
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

$user_id = (int)$user['user_id']; // ✅ SAME AS SAVE

/* ------------------------------------------------
   3️⃣ FETCH WEBSITE SETTINGS
------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT hero_title, hero_description, hero_image, banners
    FROM website_settings
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

/* ------------------------------------------------
   4️⃣ DEFAULTS + DECODE
------------------------------------------------ */
if (!$row) {
    $row = [
        "hero_title" => "",
        "hero_description" => "",
        "hero_image" => "",
        "banners" => []
    ];
} else {
    $row["banners"] = $row["banners"]
        ? json_decode($row["banners"], true)
        : [];
}

/* ------------------------------------------------
   5️⃣ IMAGE URL
------------------------------------------------ */
$baseURL = "http://localhost/manager.bookpanlam/public/uploads/";

$row["hero_image_url"] = $row["hero_image"]
    ? $baseURL . $row["hero_image"]
    : "";

$row["user_id"] = $user_id;

/* ------------------------------------------------
   6️⃣ RESPONSE
------------------------------------------------ */
echo json_encode([
    "success" => true,
    "data" => $row
]);
exit;
