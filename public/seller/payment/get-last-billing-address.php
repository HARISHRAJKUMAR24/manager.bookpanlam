<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID is required"
    ]);
    exit;
}

/* Fetch latest successful subscription */
$stmt = $pdo->prepare("
    SELECT
        name,
        email,
        phone,
        address_1,
        address_2,
        city,
        state,
        pin_code,
        country,
        gst_number
    FROM subscription_histories
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "No previous billing data found"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
