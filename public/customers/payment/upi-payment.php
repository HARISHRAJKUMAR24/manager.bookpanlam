<?php
// manager.bookpanlam/public/customers/payment/upi-payment.php

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Only POST requests are allowed"
        ]);
        exit;
    }

    $json = file_get_contents('php://input');
    
    if (empty($json)) {
        echo json_encode([
            "success" => false,
            "message" => "No data received"
        ]);
        exit;
    }

    $input = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON: " . json_last_error_msg()
        ]);
        exit;
    }

    // Required fields
    if (empty($input['user_id']) || empty($input['customer_id'])) {
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields"
        ]);
        exit;
    }

    // Include required files
    $config_path = __DIR__ . '/../../../config/config.php';
    $database_path = __DIR__ . '/../../../src/database.php';
    $functions_path = __DIR__ . '/../../../src/functions.php';
    
    require_once $config_path;
    require_once $database_path;
    require_once $functions_path;

    $db = getDbConnection();
    
    // Extract data
    $user_id = (int) $input['user_id'];
    $customer_id = (int) $input['customer_id'];
    
    // Booking details
    $appointment_date = $input['appointment_date'] ?? '';
    $slot_from = $input['slot_from'] ?? '';
    $slot_to = $input['slot_to'] ?? '';
    $token_count = (int) ($input['token_count'] ?? 1);
    $category_id = $input['category_id'] ?? null;
    $batch_id = $input['batch_id'] ?? null;
    
    // Payment details
    $total_amount = (float) ($input['total_amount'] ?? 0);
    $sub_total = (float) ($input['sub_total'] ?? $total_amount);
    $gst_type = $input['gst_type'] ?? '';
    $gst_percent = (float) ($input['gst_percent'] ?? 0);
    $gst_amount = (float) ($input['gst_amount'] ?? 0);
    
    // Customer details
    $customer_name = $input['customer_name'] ?? '';
    $customer_email = $input['customer_email'] ?? '';
    $customer_phone = $input['customer_phone'] ?? '';
    
    // ⭐ NEW: Get services_json from input
    $services_json = $input['services_json'] ?? null;
    
    // Generate appointment ID
    $appointment_id = generateAppointmentId($user_id, $db);
    
    // Generate receipt
    $receipt = "UPI_" . time() . "_" . rand(1000, 9999);
    
    // ⭐ UPDATED: Get service information with JSON support
    $service_ref_id = null;
    $service_ref_type = null;
    $service_name_json = null;
    $service_display_name = null;
    
    // If services_json is provided (for department bookings)
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
        $service_type = $input['service_type'] ?? 'category';
        if ($service_type === 'department') {
            $department_id = $input['department_id'] ?? $category_id;
            $service_ref_id = $department_id;
            $service_ref_type = 'department_id';

            // If it's a primary ID, try to get department_id from database
            if ($service_ref_id && !strpos($service_ref_id, 'DEPT_') === 0) {
                $stmt = $db->prepare("SELECT department_id FROM departments WHERE id = ? AND user_id = ?");
                $stmt->execute([$service_ref_id, $user_id]);
                $dept = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($dept && $dept['department_id']) {
                    $service_ref_id = $dept['department_id'];
                }
            }
            
            $service_display_name = $servicesData['department_name'] ?? $input['service_name'] ?? 'Department Service';
        } else {
            $service_ref_id = $category_id;
            $service_ref_type = 'category_id';
            $service_display_name = $input['service_name'] ?? 'Service';
        }
    } else {
        // Fallback to category lookup if no services_json
        if ($category_id) {
            $catStmt = $db->prepare("
                SELECT category_id, name, doctor_name 
                FROM categories 
                WHERE category_id = ? 
                AND user_id = ?
                LIMIT 1
            ");
            $catStmt->execute([$category_id, $user_id]);
            $category = $catStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($category) {
                $service_ref_id = $category['category_id'];
                $service_ref_type = 'category_id';
                $service_name_json = json_encode([
                    "type" => "category",
                    "doctor_name" => $category['doctor_name'] ?? '',
                    "specialization" => $category['name'] ?? '',
                    "service_type" => "category"
                ]);
                $service_display_name = $category['doctor_name'] ?? $category['name'];
            }
        }
    }
    
    // Final fallback if no service info found
    if (!$service_name_json) {
        $service_name_json = json_encode([
            "type" => "generic",
            "service_name" => $input['service_name'] ?? "Service Booking",
            "service_type" => "UPI Payment"
        ]);
        $service_display_name = $input['service_name'] ?? "Service Booking";
    }
    
    // ⭐ UPDATED SQL query to store service_name as JSON
    $sql = "INSERT INTO customer_payment 
            (user_id, customer_id, appointment_id, receipt, amount, total_amount, currency,
             status, payment_method, appointment_date, slot_from, slot_to, token_count,
             service_reference_id, service_reference_type, service_name,
             gst_type, gst_percent, gst_amount, batch_id)
            VALUES (?, ?, ?, ?, ?, ?, 'INR', 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    
    // EXECUTE with JSON service name
    $success = $stmt->execute([
        $user_id,                    // 1. user_id
        $customer_id,                // 2. customer_id
        $appointment_id,             // 3. appointment_id
        $receipt,                    // 4. receipt
        $sub_total,                  // 5. amount
        $total_amount,               // 6. total_amount
        'upi',                       // 7. payment_method (set as 'upi')
        $appointment_date,           // 8. appointment_date
        $slot_from,                  // 9. slot_from
        $slot_to,                    // 10. slot_to
        $token_count,                // 11. token_count
        $service_ref_id,             // 12. service_reference_id
        $service_ref_type,           // 13. service_reference_type
        $service_name_json,          // 14. service_name (as JSON)
        $gst_type,                   // 15. gst_type
        $gst_percent,                // 16. gst_percent
        $gst_amount,                 // 17. gst_amount
        $batch_id                    // 18. batch_id
    ]);
    
    if ($success) {
        $payment_id = $db->lastInsertId();
        
        // Update token availability if batch_id exists
        if ($batch_id && $appointment_date && $service_ref_id) {
            try {
                $batchParts = explode(':', $batch_id);
                if (count($batchParts) === 2) {
                    $dayIndex = intval($batchParts[0]);
                    $slotIndex = intval($batchParts[1]);
                    
                    $dayName = date('D', strtotime($appointment_date));
                    
                    $stmtDoctor = $db->prepare("
                        SELECT weekly_schedule 
                        FROM doctor_schedule 
                        WHERE category_id = ? 
                        AND user_id = ?
                        LIMIT 1
                    ");
                    $stmtDoctor->execute([$service_ref_id, $user_id]);
                    $doctor = $stmtDoctor->fetch(PDO::FETCH_ASSOC);
                    
                    if ($doctor && $doctor['weekly_schedule']) {
                        $weeklySchedule = json_decode($doctor['weekly_schedule'], true);
                        
                        if (isset($weeklySchedule[$dayName]['slots'][$slotIndex])) {
                            $currentTokens = intval($weeklySchedule[$dayName]['slots'][$slotIndex]['token'] ?? 0);
                            $newTokens = max(0, $currentTokens - $token_count);
                            $weeklySchedule[$dayName]['slots'][$slotIndex]['token'] = strval($newTokens);
                            
                            $updateSchedule = $db->prepare("
                                UPDATE doctor_schedule 
                                SET weekly_schedule = ? 
                                WHERE category_id = ? 
                                AND user_id = ?
                            ");
                            $updateSchedule->execute([
                                json_encode($weeklySchedule),
                                $service_ref_id,
                                $user_id
                            ]);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("UPI Batch token update error: " . $e->getMessage());
            }
        }
        
        // Return success response with JSON service info
        echo json_encode([
            "success" => true,
            "message" => "UPI payment order created successfully",
            "payment_id" => $payment_id,
            "appointment_id" => $appointment_id,
            "receipt" => $receipt,
            "status" => "pending",
            "payment_method" => "upi",
            "service_info" => [
                "reference_id" => $service_ref_id,
                "reference_type" => $service_ref_type,
                "display_name" => $service_display_name,
                "has_services_json" => $services_json ? true : false
            ],
            "data" => [
                "customer_id" => $customer_id,
                "customer_name" => $customer_name,
                "customer_email" => $customer_email,
                "customer_phone" => $customer_phone,
                "total_amount" => $total_amount,
                "appointment_date" => $appointment_date,
                "slot_time" => $slot_from . " to " . $slot_to
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
    error_log("UPI Payment Error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage(),
        "error" => true,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
}

exit;
?>