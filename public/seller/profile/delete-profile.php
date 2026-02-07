<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input["path"]) || empty($input["path"])) {
    echo json_encode(["success" => false, "message" => "Missing file path"]);
    exit;
}

$path = $input["path"];  

$cleanPath = str_replace("../", "/", $path);
$cleanPath = str_replace("//", "/", $cleanPath);

$relative = ltrim(str_replace("/uploads", "", $cleanPath), "/");


$uploadsRoot = realpath(__DIR__ . "/../../uploads");

if (!$uploadsRoot) {
    echo json_encode(["success" => false, "message" => "Uploads folder not found"]);
    exit;
}

$filePath = $uploadsRoot . "/" . $relative;

if (!file_exists($filePath)) {
    echo json_encode([
        "success" => false,
        "message" => "File not found: " . $filePath
    ]);
    exit;
}

if (!unlink($filePath)) {
    echo json_encode(["success" => false, "message" => "Failed to delete file"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Profile image deleted"]);
