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

if (!isset($_GET["user_id"])) {
    echo json_encode(["success" => false, "message" => "user_id missing"]);
    exit();
}

$userId = $_GET["user_id"];
$module = "departments";  // ⭐ this is key

$year = date("Y");
$month = date("m");
$day = date("d");

$finalPath = "../../../public/uploads/sellers/$userId/$module/$year/$month/$day/";

if (!is_dir($finalPath)) {
    mkdir($finalPath, 0777, true);
}

if (!isset($_FILES["file"])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit();
}

$file = $_FILES["file"];
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$filename = time() . "_" . uniqid() . "." . $ext;

$targetFile = $finalPath . $filename;

if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
    echo json_encode(["success" => false, "message" => "Upload failed"]);
    exit();
}

$relativePath = "sellers/$userId/$module/$year/$month/$day/$filename";

echo json_encode([
    "success" => true,
    "filename" => $relativePath
]);

exit();
