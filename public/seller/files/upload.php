<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";   // ⭐ DB connection

$pdo = getDbConnection();

$uploadDir = "../../../uploads/employees/";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!isset($_FILES['file'])) {
    echo json_encode(["success" => false, "message" => "No file received"]);
    exit;
}

$file = $_FILES['file'];
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$filename = uniqid() . "." . $ext;

$path = $uploadDir . $filename;

// ⭐ Move uploaded file
if (move_uploaded_file($file["tmp_name"], $path)) {

    // ⭐ SAVE IN DATABASE (files table)
    $stmt = $pdo->prepare("INSERT INTO files (name, path) VALUES (?, ?)");
    $stmt->execute([$filename, "employees/" . $filename]); // path saved WITHOUT uploads/

    echo json_encode([
        "success" => true,
        "url" => "/uploads/employees/" . $filename,
        "path" => "employees/" . $filename
    ]);
    exit;
}

// ❌ Error
echo json_encode(["success" => false, "message" => "Upload failed"]);
exit;
