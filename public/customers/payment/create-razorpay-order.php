<?php
// manager.bookpanlam/public/customers/payment/create-razorpay-order.php

/* -------------------------------
   CORS SETTINGS
-------------------------------- */
$allowedOrigins = [
    "http://localhost:3000",
    "http://localhost:3001",
    "http://localhost"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

/* -------------------------------
   LOAD DEPENDENCIES
-------------------------------- */
require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

/* -------------------------------
   READ JSON INPUT
-------------------------------- */
$input = json_decode(file_get_contents("php://input"), true);

$required = ["amount", "currency", "user_id", "customer_id"];

foreach ($required as $field) {
    if (!isset($input[$field]) || $input[$field] === "") {
        echo json_encode([
            "success" => false,
            "message" => "Missing required field: $field"
        ]);
        exit;
    }
}

$amount          = floatval($input["amount"]) * 100;  // Razorpay uses paise
$currency        = $input["currency"];
$user_id         = intval($input["user_id"]);
$customer_id     = intval($input["customer_id"]);

// ⭐ Get service information from input
$service_type = $input["service_reference_type"] ?? ($input["service_type"] ?? 'category');
$category_id = $input["category_id"] ?? $input["service_reference_id"] ?? null;
$department_id = $input["department_id"] ?? null;
$service_name = $input["service_name"] ?? '';

// ⭐ NEW: Get services_json if available (for department bookings with multiple services)
$services_json = $input["services_json"] ?? null;

$db = getDbConnection();

// Use the new generateAppointmentId function
$appointment_id = generateAppointmentId($user_id, $db);

$customer_email  = $input["customer_email"] ?? "";
$customer_phone  = $input["customer_phone"] ?? "";

$receipt = "receipt_" . $customer_id . "_" . time();

/* -------------------------------
   ⭐ GET SERVICE INFORMATION FOR JSON STORAGE - UPDATED FOR DEPARTMENT
-------------------------------- */
$serviceInfo = [];

// ⭐ If services_json is provided, use it to create service_name_json
if ($services_json) {
    // Prepare service_name_json based on services_json
    if (is_string($services_json)) {
        $servicesData = json_decode($services_json, true);
    } else {
        $servicesData = $services_json;
    }

    // Create proper service_name_json
    $service_name_json = json_encode($servicesData);

    // Determine reference_id based on service type
    if ($service_type === 'department') {
        $reference_id = $department_id ?? $category_id;
        $reference_type = 'department_id';

        // If it's a primary ID, try to get department_id from database
        if ($reference_id && !strpos($reference_id, 'DEPT_') === 0) {
            $stmt = $db->prepare("SELECT department_id FROM departments WHERE id = ? AND user_id = ?");
            $stmt->execute([$reference_id, $user_id]);
            $dept = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dept && $dept['department_id']) {
                $reference_id = $dept['department_id'];
            }
        }
    } else {
        $reference_id = $category_id;
        $reference_type = 'category_id';
    }

    $serviceInfo = [
        "success" => true,
        "reference_id" => $reference_id,
        "reference_type" => $reference_type,
        "service_name_json" => $service_name_json,
        "service_name_display" => $servicesData['department_name'] ?? $servicesData['service_name'] ?? $service_name
    ];
} else {
    // Fallback to original function if no services_json
    $serviceInfo = getServiceInformation($db, $user_id, $service_type, $category_id, $service_name);
}

// Debug log
error_log("Razorpay Create - Service Info: " . json_encode($serviceInfo));

if (!$serviceInfo || !isset($serviceInfo['success']) || !$serviceInfo['success']) {
    error_log("Service info error: " . ($serviceInfo['message'] ?? 'Unknown error'));

    // Fallback service info
    $serviceInfo = [
        "reference_id" => $department_id ?? $category_id ?? 'GENERIC_' . $user_id,
        "reference_type" => ($service_type === 'department') ? 'department_id' : 'category_id',
        "service_name_json" => json_encode([
            "type" => $service_type === 'department' ? "department" : "generic",
            "service_name" => $service_name ?: "Service Booking",
            "service_type" => "Razorpay Payment"
        ]),
        "service_name_display" => $service_name ?: "Service Booking"
    ];
}

/* -------------------------------
   GET RAZORPAY KEYS
-------------------------------- */
$stmt = $db->prepare("SELECT razorpay_key_id, razorpay_secret_key 
                      FROM site_settings WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings || empty($settings["razorpay_key_id"]) || empty($settings["razorpay_secret_key"])) {
    echo json_encode([
        "success" => false,
        "message" => "Razorpay keys missing in site_settings"
    ]);
    exit;
}

$key_id     = $settings["razorpay_key_id"];
$key_secret = $settings["razorpay_secret_key"];

