<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

$sellerId = $_GET['seller_id'] ?? '';
$module   = $_GET['module'] ?? '';

if (!$sellerId || !$module) {
    echo json_encode(["success" => false, "message" => "seller_id and module required"]);
    exit();
}

if (!isset($_FILES['file'])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit();
}

$year  = date("Y");
$month = date("m");
$day   = date("d");

$uploadDir = "../../../uploads/seller_$sellerId/$year/$month/$day/$module/";

// Make folders
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$filename = uniqid($module . "_") . "." . $ext;

$filepath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $filepath)) {

    // Path to store in DB (relative)
    $relativePath = "seller_$sellerId/$year/$month/$day/$module/$filename";

    // Full URL for display
    $fileUrl = "http://localhost/manager.bookpanlam/public/uploads/$relativePath";

    echo json_encode([
        "success" => true,
        "filename" => $filename,
        "url" => $fileUrl,
        "path" => $relativePath
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Upload failed"]);
}
