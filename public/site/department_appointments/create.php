<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "No data received"
    ]);
    exit();
}

// Validate required fields
$required_fields = [
    'userId', 'customerId', 'departmentName', 
    'customerName', 'customerPhone', 'selectedDate', 'selectedSlot'
];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Missing required field: $field"
        ]);
        exit();
    }
}

try {
    // Generate unique appointment ID
    $appointment_id = $data['appointment_id'] ?? 'DEPT-' . time() . '-' . rand(1000, 9999);
    
    // Generate receipt number
    $receipt = 'RC' . date('Ymd') . '-' . rand(10000, 99999);
    
    // Extract slot times
    $slot_from = $data['selectedSlot']['from'] ?? '';
    $slot_to = $data['selectedSlot']['to'] ?? '';
    
    // Parse date
    $appointment_date = date('Y-m-d', strtotime($data['selectedDate']));
    
    // Calculate amounts
    $consultation_fee = floatval($data['amount'] ?? 0);
    $token_count = intval($data['token'] ?? 1);
    $amount = $consultation_fee * $token_count;
    
    $gst_percent = floatval($data['gstPercent'] ?? 0);
    $gst_type = $data['gstType'] ?? 'inclusive';
    $gst_amount = floatval($data['gstAmount'] ?? 0);
    $total_amount = floatval($data['totalAmount'] ?? $amount);
    
    // Determine payment status
    $payment_method = $data['paymentMethod'] ?? 'cash';
    $status = ($payment_method === 'cash') ? 'pending' : 'pending_online';
    
    // Insert into customer_payment table
    $stmt = $pdo->prepare("
        INSERT INTO customer_payment (
            user_id,
            customer_id,
            appointment_id,
            receipt,
            amount,
            currency,
            status,
            payment_id,
            signature,
            gst_type,
            gst_percent,
            gst_amount,
            total_amount,
            payment_method,
            appointment_date,
            slot_from,
            slot_to,
            token_count,
            service_reference_id,
            service_reference_type,
            service_name,
            batch_id
        ) VALUES (
            :user_id,
            :customer_id,
            :appointment_id,
            :receipt,
            :amount,
            :currency,
            :status,
            :payment_id,
            :signature,
            :gst_type,
            :gst_percent,
            :gst_amount,
            :total_amount,
            :payment_method,
            :appointment_date,
            :slot_from,
            :slot_to,
            :token_count,
            :service_reference_id,
            :service_reference_type,
            :service_name,
            :batch_id
        )
    ");
    
    // Execute with parameters
    $stmt->execute([
        ':user_id' => $data['userId'],
        ':customer_id' => $data['customerId'],
        ':appointment_id' => $appointment_id,
        ':receipt' => $receipt,
        ':amount' => $amount,
        ':currency' => 'INR',
        ':status' => $status,
        ':payment_id' => NULL,
        ':signature' => NULL,
        ':gst_type' => $gst_type,
        ':gst_percent' => $gst_percent,
        ':gst_amount' => $gst_amount,
        ':total_amount' => $total_amount,
        ':payment_method' => $payment_method,
        ':appointment_date' => $appointment_date,
        ':slot_from' => $slot_from,
        ':slot_to' => $slot_to,
        ':token_count' => $token_count,
        ':service_reference_id' => $data['departmentId'] ?? 0,
        ':service_reference_type' => 'department',
        ':service_name' => $data['departmentName'],
        ':batch_id' => $data['selectedSlot']['batch_id'] ?? NULL
    ]);
    
    $payment_id = $pdo->lastInsertId();
    
    // Store booking data in session for payment page
    $_SESSION['pending_booking'] = [
        'type' => 'department',
        'appointment_id' => $appointment_id,
        'payment_id' => $payment_id,
        'amount' => $total_amount,
        'department_name' => $data['departmentName'],
        'customer_name' => $data['customerName'],
        'customer_phone' => $data['customerPhone'],
        'appointment_date' => $appointment_date,
        'slot_from' => $slot_from,
        'slot_to' => $slot_to,
        'payment_method' => $payment_method
    ];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Department appointment created successfully',
        'data' => [
            'appointment_id' => $appointment_id,
            'receipt' => $receipt,
            'payment_id' => $payment_id,
            'payment_status' => $status,
            'amount' => $total_amount,
            'currency' => 'INR',
            'appointment_date' => $appointment_date,
            'slot_from' => $slot_from,
            'slot_to' => $slot_to,
            'department_name' => $data['departmentName'],
            'customer_name' => $data['customerName'],
            'customer_phone' => $data['customerPhone']
        ],
        'next_step' => 'redirect_to_payment'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>