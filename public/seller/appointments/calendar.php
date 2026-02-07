<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ---------------------------
   AUTH USING TOKEN COOKIE
---------------------------- */
$token = $_COOKIE["token"] ?? null;

if (!$token) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$userId = $user["user_id"];

/* ---------------------------
   FETCH ALL APPOINTMENTS + DOCTOR DETAILS
---------------------------- */

$sql = "
SELECT 
    cp.id,
    cp.customer_id,
    cp.appointment_id,
    cp.appointment_date,
    cp.slot_from,
    cp.slot_to,
    cp.status,
    cp.total_amount,
    cp.service_name,

    c.doctor_name,
    c.doctor_image,
    c.specialization,
    c.qualification,

    cp.service_reference_id

FROM customer_payment cp
LEFT JOIN categories c 
    ON cp.service_reference_id = c.category_id
WHERE cp.user_id = :user_id
ORDER BY cp.appointment_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "records" => $records
]);
