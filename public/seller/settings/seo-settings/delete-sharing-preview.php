<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$filename = $data["filename"] ?? "";

if (!$filename) {
    echo json_encode(["success" => false, "message" => "Missing filename"]);
    exit;
}

$uploadsRoot = realpath(__DIR__ . "/../../../../public/uploads");
$cleanPath = ltrim($filename, "/");
$absolutePath = $uploadsRoot . "/" . $cleanPath;

if (file_exists($absolutePath)) {
    unlink($absolutePath);
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "File not found"]);
}
