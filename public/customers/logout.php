<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===============================
   CORS
================================ */
$allowedOrigin = "http://localhost:3001";
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===============================
   DELETE AUTH COOKIES
================================ */
setcookie("customer_token", "", time() - 3600, "/", "", false, true);
setcookie("PHPSESSID", "", time() - 3600, "/", "", false, true);

echo json_encode([
    "success" => true,
    "message" => "Logged out successfully"
]);
