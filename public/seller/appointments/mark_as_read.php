<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);
$id = $data["appointment_id"] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "appointment_id missing"]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE appointment_notifications 
    SET seen = 1 
    WHERE appointment_id = ?
");
$stmt->execute([$id]);

echo json_encode(["success" => true]);
