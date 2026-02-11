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
   FETCH APPOINTMENT WITH CUSTOMER DETAILS
======================= */
$sql = "
SELECT 
    cp.*,
    c.name AS customer_name,
    c.email AS customer_email,
    c.phone AS customer_phone,
    c.photo AS customer_photo
FROM customer_payment cp
LEFT JOIN customers c ON cp.customer_id = c.customer_id AND c.user_id = :user_id
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
   PREPARE RESPONSE WITH CUSTOMER DETAILS
======================= */
$response = [
    "success" => true,
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
    "customer_id" => $appointment['customer_id'],
    "name" => $appointment['customer_name'],  // From customers table
    "phone" => $appointment['customer_phone'], // From customers table
    "email" => $appointment['customer_email'], // From customers table
    "photo" => $appointment['customer_photo'], // From customers table
    
    // Customer object for frontend
    "customer" => [
        "name" => $appointment['customer_name'],
        "email" => $appointment['customer_email'],
        "phone" => $appointment['customer_phone'],
        "photo" => $appointment['customer_photo'],
        "customerId" => $appointment['customer_id']
    ],
    
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
    
    // Note: address, area, postalCode are not in your customers table structure
    // If you need these, you'll need to add them to the table or remove from frontend
    "address" => null,
    "area" => null,
    "postalCode" => null
];

// Add debug info if needed
if (isset($_GET['debug'])) {
    $response['debug'] = [
        'raw_service_name' => $appointment['service_name'],
        'parsed_service_data' => $serviceData,
        'doctor_name' => $doctorName,
        'specialization' => $specialization,
        'customer_data' => [
            'name' => $appointment['customer_name'],
            'email' => $appointment['customer_email'],
            'phone' => $appointment['customer_phone']
        ]
    ];
}

echo json_encode($response);