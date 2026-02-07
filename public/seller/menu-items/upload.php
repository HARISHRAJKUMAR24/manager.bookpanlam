<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

/* ===============================
   HEADERS / CORS
================================ */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   AUTH
================================ */
$token = $_COOKIE['token'] ?? '';

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = (int)$user->user_id;

/* ===============================
   FILE CHECK
================================ */
if (!isset($_FILES['file'])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit;
}

$file = $_FILES['file'];

/* ===============================
   DELETE OLD IMAGE IF PROVIDED
================================ */
$oldImage = $_POST['old_image'] ?? null;

if ($oldImage) {
    $oldPath = __DIR__ . "/../../" . ltrim($oldImage, "/");

    if (file_exists($oldPath)) {
        unlink($oldPath);
    }
}

/* ===============================
   VALIDATION
================================ */
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowed[$file['type']])) {
    echo json_encode(["success" => false, "message" => "Invalid file type"]);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(["success" => false, "message" => "Max 5MB"]);
    exit;
}

/* ===============================
   DIRECTORY
================================ */
$year = date("Y");
$month = date("m");
$day = date("d");

$uploadBase = __DIR__ . "/../../uploads";
$relativeDir = "sellers/$user_id/menu-settings/$year/$month/$day";

$uploadDir = $uploadBase . "/" . $relativeDir;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ===============================
   FILENAME
================================ */
$ext = $allowed[$file['type']];
$fileName = "menu_item_" . time() . "." . $ext;

$fullPath = $uploadDir . "/" . $fileName;

/* ===============================
   SAVE FILE
================================ */
if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
    echo json_encode(["success" => false, "message" => "Failed to save"]);
    exit;
}

echo json_encode([
    "success"  => true,
    "imageUrl" => "/uploads/" . $relativeDir . "/" . $fileName
]);
exit;
