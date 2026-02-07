<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* =====================
   AUTH
===================== */
$token = $_COOKIE['token'] ?? null;

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT user_id FROM users WHERE api_token = ? LIMIT 1"
);
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$userId = (int)$user['user_id'];

/* =====================
   INPUT
===================== */
$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "ID required"
    ]);
    exit;
}

/* =====================
   DELETE
===================== */
$stmt = $pdo->prepare(
    "DELETE FROM doctor_schedule WHERE id = ? AND user_id = ?"
);

$success = $stmt->execute([$id, $userId]);

echo json_encode([
    "success" => $success,
    "message" => $success
        ? "Doctor deleted successfully"
        : "Delete failed"
]);
