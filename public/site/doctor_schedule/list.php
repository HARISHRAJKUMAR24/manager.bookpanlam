<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

$userId = (int)($_GET['user_id'] ?? 0);

if (!$userId) {
    echo json_encode([
        "success" => false,
        "message" => "user_id required"
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        ds.id,
        ds.name,
        ds.slug,
        ds.amount,
        ds.token_limit,
        ds.doctor_image AS doctorImage,
        ds.weekly_schedule,
        ds.leave_dates,
        ds.category_id,
        ds.appointment_time_from,
        ds.appointment_time_to,

        c.name AS category_name,
        c.doctor_name,
        c.specialization,
        c.qualification,
        c.experience,
        c.reg_number,
        c.doctor_image AS cat_doctor_image,
        c.user_id

    FROM doctor_schedule ds
    LEFT JOIN categories c 
        ON c.category_id = ds.category_id 
        AND c.user_id = ds.user_id
    WHERE ds.user_id = ?
");
$stmt->execute([$userId]);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($records as &$row) {

    // Weekly schedule
    $row['weeklySchedule'] = !empty($row['weekly_schedule'])
        ? json_decode($row['weekly_schedule'], true)
        : [];

    // Leave dates
    $row['leaveDates'] = !empty($row['leave_dates'])
        ? json_decode($row['leave_dates'], true)
        : [];

    // Token limit
    $row['tokenLimit'] = $row['token_limit'] ?? null;

    // NEW: include appointment times
    $row['appointment_time_from'] = $row['appointment_time_from'] ?? null;
    $row['appointment_time_to']   = $row['appointment_time_to'] ?? null;

    // NEW: category and doctor info
    $row['category_id'] = $row['category_id'] ?? null;
    $row['doctor_name'] = $row['doctor_name'] ?? $row['name'];
    $row['specialization'] = $row['specialization'] ?? null;

    // Override doctor image if category has one
    if (!empty($row['cat_doctor_image'])) {
        $row['doctorImage'] = $row['cat_doctor_image'];
    }

    // Remove backend raw fields
    unset(
        $row['weekly_schedule'],
        $row['leave_dates'],
        $row['token_limit'],
        $row['cat_doctor_image']
    );
}

echo json_encode([
    "success" => true,
    "records" => $records
]);