/* -------------------------------
   CREATE RAZORPAY ORDER
-------------------------------- */
$orderData = [
    "amount" => $amount,
    "currency" => $currency,
    "receipt" => $receipt,
    "payment_capture" => 1,
    "notes" => [
        "user_id"        => $user_id,
        "customer_id"    => $customer_id,
        "appointment_id" => $appointment_id,
        "email"          => $customer_email,
        "phone"          => $customer_phone,
        "service_reference" => $serviceInfo['reference_id'],
        "service_name"   => $serviceInfo['service_name_display'],
        "service_type"   => $service_type,
        "services_json"  => $services_json ? 'yes' : 'no'
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

/* -------------------------------
   SUCCESS
-------------------------------- */
if ($httpCode === 200) {

    $order = json_decode($response, true);

    // ⭐ Store Razorpay order_id into payment_id field
    $razorpay_order_id = $order["id"];

    // Debug: Check what we're inserting
    error_log("Inserting Razorpay order with service_name_json: " . $serviceInfo['service_name_json']);

    // ⭐ INSERT WITH SERVICE INFORMATION (JSON format) - UPDATED WITH ALL FIELDS
    $ins = $db->prepare("
        INSERT INTO customer_payment 
        (user_id, customer_id, appointment_id, payment_id, receipt, amount, currency, 
         gst_type, gst_percent, gst_amount, total_amount, status, payment_method,
         service_reference_id, service_reference_type, service_name,
         appointment_date, slot_from, slot_to, token_count, batch_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?,
                ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Prepare values for insertion
    $insertValues = [
        $user_id,
        $customer_id,
        $appointment_id,
        $razorpay_order_id,
        $receipt,
        $input["amount"] ?? 0,
        $currency,
        $input["gst_type"] ?? "",
        $input["gst_percent"] ?? 0,
        $input["gst_amount"] ?? 0,
        $input["total_amount"] ?? 0,
        'razorpay',
        $serviceInfo['reference_id'],           // DEPT_xxx or CAT_xxx
        $serviceInfo['reference_type'],         // department_id or category_id
        $serviceInfo['service_name_json'],      // ⭐ JSON format
        $input["appointment_date"] ?? null,
        $input["slot_from"] ?? null,
        $input["slot_to"] ?? null,
        $input["token_count"] ?? 1,
        $input["batch_id"] ?? null
    ];

    // Debug log insertion values
    error_log("Insert values: " . json_encode($insertValues));

    $ins->execute($insertValues);

    // Check if insert was successful
    if (!$ins) {
        $errorInfo = $db->errorInfo();
        error_log("Database insert error: " . json_encode($errorInfo));

        echo json_encode([
            "success" => false,
            "message" => "Failed to store payment record: " . ($errorInfo[2] ?? 'Unknown error')
        ]);
        exit;
    }

    $payment_id = $db->lastInsertId();

    // Verify the record was created
    $checkStmt = $db->prepare("SELECT id, service_name FROM customer_payment WHERE id = ?");
    $checkStmt->execute([$payment_id]);
    $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

    error_log("Record created - ID: " . $payment_id . ", service_name: " . ($checkResult['service_name'] ?? 'NULL'));

    // Parse service name for response
    $serviceDisplay = $serviceInfo['service_name_display'];
    if ($serviceInfo['service_name_json']) {
        try {
            $serviceJson = json_decode($serviceInfo['service_name_json'], true);
            if (isset($serviceJson['department_name'])) {
                $serviceDisplay = $serviceJson['department_name'];
            } elseif (isset($serviceJson['doctor_name'])) {
                $serviceDisplay = $serviceJson['doctor_name'];
            } elseif (isset($serviceJson['service_name'])) {
                $serviceDisplay = $serviceJson['service_name'];
            }
        } catch (Exception $e) {
            error_log("Error parsing service JSON: " . $e->getMessage());
        }
    }

    echo json_encode([
        "success" => true,
        "order"   => $order,
        "receipt" => $receipt,
        "appointment_id" => $appointment_id,
        "payment_id" => $payment_id,
        "service_info" => [
            "reference_id" => $serviceInfo['reference_id'],
            "reference_type" => $serviceInfo['reference_type'],
            "display_name" => $serviceDisplay,
            "service_type" => $service_type,
            "json_stored" => $serviceInfo['service_name_json'] ? true : false,
            "has_services_json" => $services_json ? true : false
        ]
    ]);
    exit;
}

/* -------------------------------
   FAILURE
-------------------------------- */
$errorResponse = json_decode($response, true);

echo json_encode([
    "success" => false,
    "message" => $errorResponse["error"]["description"] ?? "Order creation failed",
    "http_code" => $httpCode,
    "response" => $response
]);
exit;
