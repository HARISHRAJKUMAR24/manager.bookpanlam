<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");




if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   READ INPUT
================================ */
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

$id = $input["id"] ?? null;
if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "ID required"
    ]);
    exit;
}

/* ===============================
   VALIDATE APPOINTMENT TIMES
================================ */
function convertTo24Hour($time, $period) {
    if (!$time) return null;
    
    $timeParts = explode(':', $time);
    $hours = (int)$timeParts[0];
    $minutes = $timeParts[1] ?? '00';
    
    if ($period === 'PM' && $hours < 12) {
        $hours += 12;
    } elseif ($period === 'AM' && $hours == 12) {
        $hours = 0;
    }
    
    return sprintf("%02d:%s", $hours, $minutes);
}

$appointmentFromTime = $input["appointmentTimeFromFormatted"]["time"] ?? "";
$appointmentFromPeriod = $input["appointmentTimeFromFormatted"]["period"] ?? "AM";
$appointmentToTime = $input["appointmentTimeToFormatted"]["time"] ?? "";
$appointmentToPeriod = $input["appointmentTimeToFormatted"]["period"] ?? "AM";

$appointmentTimeFrom = convertTo24Hour($appointmentFromTime, $appointmentFromPeriod);
$appointmentTimeTo = convertTo24Hour($appointmentToTime, $appointmentToPeriod);

/* ===============================
   LEAVE DATES
================================ */
$leaveDates = $input["leaveDates"] ?? [];
if (!is_array($leaveDates)) {
    $leaveDates = [];
}

/* ===============================
   AMOUNT VALIDATION
================================ */
$amountRaw = $input["amount"] ?? "";

if (is_string($amountRaw)) {
    $amountRaw = trim($amountRaw);
}

if ($amountRaw === "" || $amountRaw === null) {
    $amountValue = 0;
} else {
    $amountValue = (float)$amountRaw;
}

if (!is_numeric($amountValue) || $amountValue <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid amount"
    ]);
    exit;
}

/* ===============================
   TOKEN LIMIT
================================ */
$tokenRaw = $input["tokenLimit"] ?? null;

if ($tokenRaw === "" || $tokenRaw === null) {
    $tokenLimit = 0;
} else {
    $tokenLimit = (int)$tokenRaw;
}

if ($tokenLimit < 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid token limit"
    ]);
    exit;
}

/* ===============================
   FETCH DOCTOR DETAILS
================================ */
$categoryId = $input["categoryId"] ?? null;

$doctorDetails = null;

if (!empty($categoryId)) {
    if (is_numeric($categoryId)) {
        $sql = "SELECT * FROM categories WHERE id = ?";
    } else {
        $sql = "SELECT * FROM categories WHERE category_id = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoryId]);
    $doctorDetails = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ===============================
   SAFE VALUES
================================ */
$loc = $input["doctorLocation"] ?? [];

$doctorName     = $doctorDetails["doctor_name"] ?? "";
$specialization = $doctorDetails["specialization"] ?? "";
$qualification  = $doctorDetails["qualification"] ?? "";
$experience     = isset($doctorDetails["experience"])
    ? (int)$doctorDetails["experience"]
    : null;
$doctorImage    = $doctorDetails["doctor_image"] ?? "";


function validateAppointmentHours($from, $to) {
    if (!$from || !$to) {
        return [false, "Appointment start and end time required"];
    }

    // Convert HH:MM to minutes
    list($fh, $fm) = explode(':', $from);
    list($th, $tm) = explode(':', $to);

    $fromMin = $fh * 60 + $fm;
    $toMin   = $th * 60 + $tm;

    if ($fromMin >= $toMin) {
        return [false, "Appointment start time must be before end time"];
    }

    return [true, ""];
}


/* ===============================
   UPDATE
================================ */
$sql = "UPDATE doctor_schedule SET
    category_id = :category_id,
    name = :name,
    slug = :slug,
    amount = :amount,
    token_limit = :token_limit,
    description = :description,
    specialization = :specialization,
    qualification = :qualification,
    experience = :experience,
    doctor_image = :doctor_image,
    meta_title = :meta_title,
    meta_description = :meta_description,
    country = :country,
    state = :state,
    city = :city,
    pincode = :pincode,
    address = :address,
    map_link = :map_link,
    weekly_schedule = :weekly_schedule,
    appointment_time_from = :appointment_time_from,
    appointment_time_to = :appointment_time_to,
    leave_dates = :leave_dates,
    updated_at = NOW()
WHERE id = :id";

$stmt = $pdo->prepare($sql);

$result = $stmt->execute([
    ":id" => (int)$id,
    ":category_id" => $categoryId,
    ":name" => $doctorName,
    ":slug" => $input["slug"] ?? "",
    ":amount" => $amountValue,
    ":token_limit" => $tokenLimit,
    ":description" => $input["description"] ?? "",
    ":specialization" => $specialization,
    ":qualification" => $qualification,
    ":experience" => $experience,
    ":doctor_image" => $doctorImage,
    ":meta_title" => $input["metaTitle"] ?? "",
    ":meta_description" => $input["metaDescription"] ?? "",
    ":country" => $loc["country"] ?? "",
    ":state" => $loc["state"] ?? "",
    ":city" => $loc["city"] ?? "",
    ":pincode" => $loc["pincode"] ?? "",
    ":address" => $loc["address"] ?? "",
    ":map_link" => $loc["mapLink"] ?? "",
    ":weekly_schedule" => json_encode($input["weeklySchedule"] ?? []),
    ":appointment_time_from" => $appointmentTimeFrom,
    ":appointment_time_to" => $appointmentTimeTo,
    ":leave_dates" => json_encode($leaveDates),
]);

if ($result) {
    echo json_encode([
        "success" => true,
        "message" => "Doctor schedule updated successfully",
        "id" => $id,
        "appointment_time_from" => $appointmentTimeFrom,
        "appointment_time_to" => $appointmentTimeTo
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update doctor schedule",
        "error_info" => $stmt->errorInfo()
    ]);
}