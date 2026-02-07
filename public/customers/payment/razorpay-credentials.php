<?php

// --- CORS ---
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

$sql = "SELECT razorpay_key_id, razorpay_secret_key 
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

if (empty($row['razorpay_key_id']) || empty($row['razorpay_secret_key'])) {
    echo json_encode([
        "success" => false,
        "message" => "Razorpay keys missing in site_settings"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "razorpay_key_id" => $row['razorpay_key_id'],
    "razorpay_key_secret" => $row['razorpay_secret_key']
]);
