<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$employee_id = $_GET['id'] ?? null;

if (!$employee_id) {
    echo json_encode([
        "success" => false,
        "message" => "Employee ID missing"
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->execute([$employee_id]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Employee not found"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
