<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// Token from cookies
$token = $_COOKIE["token"] ?? null;

if (!$token) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Validate user
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$userId = $user["user_id"];

// Fetch latest 10 appointments + notification state
$sql = "
SELECT 
    cp.appointment_id,
    cp.customer_id,
    cp.amount,
    cp.currency,
    cp.status,
    cp.created_at,
    cp.appointment_date,
    cp.slot_from,
    cp.slot_to,
    cp.service_name,
    cp.service_reference_type,
    cp.payment_method,

    c.doctor_name,
    c.specialization,
    c.doctor_image,

    COALESCE(n.seen, 0) AS seen

FROM customer_payment cp
LEFT JOIN categories c 
       ON cp.service_reference_id = c.category_id
LEFT JOIN appointment_notifications n
       ON n.appointment_id = cp.appointment_id 
       AND n.user_id = :user_id

WHERE cp.user_id = :user_id
ORDER BY cp.created_at DESC
LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute(["user_id" => $userId]);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return JSON
echo json_encode([
    "success" => true,
    "records" => $records
]);
exit;
