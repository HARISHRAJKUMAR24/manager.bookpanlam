<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$doctor_id = $_GET["doctor_id"] ?? null;
$user_id   = $_GET["user_id"] ?? null;

if (!$doctor_id || !$user_id) {
    echo json_encode(["success" => false, "message" => "doctor_id & user_id required"]);
    exit;
}

$stmt = $pdo->prepare("SELECT day, slots FROM doctor_schedule WHERE doctor_id=? AND user_id=?");
$stmt->execute([$doctor_id, $user_id]);

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$response = [];
foreach ($result as $row) {
    $response[$row["day"]] = [
        "enabled" => true,
        "slots" => json_decode($row["slots"], true)
    ];
}

echo json_encode([
    "success" => true,
    "data" => $response
]);
