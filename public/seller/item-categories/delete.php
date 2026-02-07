<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* 🔹 READ JSON BODY */
$data = json_decode(file_get_contents("php://input"), true);
$id = $data["id"] ?? null;

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "Category ID required"
    ]);
    exit;
}

/* 🔹 AUTH */
$token = $_COOKIE["token"] ?? "";

$stmt = $pdo->prepare(
    "SELECT user_id FROM users WHERE api_token = ? LIMIT 1"
);
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* 🔹 DELETE (SELLER SAFE) */
$stmt = $pdo->prepare(
    "DELETE FROM item_categories WHERE id = ? AND user_id = ?"
);

$ok = $stmt->execute([$id, $user->user_id]);

echo json_encode([
    "success" => $ok && $stmt->rowCount() > 0
]);
exit;
