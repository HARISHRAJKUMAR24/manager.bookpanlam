<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* 🔐 Resolve user via token */
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

$user_id = $user["user_id"]; // ✅ 5-digit public user_id

/* 📁 Date-based folders */
$year  = date("Y");
$month = date("m");
$day   = date("d");

$relativePath = "sellers/$user_id/site-settings/favicon/$year/$month/$day/";
$uploadDir = "../../../uploads/" . $relativePath;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!isset($_FILES["file"])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit;
}

$file = $_FILES["file"];
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$filename = uniqid("favicon_") . "." . $ext;

if (move_uploaded_file($file["tmp_name"], $uploadDir . $filename)) {
    echo json_encode([
        "success" => true,
        "filename" => $relativePath . $filename
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Upload failed"]);
}
