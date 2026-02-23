<?php
// managerbp/public/customers/payment/create-coh-order.php

/* -------------------------------
   CORS SETTINGS
-------------------------------- */
header("Access-Control-Allow-Origin: https://web.bookpanlam.com");
header("Access-Control-Allow-Origin: https://bookpanlam.com");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Only POST requests are allowed"
        ]);
        exit;
    }

    // Get the raw POST data
    $json = file_get_contents('php://input');
    
    if (empty($json)) {
        echo json_encode([
            "success" => false,
            "message" => "No data received"
        ]);
        exit;
    }

    // Decode JSON
    $input = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON: " . json_last_error_msg()
        ]);
        exit;
    }

    // Check required fields
    if (empty($input['user_id']) || empty($input['customer_id'])) {
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields: user_id or customer_id"
        ]);
        exit;
    }

    // Include database connection
    $config_path = __DIR__ . '/../../../config/config.php';
    $database_path = __DIR__ . '/../../../src/database.php';
    $functions_path = __DIR__ . '/../../../src/functions.php';
    
    require_once $config_path;
    require_once $database_path;
    require_once $functions_path;

    $db = getDbConnection();
    
    // ⭐ Generate appointment ID using the same function as Razorpay
    $appointment_id = generateAppointmentId($input['user_id'], $db);
    
    // Extract data with defaults
    $user_id = (int) $input['user_id'];
    $customer_id = (int) $input['customer_id'];
    $customer_name = $input['customer_name'] ?? '';
    $customer_email = $input['customer_email'] ?? '';
    $customer_phone = $input['customer_phone'] ?? '';
    $total_amount = (float) ($input['amount'] ?? 0);
    
    // Appointment details
    $appointment_date = $input['appointment_date'] ?? date('Y-m-d');
    $slot_from = $input['slot_from'] ?? '';
    $slot_to = $input['slot_to'] ?? '';
    $token_count = (int) ($input['token_count'] ?? 1);
    $category_id = $input['category_id'] ?? null;
    $department_id = $input['department_id'] ?? null;
    
    // Service type and name
    $service_type = $input['service_type'] ?? 'category';
    $service_name = $input['service_name'] ?? '';
    
    // ⭐ NEW: Services data in JSON format
    $services_json = $input['services_json'] ?? null;
    
    // ⭐ NEW: Extract batch_id
    $batch_id = $input['batch_id'] ?? null;
    
    // GST Details
    $gst_type = $input['gst_type'] ?? '';
    $gst_percent = (float) ($input['gst_percent'] ?? 0);
    $gst_amount = (float) ($input['gst_amount'] ?? 0);
    $sub_total = (float) ($input['subTotal'] ?? $total_amount);
    
    // Check if cash_in_hand is enabled
    $settingsStmt = $db->prepare("
        SELECT cash_in_hand 
        FROM site_settings 
        WHERE user_id = ?
        LIMIT 1
    ");
    $settingsStmt->execute([$user_id]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings || $settings['cash_in_hand'] != 1) {
        echo json_encode([
            "success" => false,
            "message" => "Cash on Hand is not enabled for this seller"
        ]);
        exit;
    }
    
    // ⭐ NEW: Prepare service_name_json based on services data
    $service_name_json = '';
    
    if ($services_json) {
        // Use the provided services JSON
        $service_name_json = json_encode($services_json);
    } else {
        // Generate service_name_json based on service type
        $serviceInfo = getServiceInformation($db, $user_id, $service_type, $category_id, $service_name);
        
        if ($serviceInfo['success']) {
            $service_name_json = $serviceInfo['service_name_json'];
        } else {
            // Fallback if service info not found
            if ($service_type === 'department') {
                $service_name_json = json_encode([
                    "type" => "department",
                    "department_name" => $service_name ?: "Department Service",
                    "services" => []
                ]);
            } else {
                $service_name_json = json_encode([
                    "type" => "category",
                    "service_name" => $service_name ?: "Service",
                    "services" => []
                ]);
            }
        }
    }
    
    // Generate receipt for COH
    $receipt = "COH_" . time() . "_" . rand(1000, 9999);
    
    // ⭐ UPDATE: Insert COH order into database WITH services_json
    $sql = "INSERT INTO customer_payment 
            (user_id, customer_id, appointment_id, receipt, amount, total_amount, currency, 
             status, payment_method, appointment_date, slot_from, slot_to, token_count,
             service_reference_id, service_reference_type, service_name,
             gst_type, gst_percent, gst_amount, batch_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'INR', 'pending', 'cash', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    
    // Determine reference type and ID
    $reference_type = 'category_id';
    $reference_id = $category_id;
    
    if ($service_type === 'department') {
        $reference_type = 'department_id';
        $reference_id = $department_id ?? $category_id;
    }
    
    $success = $stmt->execute([
        $user_id,
        $customer_id,
        $appointment_id,
        $receipt,
        $sub_total,
        $total_amount,
        $appointment_date,
        $slot_from,
        $slot_to,
        $token_count,
        $reference_id,
        $reference_type,
        $service_name_json, // JSON format with services
        $gst_type,
        $gst_percent,
        $gst_amount,
        $batch_id
    ]);
    
    if ($success) {
        $payment_id = $db->lastInsertId();
        
        /* -------------------------------
           ⭐ TOKEN UPDATE SECTION - COMPLETELY REMOVED
           No token subtraction from doctor_schedule for COH
        -------------------------------- */
        
        echo json_encode([
            "success" => true,
            "message" => "Cash on Hand appointment booked successfully",
            "payment_id" => $payment_id,
            "appointment_id" => $appointment_id,
            "receipt" => $receipt,
            "status" => "pending",
            "payment_method" => "cash",
            "service_name_json" => $service_name_json,
            "token_update" => "Token subtraction disabled for COH - no changes to doctor_schedule",
            "data" => [
                "customer_name" => $customer_name,
                "customer_phone" => $customer_phone,
                "total_amount" => $total_amount,
                "appointment_date" => $appointment_date,
                "slot_from" => $slot_from,
                "slot_to" => $slot_to,
                "token_count" => $token_count,
                "reference_id" => $reference_id,
                "reference_type" => $reference_type,
                "service_type" => $service_type,
                "batch_id" => $batch_id
            ]
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode([
            "success" => false,
            "message" => 'Database error: ' . ($errorInfo[2] ?? 'Unknown error')
        ]);
    }
    
} catch (Exception $e) {
    error_log("COH Error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage(),
        "error" => true,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
}

exit;