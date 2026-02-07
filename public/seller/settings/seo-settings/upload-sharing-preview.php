<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";
require_once "../../../../src/auth.php";

$pdo = getDbConnection();

/* AUTH */
$user = getAuthenticatedUser($pdo);
$user_id = $user['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

/* DELETE OLD FILE */
$oldFile = $_POST["old_file"] ?? "";

if (!empty($oldFile)) {
    $uploadsRoot = realpath(__DIR__ . "/../../../../public/uploads");

    // normalize incoming path
    $cleanOld = ltrim($oldFile, "/");

    $absoluteOld = $uploadsRoot . "/" . $cleanOld;

    if (file_exists($absoluteOld)) {
        unlink($absoluteOld);
    }
}

/* NEW UPLOAD */
if (!isset($_FILES["file"])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit;
}

/* Date folders */
$year = date("Y");
$month = date("m");
$day = date("d");

/* Correct relative path for DB */
$relativePath = 
    "sellers/$user_id/seo-settings/preview-image/$year/$month/$day/";

/* Full FS Path */
$uploadDir = __DIR__ . "/../../../../public/uploads/" . $relativePath;

/* Ensure folder exists */
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES["file"];
$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
$filename = uniqid("preview_") . "." . $ext;

$target = $uploadDir . $filename;

if (!move_uploaded_file($file["tmp_name"], $target)) {
    echo json_encode(["success" => false, "message" => "Upload failed"]);
    exit;
}

echo json_encode([
    "success" => true,
    "filename" => $relativePath . $filename
]);
