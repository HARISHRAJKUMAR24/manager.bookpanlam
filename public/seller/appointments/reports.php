<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ---------------- AUTH ---------------- */

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

/* ---------------- FILTER PARAMS ---------------- */

$q = $_GET["q"] ?? "";
$status = $_GET["status"] ?? "";
$paymentMethod = $_GET["paymentMethod"] ?? "";
$fromDate = $_GET["fromDate"] ?? "";
$toDate = $_GET["toDate"] ?? "";
$date = $_GET["date"] ?? "";
$doctor = $_GET["doctor"] ?? "";

/* ---------------- WHERE BUILD ---------------- */

$where = " WHERE cp.user_id = :user_id ";
$params = [":user_id" => $userId];

/* Search */
if (!empty($q)) {
    $where .= " AND (
        cp.appointment_id LIKE :search
        OR cp.customer_id LIKE :search
        OR cp.service_name LIKE :search
    )";
    $params[":search"] = "%$q%";
}

/* Status */
if (!empty($status)) {
    $where .= " AND cp.status = :status ";
    $params[":status"] = $status;
}

/* Payment Method */
if (!empty($paymentMethod)) {
    $where .= " AND cp.payment_method = :paymentMethod ";
    $params[":paymentMethod"] = $paymentMethod;
}

/* Doctor Filter */
if (!empty($doctor) && $doctor !== "all") {
$where .= " AND (
    c.doctor_name = :doctor
    OR cp.service_name = :doctor
    OR (
        cp.service_name LIKE '{%' AND
        JSON_UNQUOTE(JSON_EXTRACT(cp.service_name, '$.doctor_name')) = :doctor
    )
)";
    $params[":doctor"] = $doctor;
}

/* Date Range */
if (!empty($fromDate) && !empty($toDate)) {
    $where .= " AND cp.appointment_date BETWEEN :fromDate AND :toDate ";
    $params[":fromDate"] = $fromDate;
    $params[":toDate"] = $toDate;
}

/* Quick Date */
switch ($date) {
    case "today":
        $where .= " AND DATE(cp.appointment_date) = CURDATE() ";
        break;

    case "yesterday":
        $where .= " AND DATE(cp.appointment_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) ";
        break;

    case "this_week":
        $where .= " AND YEARWEEK(cp.appointment_date,1)=YEARWEEK(CURDATE(),1) ";
        break;

    case "this_month":
        $where .= " AND MONTH(cp.appointment_date)=MONTH(CURDATE())
                    AND YEAR(cp.appointment_date)=YEAR(CURDATE()) ";
        break;

    case "this_year":
        $where .= " AND YEAR(cp.appointment_date)=YEAR(CURDATE()) ";
        break;
}

/* ---------------- REPORT QUERY ---------------- */

$sql = "
SELECT 
    COUNT(*) as totalAppointments,
    SUM(cp.total_amount) as totalAmount,
    SUM(CASE WHEN cp.status='paid' THEN cp.total_amount ELSE 0 END) as paidAmount,
    SUM(CASE WHEN cp.status='pending' THEN cp.total_amount ELSE 0 END) as pendingAmount,
    SUM(cp.gst_amount) as gstAmount
FROM customer_payment cp
LEFT JOIN categories c 
    ON cp.service_reference_id = c.category_id
$where
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

/* Prevent null */

echo json_encode([
    "totalAppointments" => (int)($result["totalAppointments"] ?? 0),
    "totalAmount" => (float)($result["totalAmount"] ?? 0),
    "paidAmount" => (float)($result["paidAmount"] ?? 0),
    "pendingAmount" => (float)($result["pendingAmount"] ?? 0),
    "gstAmount" => (float)($result["gstAmount"] ?? 0),
]);
