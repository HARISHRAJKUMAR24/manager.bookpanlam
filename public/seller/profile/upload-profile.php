<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($userId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid user ID"
    ]);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode([
        "success" => false,
        "message" => "No file uploaded"
    ]);
    exit;
}

$year  = date("Y");
$month = date("m");
$day   = date("d");

$basePath = __DIR__ . "/../../../public/uploads/sellers/$userId/profile/$year/$month/$day/";

if (!is_dir($basePath)) {
    mkdir($basePath, 0777, true);
}

$originalName = basename($_FILES["file"]["name"]);
$cleanName = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $originalName);
$filename = "profile_" . uniqid() . "_" . $cleanName;

$targetFile = $basePath . $filename;

if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    echo json_encode([
        "success" => false,
        "message" => "Upload failed"
    ]);
    exit;
}
$imagePath = "../uploads/sellers/$userId/profile/$year/$month/$day/$filename";

$sql = "UPDATE users SET image = :image WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);

$success = $stmt->execute([
    ":image"   => $imagePath,
    ":user_id" => $userId
]);

if (!$success) {
    echo json_encode([
        "success" => false,
        "message" => "Image uploaded but DB update failed"
    ]);
    exit;
}

echo json_encode([
    "success"  => true,
    "image"    => $imagePath
]);
