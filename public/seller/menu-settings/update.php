<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$name = trim($data['name'] ?? '');

if (!$id || !$name) {
    echo json_encode([
        "success" => false,
        "message" => "Menu ID and name are required"
    ]);
    exit;
}

$stmt = $pdo->prepare("UPDATE menus SET name = ? WHERE id = ?");
$ok = $stmt->execute([$name, $id]);

echo json_encode([
    "success" => $ok,
    "id" => $id,
    "name" => $name
]);
