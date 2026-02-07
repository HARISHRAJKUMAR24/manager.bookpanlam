<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/config.php";
require_once "../../src/database.php";

$pdo = getDbConnection();

// Get token from headers or cookies
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';
$token = '';

if (str_starts_with($auth, 'Bearer ')) {
    $token = substr($auth, 7);
} elseif (isset($_COOKIE['token'])) {
    $token = $_COOKIE['token'];
}

// Clear API token in database if token exists (IMMEDIATE)
if ($token) {
    $stmt = $pdo->prepare("UPDATE users SET api_token = NULL WHERE api_token = ?");
    $stmt->execute([$token]);
}

// Clear cookies IMMEDIATELY (expire 1 second ago)
setcookie("token", "", time() - 1, "/", "", false, true);
setcookie("user_id", "", time() - 1, "/", "", false, false);
setcookie("user_data", "", time() - 1, "/", "", false, false);

// Also send header to clear cookies
header_remove("Set-Cookie");

echo json_encode([
    "success" => true,
    "message" => "Logged out successfully"
]);
exit;