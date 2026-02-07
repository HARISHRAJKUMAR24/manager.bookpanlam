<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

$user_id = (int)$user['user_id']; // ✅ 32128

/* ------------------------------------------------
   3️⃣ READ JSON BODY
------------------------------------------------ */
$data = json_decode(file_get_contents("php://input"), true);

$hero_title       = trim($data["hero_title"] ?? "");
$hero_description = trim($data["hero_description"] ?? "");
$hero_image       = trim($data["hero_image"] ?? "");
$banners          = $data["banners"] ?? [];

$banners_json = json_encode($banners, JSON_UNESCAPED_SLASHES);

/* ------------------------------------------------
   4️⃣ CHECK EXISTING ROW
------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT id 
    FROM website_settings 
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$exists = $stmt->fetch();

/* ------------------------------------------------
   5️⃣ INSERT / UPDATE
------------------------------------------------ */
if ($exists) {
    $sql = "
        UPDATE website_settings
        SET hero_title = ?,
            hero_description = ?,
            hero_image = ?,
            banners = ?
        WHERE user_id = ?
    ";
    $params = [
        $hero_title,
        $hero_description,
        $hero_image,
        $banners_json,
        $user_id
    ];
} else {
    $sql = "
        INSERT INTO website_settings
        (user_id, hero_title, hero_description, hero_image, banners)
        VALUES (?, ?, ?, ?, ?)
    ";
    $params = [
        $user_id,
        $hero_title,
        $hero_description,
        $hero_image,
        $banners_json
    ];
}

$ok = $pdo->prepare($sql)->execute($params);

/* ------------------------------------------------
   6️⃣ RESPONSE
------------------------------------------------ */
if ($ok) {
    echo json_encode([
        "success" => true,
        "message" => "Homepage settings saved successfully"
    ]);
    exit;
}

echo json_encode([
    "success" => false,
    "message" => "Database update failed"
]);
exit;
