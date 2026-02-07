<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

/* ===============================
   INPUT
================================ */
$user_id     = $_GET['userId'] ?? null;       // seller ID
$customer_id = $_GET['customerId'] ?? null;

if (!$user_id || !$customer_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing userId or customerId"
    ]);
    exit;
}

if (empty($_FILES['files'])) {
    echo json_encode([
        "success" => false,
        "message" => "No file uploaded"
    ]);
    exit;
}

/* ===============================
   DIRECTORY — CORRECT FIX
   Make sure we save to:
   manager.bookpanlam/public/uploads/...
================================ */
$year  = date('Y');
$month = date('m');
$day   = date('d');

/**
 * dirname(__DIR__, 2) = manager.bookpanlam/public
 */
$baseDir = dirname(__DIR__, 2) . "/uploads/sellers/{$user_id}/customers/{$year}/{$month}/{$day}/";

if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

/* ===============================
   SAVE FILE
================================ */
$file = $_FILES['files'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);

$filename = "profile_" . time() . "." . $ext;
$absolutePath = $baseDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to store file"
    ]);
    exit;
}

/* ===============================
   RELATIVE PATH (DB)
================================ */
$relativePath = "sellers/{$user_id}/customers/{$year}/{$month}/{$day}/{$filename}";

/* ===============================
   RESPONSE
================================ */
echo json_encode([
    "success" => true,
    "message" => "File uploaded successfully",
    "files" => [
        ["filename" => $relativePath]
    ]
]);
