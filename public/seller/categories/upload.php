<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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

// date folders
$year = date("Y");
$month = date("m");
$day = date("d");

// final upload folder
$uploadPath = "../../../public/uploads/sellers/$userId/doctors/$year/$month/$day/";

if (!is_dir($uploadPath)) {
    mkdir($uploadPath, 0777, true);
}

if (!isset($_FILES["file"])) {
    echo json_encode(["success" => false, "message" => "no file uploaded"]);
    exit();
}

$file = $_FILES["file"];
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);

$filename = time() . "_" . uniqid() . "." . $ext;

if (!move_uploaded_file($file["tmp_name"], $uploadPath . $filename)) {
    echo json_encode(["success" => false, "message" => "upload failed"]);
    exit();
}

// value stored in DB
$relativePath = "sellers/$userId/doctors/$year/$month/$day/$filename";

echo json_encode([
    "success" => true,
    "filename" => $relativePath
]);

exit();
