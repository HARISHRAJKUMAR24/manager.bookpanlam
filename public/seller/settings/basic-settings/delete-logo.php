<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Content-Type: application/json");

$req = json_decode(file_get_contents("php://input"), true);

if (!isset($req["path"]) || empty($req["path"])) {
    echo json_encode(["success" => false, "message" => "Missing path"]);
    exit;
}

$relativePath = $req["path"];  

$uploadsRoot = realpath(__DIR__ . "/../../../uploads");

if (!$uploadsRoot) {
    echo json_encode([
        "success" => false,
        "message" => "Uploads folder not found at: " . __DIR__ . "/../../../uploads"
    ]);
    exit;
}

$fullPath = $uploadsRoot . "/" . $relativePath;

$realFullPath = realpath($fullPath);
if (!$realFullPath || strpos($realFullPath, $uploadsRoot) !== 0) {
    echo json_encode(["success" => false, "message" => "Invalid or unsafe file path"]);
    exit;
}

if (!file_exists($realFullPath)) {
    echo json_encode(["success" => false, "message" => "File not found: " . $realFullPath]);
    exit;
}

if (unlink($realFullPath)) {
    echo json_encode(["success" => true, "message" => "File deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Unable to delete file"]);
}
