<?php
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$id = $_GET["id"] ?? null;

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "id required"
    ]);
    exit;
}

/* =========================
   FETCH DOCTOR SCHEDULE
========================= */
$stmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        category_id,
        name,
        slug,
        amount,
        token_limit,
        description,
        specialization,
        qualification,
        experience,
        doctor_image,
        weekly_schedule,
        appointment_timings,  -- IMPORTANT: Get this field
        leave_dates,
        meta_title,
        meta_description,
        country,
        state,
        city,
        pincode,
        address,
        map_link,
        created_at,
        updated_at
    FROM doctor_schedule
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([":id" => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode([
        "success" => false,
        "message" => "not found"
    ]);
    exit;
}

/* =========================
   SAFE JSON DECODE
========================= */
$weeklySchedule = !empty($row["weekly_schedule"])
    ? json_decode($row["weekly_schedule"], true)
    : [];

// IMPORTANT: Decode appointment_timings
$appointmentTimings = !empty($row["appointment_timings"])
    ? json_decode($row["appointment_timings"], true)
    : [];

$leaveDates = !empty($row["leave_dates"])
    ? json_decode($row["leave_dates"], true)
    : [];

/* =========================
   NORMALIZED RESPONSE
========================= */
$data = [
    "id" => $row["id"],
    "serviceId" => $row["id"],
    "userId" => $row["user_id"],
    "categoryId" => (string)($row["category_id"] ?? ""),

    "name" => $row["name"] ?? "",
    "doctor_name" => $row["name"] ?? "",
    "slug" => $row["slug"] ?? "",
    "amount" => $row["amount"] !== null ? (string)$row["amount"] : "0",
    "token_limit" => (string)($row["token_limit"] ?? "0"),

    "description" => $row["description"] ?? "",
    "specialization" => $row["specialization"] ?? "",
    "qualification" => $row["qualification"] ?? "",
    "experience" => $row["experience"] !== null ? (string)$row["experience"] : "0",

    "doctorImage" => $row["doctor_image"] ?? "",
    "doctor_image" => $row["doctor_image"] ?? "",

    "weeklySchedule" => $weeklySchedule,
    "appointmentTimings" => $appointmentTimings,  // IMPORTANT: Include this
    "leaveDates" => $leaveDates,

    "metaTitle" => $row["meta_title"] ?? "",
    "metaDescription" => $row["meta_description"] ?? "",

    "doctorLocation" => [
        "country" => $row["country"] ?? "",
        "state" => $row["state"] ?? "",
        "city" => $row["city"] ?? "",
        "pincode" => $row["pincode"] ?? "",
        "address" => $row["address"] ?? "",
        "mapLink" => $row["map_link"] ?? ""
    ],

    "createdAt" => $row["created_at"],
    "updatedAt" => $row["updated_at"]
];

/* =========================
   DEBUG INFO
========================= */
echo json_encode([
    "success" => true,
    "data" => $data,
    "debug" => [
        "appointment_timings_raw" => $row["appointment_timings"] ?? "NULL",
        "appointment_timings_parsed" => $appointmentTimings
    ]
]);