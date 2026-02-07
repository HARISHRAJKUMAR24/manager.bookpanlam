<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* ---------- AUTH ---------- */
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';

if (strpos($auth, 'Bearer ') !== 0) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$token = trim(substr($auth, 7));

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = (int)$user['user_id'];

/* ---------- FILE ---------- */
if (!isset($_FILES["file"])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit;
}

$file = $_FILES["file"];
$ext  = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

$year  = date("Y");
$month = date("m");
$day   = date("d");

$filename = "hero_" . time() . "." . $ext;

/* ✅ REAL PUBLIC PATH */
$baseDir = __DIR__ . "/../../../uploads";
$relativePath = "sellers/$user_id/website-settings/homepage/$year/$month/$day";
$uploadDir = $baseDir . "/" . $relativePath;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fullPath = $uploadDir . "/" . $filename;

if (!move_uploaded_file($file["tmp_name"], $fullPath)) {
    echo json_encode(["success" => false, "message" => "Upload failed"]);
    exit;
}

echo json_encode([
    "success" => true,
    "filename" => "$relativePath/$filename"
]);
