<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Origin, Accept");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* =======================
   AUTH BY TOKEN
======================= */
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

/* =======================
   GET APPOINTMENT ID FROM URL PARAMETER
======================= */
$appointmentId = $_GET['id'] ?? null;

if (!$appointmentId) {
    echo json_encode(["success" => false, "message" => "Appointment ID is required"]);
    exit;
}

/* =======================
   FETCH APPOINTMENT WITH CUSTOMER DETAILS - FIXED JOIN
======================= */
$sql = "
SELECT 
    cp.*,
    c.name AS customer_name,
    c.email AS customer_email,
    c.phone AS customer_phone,
    c.photo AS customer_photo,
    c.customer_id AS customer_customer_id,
    c.id AS customer_db_id
FROM customer_payment cp
LEFT JOIN customers c ON cp.customer_id = c.customer_id AND cp.user_id = c.user_id
WHERE cp.appointment_id = :appointment_id 
AND cp.user_id = :user_id
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":appointment_id", $appointmentId, PDO::PARAM_STR);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    echo json_encode(["success" => false, "message" => "Appointment not found"]);
    exit;
}

/* =======================
   DEBUG: Check what data we got
======================= */
error_log("DEBUG Appointment Data:");
error_log("Customer ID from cp: " . ($appointment['customer_id'] ?? 'NULL'));
error_log("Customer Name: " . ($appointment['customer_name'] ?? 'NULL'));
error_log("Customer Phone: " . ($appointment['customer_phone'] ?? 'NULL'));

/* =======================
   PARSE SERVICE NAME (IT'S JSON)
======================= */
$serviceName = $appointment['service_name'];
$serviceData = null;
$doctorName = null;
$specialization = null;
$serviceType = null;

if ($serviceName && strpos($serviceName, '{') === 0) {
    // It's JSON, parse it
    $serviceData = json_decode($serviceName, true);
    $doctorName = $serviceData['doctor_name'] ?? null;
    $specialization = $serviceData['specialization'] ?? null;
    $serviceType = $serviceData['service_type'] ?? $serviceData['type'] ?? null;
    $serviceName = $doctorName ?? $serviceData['service_name'] ?? $serviceName;
} else {
    // It's a plain string
    $doctorName = $serviceName;
    $serviceName = $serviceName;
}

/* =======================
   PREPARE RESPONSE WITH CUSTOMER INFO
======================= */
$response = [
    "success" => true,
    
    // Appointment Info
    "appointmentId" => $appointment['appointment_id'],
    "appointment_id" => $appointment['appointment_id'],
    
    // Date and Time
    "date" => $appointment['appointment_date'],
    "appointment_date" => $appointment['appointment_date'],
    "time" => $appointment['slot_from'],
    "slot_from" => $appointment['slot_from'],
    "slot_to" => $appointment['slot_to'],
    
    // Status
    "status" => $appointment['status'],
    "paymentStatus" => $appointment['status'],
    
    // Payment Information
    "paymentMethod" => $appointment['payment_method'],
    "payment_method" => $appointment['payment_method'],
    "amount" => $appointment['amount'],
    "total_amount" => $appointment['total_amount'],
    "currency" => $appointment['currency'] ?: 'INR',
    "paymentId" => $appointment['payment_id'],
    "payment_id" => $appointment['payment_id'],
    "gstType" => $appointment['gst_type'],
    "gst_type" => $appointment['gst_type'],
    "gstPercentage" => !empty($appointment['gst_percent']) ? (float)$appointment['gst_percent'] : 0,
    "gst_percent" => !empty($appointment['gst_percent']) ? (float)$appointment['gst_percent'] : 0,
    "gst_amount" => $appointment['gst_amount'] ?? 0,
    
    // Customer Information (from customers table)
    "customer_id" => $appointment['customer_id'], // This is 951272
    "customer_db_id" => $appointment['customer_db_id'], // This is the auto-increment id
    "name" => $appointment['customer_name'] ?? 'N/A',
    "phone" => $appointment['customer_phone'] ?? 'N/A',
    "email" => $appointment['customer_email'] ?? 'N/A',
    "photo" => $appointment['customer_photo'] ?? null,
    
    "customer" => $appointment['customer_name'] ? [
        "id" => $appointment['customer_db_id'], // Use the auto-increment id
        "customerId" => $appointment['customer_id'], // This is 951272
        "name" => $appointment['customer_name'] ?? 'N/A',
        "phone" => $appointment['customer_phone'] ?? 'N/A',
        "email" => $appointment['customer_email'] ?? 'N/A',
        "photo" => $appointment['customer_photo'] ?? null
    ] : null,
    
    // Service Information (parsed from service_name JSON)
    "service_name" => $serviceName,
    "service" => [
        "id" => $appointment['service_reference_id'],
        "name" => $serviceName,
        "type" => $appointment['service_reference_type'],
        "doctorName" => $doctorName,
        "specialization" => $specialization,
        "serviceType" => $serviceType,
        "rawData" => $serviceData
    ],
    "service_reference_id" => $appointment['service_reference_id'],
    "service_reference_type" => $appointment['service_reference_type'],
    
    // Additional Information
    "createdAt" => $appointment['created_at'],
    "created_at" => $appointment['created_at'],
    "receipt" => $appointment['receipt'],
    "tokenCount" => !empty($appointment['token_count']) ? (int)$appointment['token_count'] : 1,
    "token_count" => !empty($appointment['token_count']) ? (int)$appointment['token_count'] : 1,
    "batchId" => $appointment['batch_id'],
    "batch_id" => $appointment['batch_id'],
    "signature" => $appointment['signature'],
    "remark" => "",
    
    // These will be null (not in your database structure)
    "address" => 'N/A',
    "area" => 'N/A',
    "postalCode" => 'N/A'
];

// Add debug info
$response['debug'] = [
    'customer_id_in_cp' => $appointment['customer_id'],
    'customer_name_found' => $appointment['customer_name'] ?? 'NOT FOUND',
    'join_condition' => 'cp.customer_id = c.customer_id AND cp.user_id = c.user_id'
];

echo json_encode($response);