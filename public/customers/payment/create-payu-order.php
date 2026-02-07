<?php
// manager.bookpanlam/public/customers/payment/create-payu-order.php

header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$pdo = getDbConnection();
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

try {
    $user_id       = intval($data['user_id']);
    $customer_id   = intval($data['customer_id']);
    $amount        = floatval($data['total_amount']);
    $name          = trim($data['customer_name'] ?? "Customer");
    $email         = trim($data['customer_email'] ?? "");
    $phone         = trim($data['customer_phone'] ?? "");
    
    // Extract appointment details
    $appointment_date = $data['appointment_date'] ?? null;
    $slot_from        = $data['slot_from'] ?? null;
    $slot_to          = $data['slot_to'] ?? null;
    $token_count      = intval($data['token_count'] ?? 1);
    
    // Extract service information
    $service_type     = $data['service_type'] ?? 'category';
    $category_id      = $data['category_id'] ?? null;
    $service_name     = $data['service_name'] ?? '';
    
    // ⭐ CRITICAL: Get services_json from input (for department bookings)
    $services_json    = $data['services_json'] ?? null;
    
    // Extract batch_id
    $batch_id         = $data['batch_id'] ?? null;
    
    // Extract GST Details
    $gst_type        = $data['gst_type'] ?? '';
    $gst_percent     = floatval($data['gst_percent'] ?? 0);
    $gst_amount      = floatval($data['gst_amount'] ?? 0);
    $sub_total       = floatval($data['amount'] ?? $amount);

    $appointment_id = generateAppointmentId($user_id, $pdo);
    $receipt = "receipt_" . $customer_id . "_" . time();
    
    // ⭐ GET SERVICE INFORMATION FOR JSON STORAGE - SIMILAR TO RAZORPAY
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
            $reference_id = $data['department_id'] ?? $category_id;
            $reference_type = 'department_id';

            // If it's a primary ID, try to get department_id from database
            if ($reference_id && !strpos($reference_id, 'DEPT_') === 0) {
                $stmt = $pdo->prepare("SELECT department_id FROM departments WHERE id = ? AND user_id = ?");
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
        $serviceInfo = getServiceInformation($pdo, $user_id, $service_type, $category_id, $service_name);
    }

    // Debug log
    error_log("PayU Create - Service Info: " . json_encode($serviceInfo));
    error_log("PayU Create - Received services_json: " . ($services_json ? "YES" : "NO"));
    
    if (!$serviceInfo || !isset($serviceInfo['success']) || !$serviceInfo['success']) {
        error_log("Service info error: " . ($serviceInfo['message'] ?? 'Unknown error'));
        
        // Fallback service info
        $serviceInfo = [
            "reference_id" => $category_id ?? 'GENERIC_' . $user_id,
            "reference_type" => $category_id ? 'category_id' : 'generic_service',
            "service_name_json" => json_encode([
                "type" => "generic",
                "service_name" => $service_name ?: "Service Booking",
                "service_type" => "PayU Payment"
            ]),
            "service_name_display" => $service_name ?: "Service Booking"
        ];
    }
    
    // ⭐ INSERT WITH SERVICE JSON DATA - SIMILAR TO RAZORPAY
    $stmt = $pdo->prepare("
        INSERT INTO customer_payment 
        (user_id, customer_id, appointment_id, receipt, amount, total_amount, currency, 
         status, payment_method, appointment_date, slot_from, slot_to, token_count,
         service_reference_id, service_reference_type, service_name,
         gst_type, gst_percent, gst_amount, batch_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'INR', 'pending', 'payu', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    // Debug: Log what we're inserting
    error_log("PayU Inserting service_name_json: " . substr($serviceInfo['service_name_json'], 0, 200) . "...");
    
    $success = $stmt->execute([
        $user_id,
        $customer_id,
        $appointment_id,
        $receipt,
        $sub_total,       // amount (without GST)
        $amount,          // total_amount (with GST)
        $appointment_date,
        $slot_from,
        $slot_to,
        $token_count,
        $serviceInfo['reference_id'],       // DEPT_xxx or CAT_xxx
        $serviceInfo['reference_type'],     // department_id or category_id
        $serviceInfo['service_name_json'],  // ⭐ JSON format
        $gst_type,
        $gst_percent,
        $gst_amount,
        $batch_id
    ]);

    if (!$success) {
        $errorInfo = $stmt->errorInfo();
        error_log("PayU Insert error: " . json_encode($errorInfo));
        throw new Exception("Database insert failed: " . ($errorInfo[2] ?? 'Unknown error'));
    }

    $payment_id = $pdo->lastInsertId();

    // Fetch PayU credentials
    $stmt = $pdo->prepare("
        SELECT payu_api_key, payu_salt 
        FROM site_settings 
        WHERE user_id = ? LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $cred = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cred || empty($cred['payu_api_key']) || empty($cred['payu_salt'])) {
        throw new Exception("PayU is not configured for this seller");
    }

    $merchantKey = trim($cred['payu_api_key']);
    $salt        = trim($cred['payu_salt']);

    // Generate transaction ID
    $txnid = "TXN" . time() . rand(1000, 9999);
    $amountFormatted = number_format($amount, 2, '.', '');
    
    // Set product info from service
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
    
    $productinfo = $serviceDisplay ?: "Booking Payment";

    // UDF fields - include ALL appointment details in JSON
    $udf1 = $appointment_id;      // appointment ID
    $udf2 = $customer_id;         // customer ID
    $udf3 = $user_id;             // user ID
    $udf4 = $appointment_date;    // appointment date
    $udf5 = json_encode([         // all details as JSON
        'slot_from' => $slot_from,
        'slot_to' => $slot_to,
        'token_count' => $token_count,
        'category_id' => $category_id,
        'department_id' => $data['department_id'] ?? null,
        'batch_id' => $batch_id,
        'receipt' => $receipt,
        'gst_type' => $gst_type,
        'gst_percent' => $gst_percent,
        'gst_amount' => $gst_amount,
        'sub_total' => $sub_total,
        'payment_id' => $payment_id,
        'service_info' => [       // ⭐ ADD SERVICE INFO
            'reference_id' => $serviceInfo['reference_id'],
            'reference_type' => $serviceInfo['reference_type'],
            'display_name' => $serviceDisplay,
            'service_type' => $service_type,
            'services_json' => $services_json ? 'yes' : 'no'
        ]
    ]);

    // PayU Hash Format
    $hashString =
        $merchantKey . "|" .
        $txnid . "|" .
        $amountFormatted . "|" .
        $productinfo . "|" .
        $name . "|" .
        $email . "|" .
        $udf1 . "|" .
        $udf2 . "|" .
        $udf3 . "|" .
        $udf4 . "|" .
        $udf5 . "|" .
        "" . "|" . "" . "|" . "" . "|" . "" . "|" . "" . "|" .
        $salt;

    $hash = strtolower(hash("sha512", $hashString));

    // Store txnid for later update
    $updateStmt = $pdo->prepare("
        UPDATE customer_payment 
        SET payment_id = ?
        WHERE id = ? AND user_id = ?
    ");
    $updateStmt->execute([$txnid, $payment_id, $user_id]);

    // Browser-based redirect
    $surl = "http://localhost/manager.bookpanlam/public/customers/payment/payu-success.php";
    $furl = "http://localhost/manager.bookpanlam/public/customers/payment/payu-failure.php";

    echo json_encode([
        "success"       => true,
        "endpoint"      => "https://test.payu.in/_payment",
        "key"           => $merchantKey,
        "txnid"         => $txnid,
        "amount"        => $amountFormatted,
        "productinfo"   => $productinfo,
        "firstname"     => $name,
        "email"         => $email,
        "phone"         => $phone,
        "surl"          => $surl,
        "furl"          => $furl,
        "hash"          => $hash,
        "service_provider" => "payu_paisa",

        // Pass all appointment details
        "udf1" => $udf1,  // appointment_id
        "udf2" => $udf2,  // customer_id
        "udf3" => $udf3,  // user_id
        "udf4" => $udf4,  // appointment_date
        "udf5" => $udf5,  // ALL details JSON
        
        // Return service info for frontend
        "service_info" => [
            "reference_id" => $serviceInfo['reference_id'],
            "reference_type" => $serviceInfo['reference_type'],
            "display_name" => $serviceDisplay,
            "service_type" => $service_type,
            "has_services_json" => $services_json ? true : false
        ],
        
        // Return for confirmation
        "appointment_id" => $appointment_id,
        "receipt" => $receipt,
        "payment_id" => $payment_id
    ]);

} catch (Exception $e) {
    error_log("PayU Create Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>