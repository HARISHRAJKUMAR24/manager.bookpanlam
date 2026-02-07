<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ----------------------------------------------------
   READ JSON BODY
-----------------------------------------------------*/
$raw = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];

/* ----------------------------------------------------
   AUTH
-----------------------------------------------------*/
$token = $data["token"] ?? ($_COOKIE["token"] ?? "");

if (!$token) {
    echo json_encode(["success" => false, "message" => "Missing token"]);
    exit();
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit();
}

$user_id = $user["user_id"];

/* ----------------------------------------------------
   INPUT VALIDATION
-----------------------------------------------------*/
$department_id = trim($data["department_id"] ?? "");
$appointment_settings = $data["appointment_settings"] ?? null;
$leave_dates = $data["leave_dates"] ?? [];
$appointment_time_from = trim($data["appointment_time_from"] ?? "");
$appointment_time_to = trim($data["appointment_time_to"] ?? "");

if (!$department_id) {
    echo json_encode(["success" => false, "message" => "Department ID required"]);
    exit();
}

/* ----------------------------------------------------
   VALIDATE APPOINTMENT SETTINGS
-----------------------------------------------------*/
if (!$appointment_settings) {
    echo json_encode(["success" => false, "message" => "Appointment settings required"]);
    exit();
}


// Convert empty strings to null for database
$appointment_time_from = $appointment_time_from ?: null;
$appointment_time_to = $appointment_time_to ?: null;

/* ----------------------------------------------------
   NORMALIZE APPOINTMENT SETTINGS (WITH BATCH_ID)
-----------------------------------------------------*/
$days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
$final_settings = [];

foreach ($days as $day) {
    $dayData = $appointment_settings[$day] ?? null;
    
    if (!$dayData) {
        $final_settings[$day] = [
            "enabled" => false,
            "slots" => []
        ];
        continue;
    }

    $final_settings[$day] = [
        "enabled" => (bool)($dayData["enabled"] ?? false),
        "slots" => []
    ];

    if (!empty($dayData["slots"]) && is_array($dayData["slots"])) {
        foreach ($dayData["slots"] as $index => $slot) {
            $dayIndex = array_search($day, $days);
            $batch_id = $slot["batch_id"] ?? $dayIndex . ":" . $index;
            
            $final_settings[$day]["slots"][] = [
                "batch_id" => $batch_id,
                "from" => trim($slot["from"] ?? ""),
                "to" => trim($slot["to"] ?? ""),
                "breakFrom" => trim($slot["breakFrom"] ?? ""),
                "breakTo" => trim($slot["breakTo"] ?? ""),
                "token" => intval($slot["token"] ?? 0)
            ];
        }
    }
}

/* ----------------------------------------------------
   VALIDATE AND FORMAT LEAVE DATES
-----------------------------------------------------*/
$valid_leave_dates = [];

if (is_string($leave_dates) && $leave_dates !== '') {
    $leave_dates = json_decode($leave_dates, true);
}

if (is_array($leave_dates)) {
    foreach ($leave_dates as $date) {
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dateObj = DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObj && $dateObj->format('Y-m-d') === $date) {
                $valid_leave_dates[] = $date;
            }
        }
    }
}

$valid_leave_dates = array_unique($valid_leave_dates);
sort($valid_leave_dates);

/* ----------------------------------------------------
   CHECK IF DEPARTMENT EXISTS AND BELONGS TO USER
-----------------------------------------------------*/
$stmt = $pdo->prepare("
    SELECT id FROM departments 
    WHERE department_id = ? AND user_id = ?
");
$stmt->execute([$department_id, $user_id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    echo json_encode([
        "success" => false, 
        "message" => "Department not found or unauthorized"
    ]);
    exit();
}

/* ----------------------------------------------------
   UPDATE DATABASE
-----------------------------------------------------*/
try {
    $appointment_json = json_encode($final_settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $leave_dates_json = json_encode($valid_leave_dates, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    $stmt = $pdo->prepare("
        UPDATE departments
        SET appointment_settings = ?, 
            leave_dates = ?, 
            appointment_time_from = ?, 
            appointment_time_to = ?,
            updated_at = NOW(3)
        WHERE department_id = ? AND user_id = ?
    ");

    $success = $stmt->execute([
        $appointment_json,
        $leave_dates_json,
        $appointment_time_from,
        $appointment_time_to,
        $department_id,
        $user_id
    ]);

    if (!$success) {
        throw new Exception("Database update failed");
    }

    /* ----------------------------------------------------
       SUCCESS RESPONSE
    -----------------------------------------------------*/
    echo json_encode([
        "success" => true,
        "message" => "Department appointment settings saved successfully",
        "appointment_settings" => $final_settings,
        "leave_dates" => $valid_leave_dates,
        "appointment_time_from" => $appointment_time_from,
        "appointment_time_to" => $appointment_time_to,
        "rows_affected" => $stmt->rowCount()
    ]);

} catch (Exception $e) {
    error_log("Error updating department appointment settings: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Failed to save appointment settings: " . $e->getMessage()
    ]);
}

exit();
?>