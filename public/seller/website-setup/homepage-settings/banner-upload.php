<?php
/* =========================================================
   HEADERS
========================================================= */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* =========================================================
   CONFIG + DB
========================================================= */
require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* =========================================================
   1️⃣ READ TOKEN
========================================================= */
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

/* =========================================================
   2️⃣ FETCH USER
========================================================= */
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

$user_id = (int)$user['user_id'];

/* =========================================================
   3️⃣ FILE VALIDATION
========================================================= */
if (!isset($_FILES['image'])) {
    echo json_encode([
        "success" => false,
        "message" => "No image uploaded"
    ]);
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "message" => "Upload error"
    ]);
    exit;
}

/* =========================================================
   4️⃣ IMAGE VALIDATION
========================================================= */
$allowedTypes = ["image/jpeg", "image/png", "image/webp"];

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid image type"
    ]);
    exit;
}

/* =========================================================
   5️⃣ SAVE FILE (INSIDE public/uploads)
========================================================= */
$year  = date("Y");
$month = date("m");
$day   = date("d");

/**
 * FINAL STORAGE:
 * public/uploads/seller/USER_ID/website-settings/homepage/banners/Y/m/d/
 */
$uploadsBase = realpath(__DIR__ . "/../../../../public/uploads");

$uploadDir = $uploadsBase
    . "/sellers/$user_id"
    . "/website-settings/homepage/banners"
    . "/$year/$month/$day";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid("banner_", true) . "." . $ext;
$destination = "$uploadDir/$filename";

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save image"
    ]);
    exit;
}

/* =========================================================
   6️⃣ RESPONSE (RELATIVE PUBLIC PATH)
========================================================= */
echo json_encode([
    "success" => true,
    "data" => [
        "path" =>
            "seller/$user_id/website-settings/homepage/banners"
            . "/$year/$month/$day/$filename"
    ]
]);
exit;
