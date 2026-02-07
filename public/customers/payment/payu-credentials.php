<?php
// manager.bookpanlam/public/customers/payment/payu-credentials.php

$allowedOrigins = [
    "http://localhost:3000",
    "http://localhost:3001",
    "http://localhost"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$user_id = $_GET['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "user_id missing"
    ]);
    exit;
}

// Get PayU credentials from site_settings for the specific user
$sql = "SELECT payu_api_key, payu_salt 
        FROM site_settings 
        WHERE user_id = :uid 
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['uid' => $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode([
        "success" => false,
        "message" => "Site settings not found for this user"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "payu_api_key" => $row['payu_api_key'] ?? "",
    "payu_salt" => $row['payu_salt'] ?? "",
    "payu_endpoint" => "https://test.payu.in/_payment"
]);
?>