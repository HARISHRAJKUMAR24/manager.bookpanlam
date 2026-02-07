<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$oldImage = $data["old_image"] ?? "";

if (!$oldImage) {
    echo json_encode([
        "success" => false,
        "message" => "old_image missing"
    ]);
    exit;
}

/*
    old_image format (example):
    /uploads/sellers/12/employees/2026/02/02/emp_33a9df922b.png
*/

$fullPath = dirname(__DIR__, 3) . "/public" . $oldImage;

if (file_exists($fullPath)) {
    unlink($fullPath);
    echo json_encode(["success" => true, "message" => "Image deleted"]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Image not found on server"
    ]);
}
exit;
