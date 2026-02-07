<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "ID required"
    ]);
    exit;
}

try {
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
            appointment_time_from,
            appointment_time_to,
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
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode([
            "success" => false,
            "message" => "Not found"
        ]);
        exit;
    }

    /* =========================
       SAFE JSON DECODE
    ========================= */
    $weeklySchedule = !empty($data['weekly_schedule'])
        ? json_decode($data['weekly_schedule'], true)
        : [];

    $leaveDates = !empty($data['leave_dates'])
        ? json_decode($data['leave_dates'], true)
        : [];

    /* =========================
       FORMAT TIME FOR DISPLAY
    ========================= */
    $appointmentTimeFrom = $data['appointment_time_from'] ?? null;
    $appointmentTimeTo = $data['appointment_time_to'] ?? null;
    
    // Format time for display (convert 24h to 12h)
    function formatTimeForDisplay($time24) {
        if (!$time24) return ["display" => "", "time" => "", "period" => "AM"];
        
        $time = explode(':', $time24);
        $hours = (int)$time[0];
        $minutes = $time[1] ?? '00';
        $period = $hours >= 12 ? 'PM' : 'AM';
        
        if ($hours > 12) {
            $hours -= 12;
        } elseif ($hours === 0) {
            $hours = 12;
        }
        
        return [
            "display" => sprintf("%02d:%s %s", $hours, $minutes, $period),
            "time" => sprintf("%02d:%s", $hours, $minutes),
            "period" => $period
        ];
    }
    
    $fromDisplay = formatTimeForDisplay($appointmentTimeFrom);
    $toDisplay = formatTimeForDisplay($appointmentTimeTo);

    /* =========================
       NORMALIZED RESPONSE
    ========================= */
    echo json_encode([
        "success" => true,
        "data" => [
            "id" => $data['id'],
            "serviceId" => $data['id'],
            "userId" => $data['user_id'],
            "categoryId" => (string)($data['category_id'] ?? ""),

            "name" => $data['name'] ?? "",
            "doctor_name" => $data['name'] ?? "",
            "slug" => $data['slug'] ?? "",
            "amount" => $data['amount'] !== null ? (string)$data['amount'] : "0",
            "token_limit" => (string)($data['token_limit'] ?? "0"),

            "description" => $data['description'] ?? "",
            "specialization" => $data['specialization'] ?? "",
            "qualification" => $data['qualification'] ?? "",
            "experience" => $data['experience'] !== null ? (string)$data['experience'] : "0",

            "doctorImage" => $data['doctor_image'] ?? "",
            "doctor_image" => $data['doctor_image'] ?? "",

            "weeklySchedule" => $weeklySchedule,
            "appointmentTimeFrom" => $appointmentTimeFrom,
            "appointmentTimeTo" => $appointmentTimeTo,
            "appointmentTimeFromDisplay" => $fromDisplay["display"],
            "appointmentTimeToDisplay" => $toDisplay["display"],
            "appointmentTimeFromFormatted" => [
                "time" => $fromDisplay["time"],
                "period" => $fromDisplay["period"]
            ],
            "appointmentTimeToFormatted" => [
                "time" => $toDisplay["time"],
                "period" => $toDisplay["period"]
            ],
            "leaveDates" => $leaveDates,

            "metaTitle" => $data['meta_title'] ?? "",
            "metaDescription" => $data['meta_description'] ?? "",

            "doctorLocation" => [
                "country" => $data['country'] ?? "",
                "state" => $data['state'] ?? "",
                "city" => $data['city'] ?? "",
                "pincode" => $data['pincode'] ?? "",
                "address" => $data['address'] ?? "",
                "mapLink" => $data['map_link'] ?? ""
            ],

            "createdAt" => $data['created_at'],
            "updatedAt" => $data['updated_at']
        ]
    ]);

} catch (Exception $e) {
    error_log("GET.PHP ERROR: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Error fetching doctor schedule"
    ]);
}