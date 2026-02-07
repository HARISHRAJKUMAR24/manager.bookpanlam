<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===============================
   CORS
================================ */
$origin = "http://localhost:3001";
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===============================
   READ RAW JSON
================================ */
$raw = file_get_contents("php://input");
error_log("RAW INPUT => " . $raw);

if (!$raw || trim($raw) === "") {
    echo json_encode([
        "success" => false,
        "message" => "Empty request body"
    ]);
    exit;
}

$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON",
        "raw" => $raw
    ]);
    exit;
}

/* ===============================
   SUCCESS (TEST MODE)
================================ */
echo json_encode([
    "success" => true,
    "message" => "Hotel settings received",
    "data" => $data
]);
exit;
