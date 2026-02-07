<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Origin, Accept");
header("Access-Type: application/json");

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
   GET JSON INPUT
======================= */
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Debug logging
error_log("Update Status Request: " . print_r($data, true));

$appointmentId = $data['appointment_id'] ?? null;
$newStatus = $data['status'] ?? null;
$newPaymentMethod = $data['payment_method'] ?? null;

// Debug: Log the payment method
error_log("Payment Method from request: " . $newPaymentMethod);

if (!$appointmentId || !$newStatus) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing required fields",
        "debug" => [
            "appointment_id" => $appointmentId, 
            "status" => $newStatus,
            "payment_method_received" => $newPaymentMethod
        ]
    ]);
    exit;
}

// Normalize status values
$newStatusLower = strtolower($newStatus);
if ($newStatusLower === 'cancel') {
    $newStatusLower = 'cancelled';
} elseif ($newStatusLower === 'refunded') {
    $newStatusLower = 'refund';
}

// Validate status values
$allowedStatuses = ['paid', 'pending', 'waiting', 'cancelled', 'refund'];
if (!in_array($newStatusLower, $allowedStatuses)) {
    echo json_encode([
        "success" => false, 
        "message" => "Invalid status. Allowed: paid, pending, waiting, cancelled, refund",
        "received" => $newStatus
    ]);
    exit;
}

/* =======================
   CHECK IF APPOINTMENT EXISTS AND BELONGS TO USER
======================= */
$checkStmt = $pdo->prepare("
    SELECT id, status, payment_method FROM customer_payment 
    WHERE appointment_id = ? AND user_id = ?
");
$checkStmt->execute([$appointmentId, $userId]);
$appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    echo json_encode([
        "success" => false, 
        "message" => "Appointment not found or you don't have permission"
    ]);
    exit;
}

/* =======================
   VALIDATE STATUS TRANSITION
======================= */
$currentStatus = strtolower($appointment['status'] ?? 'pending');
$paymentMethod = strtolower($appointment['payment_method'] ?? '');

// Check if the transition is valid
$isValidTransition = true;
$errorMessage = "";

// Rules for refund status
if ($newStatusLower === 'refund') {
    if ($currentStatus !== 'paid') {
        $isValidTransition = false;
        $errorMessage = "Refund can only be applied to paid appointments";
    }
}

// Rules for paid status
if ($newStatusLower === 'paid') {
    if (in_array($currentStatus, ['cancelled', 'refund'])) {
        $isValidTransition = false;
        $errorMessage = "Cannot mark cancelled/refunded appointments as paid";
    }
}

if (!$isValidTransition) {
    echo json_encode([
        "success" => false,
        "message" => $errorMessage,
        "current_status" => $currentStatus,
        "payment_method" => $paymentMethod
    ]);
    exit;
}

/* =======================
   UPDATE THE STATUS AND PAYMENT METHOD
======================= */
// Prepare the update query
$updateFields = ["status = ?"];
$updateValues = [$newStatusLower];

// Add payment method update if provided
if ($newPaymentMethod !== null) {
    $updateFields[] = "payment_method = ?";
    $updateValues[] = $newPaymentMethod;
    
    // Debug: Log the update
    error_log("Updating payment method to: " . $newPaymentMethod);
}

$updateValues[] = $appointmentId;
$updateValues[] = $userId;

$updateQuery = "UPDATE customer_payment SET " . implode(", ", $updateFields) . " WHERE appointment_id = ? AND user_id = ?";
error_log("Update Query: " . $updateQuery);
error_log("Update Values: " . print_r($updateValues, true));

$updateStmt = $pdo->prepare($updateQuery);

try {
    $updateStmt->execute($updateValues);
    
    error_log("Rows affected: " . $updateStmt->rowCount());
    
    if ($updateStmt->rowCount() > 0) {
        // Fetch updated record to return
        $fetchStmt = $pdo->prepare("
            SELECT * FROM customer_payment 
            WHERE appointment_id = ? AND user_id = ?
        ");
        $fetchStmt->execute([$appointmentId, $userId]);
        $updatedRecord = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "message" => "Status updated successfully" . ($newPaymentMethod ? " with payment method update" : ""),
            "debug" => [
                "payment_method_sent" => $newPaymentMethod,
                "payment_method_updated" => $updatedRecord['payment_method'] ?? null
            ],
            "data" => [
                "appointment_id" => $appointmentId,
                "new_status" => $newStatusLower,
                "previous_status" => $currentStatus,
                "new_payment_method" => $newPaymentMethod ?: $paymentMethod,
                "record" => $updatedRecord
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No changes made. Status might already be set to: " . $newStatusLower,
            "debug" => [
                "payment_method_sent" => $newPaymentMethod,
                "current_db_value" => $paymentMethod
            ]
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $e->getMessage(),
        "debug" => [
            "payment_method_sent" => $newPaymentMethod
        ]
    ]);
}