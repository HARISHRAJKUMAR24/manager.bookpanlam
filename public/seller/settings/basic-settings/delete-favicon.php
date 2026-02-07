<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input["filename"]) || empty($input["filename"])) {
    echo json_encode(["success" => false, "message" => "Missing filename"]);
    exit;
}

$filename = $input["filename"]; 

$rootUploads = realpath(__DIR__ . "/../../../uploads");

if (!$rootUploads) {
    echo json_encode(["success" => false, "message" => "Uploads folder not found"]);
    exit;
}

$filePath = $rootUploads . "/" . $filename;

if (!file_exists($filePath)) {
    echo json_encode([
        "success" => false,
        "message" => "File not found: " . $filePath
    ]);
    exit;
}

if (unlink($filePath)) {
    echo json_encode(["success" => true, "message" => "File deleted"]);
} else {
    echo json_encode(["success" => false, "message" => "Unable to delete file"]);
}
