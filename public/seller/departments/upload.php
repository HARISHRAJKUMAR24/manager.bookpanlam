<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// GET user_id
if (!isset($_GET["user_id"])) {
    echo json_encode(["success" => false, "message" => "user_id missing"]);
    exit();
}

$userId = $_GET["user_id"];

// CREATE DATE-BASED FOLDERS
$year = date("Y");
$month = date("m");
$day = date("d");

// Final Folder Path: uploads/sellers/{user_id}/departments/{YYYY}/{MM}/{DD}/
$finalPath = "../../../public/uploads/sellers/$userId/departments/$year/$month/$day/";

// Create directory if missing
if (!is_dir($finalPath)) {
    mkdir($finalPath, 0777, true);
}

// Ensure file received
if (!isset($_FILES["file"])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit();
}

$file = $_FILES["file"];
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);

// Unique filename
$filename = time() . "_" . uniqid() . "." . $ext;

$targetFile = $finalPath . $filename;

if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
    echo json_encode(["success" => false, "message" => "Upload failed"]);
    exit();
}

// This goes to DB
$relativePath = "sellers/$userId/departments/$year/$month/$day/$filename";

echo json_encode([
    "success" => true,
    "filename" => $relativePath
]);
exit();
?>