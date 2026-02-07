<?php
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$userId = $_GET["user_id"] ?? null;

if (!$userId) {
    echo json_encode([
        "success" => false,
        "message" => "User ID is required"
    ]);
    exit;
}

/* Fetch user suspension info */
$stmt = $pdo->prepare("
    SELECT 
        u.is_suspended,
        s.reason
    FROM users u
    LEFT JOIN suspend_users s ON s.user_id = u.user_id
    WHERE u.user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "is_suspended" => (int)$data["is_suspended"],
    "reason" => $data["reason"] ?? ""
]);
