<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$input = json_decode(file_get_contents("php://input"), true);

$doctor_id = $input["doctor_id"] ?? null;
$user_id   = $input["user_id"] ?? null;
$schedule  = $input["schedule"] ?? null;

if (!$doctor_id || !$user_id || !$schedule) {
    echo json_encode([
        "success" => false,
        "message" => "doctor_id, user_id & schedule required"
    ]);
    exit;
}

try {
    foreach ($schedule as $day => $dayData) {
        $slots = $dayData["slots"] ?? [];

        $stmt = $pdo->prepare("
            SELECT id FROM doctor_schedule 
            WHERE doctor_id=? AND user_id=? AND day=?
        ");
        $stmt->execute([$doctor_id, $user_id, $day]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // UPDATE
            $update = $pdo->prepare("
                UPDATE doctor_schedule 
                SET slots=? 
                WHERE doctor_id=? AND user_id=? AND day=?
            ");
            $update->execute([
                json_encode($slots),
                $doctor_id,
                $user_id,
                $day
            ]);
        } else {
            // INSERT
            $insert = $pdo->prepare("
                INSERT INTO doctor_schedule (doctor_id, user_id, day, slots)
                VALUES (?, ?, ?, ?)
            ");
            $insert->execute([
                $doctor_id,
                $user_id,
                $day,
                json_encode($slots)
            ]);
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Doctor schedule saved successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
