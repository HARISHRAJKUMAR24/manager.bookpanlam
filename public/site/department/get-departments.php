<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* READ seller_id (mapped to user_id in DB) */
$sellerId = (int)($_GET['seller_id'] ?? 0);

if (!$sellerId) {
    echo json_encode([
        "success" => false,
        "message" => "seller_id required"
    ]);
    exit;
}

/* FETCH ALL DEPARTMENTS */
$stmt = $pdo->prepare("SELECT * FROM departments WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$sellerId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$departments = [];

foreach ($rows as $row) {

    // Appointment Settings JSON
    $row["appointmentSettings"] = !empty($row["appointment_settings"])
        ? json_decode($row["appointment_settings"], true)
        : [];

    // Leave Dates JSON
    $row["leaveDates"] = !empty($row["leave_dates"])
        ? json_decode($row["leave_dates"], true)
        : [];

    // Appointment Times
    $row["appointmentTimeFrom"] = $row["appointment_time_from"] ?? null;
    $row["appointmentTimeTo"]   = $row["appointment_time_to"] ?? null;

    // Build services array from type_1_name … type_25_amount
    $services = [];

    for ($i = 1; $i <= 25; $i++) {
        $name = $row["type_{$i}_name"] ?? null;
        $amount = $row["type_{$i}_amount"] ?? null;
        $hsn = $row["type_{$i}_hsn"] ?? null;

        if (!empty($name)) {
            $services[] = [
                "name" => $name,
                "amount" => floatval($amount),
                "hsn" => $hsn
            ];
        }

        unset($row["type_{$i}_name"]);
        unset($row["type_{$i}_amount"]);
        unset($row["type_{$i}_hsn"]);
    }

    $row["services"] = $services;

    // Remove raw JSON columns
    unset($row["appointment_settings"]);
    unset($row["appointment_time_from"]);
    unset($row["appointment_time_to"]);
    unset($row["leave_dates"]);

    $departments[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $departments
]);
exit;
