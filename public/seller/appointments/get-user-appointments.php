<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

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
$customer_id = $_GET["customer_id"] ?? null;

$sql = "
SELECT 
    cp.id,
    cp.user_id,
    cp.customer_id,
    cp.appointment_id,
    cp.amount,
    cp.currency,
    cp.status,
    cp.created_at,
    cp.payment_id,
    cp.gst_type,
    cp.gst_percent,
    cp.gst_amount,
    cp.total_amount,
    cp.payment_method,
    cp.appointment_date,
    cp.slot_from,
    cp.slot_to,
    cp.token_count,
    cp.service_reference_id,
    cp.service_reference_type,
    cp.service_name,

    c.doctor_name,
    c.specialization,
    c.qualification,
    c.experience,
    c.reg_number,
    c.doctor_image

FROM customer_payment cp
LEFT JOIN categories c 
    ON cp.service_reference_id = c.category_id
WHERE cp.user_id = :user_id
";

if (!empty($customer_id)) {
    $sql .= " AND cp.customer_id = :customer_id";
}

$sql .= " ORDER BY cp.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);

if (!empty($customer_id)) {
    $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
}

$stmt->execute();

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "records" => $records,
    "totalRecords" => count($records),
    "totalPages" => 1
]);
