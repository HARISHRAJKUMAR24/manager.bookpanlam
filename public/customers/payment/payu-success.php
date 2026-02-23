<?php
// manager.bookpanlam/public/customers/payment/payu-success.php

// Log everything for debugging
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'POST_data' => $_POST,
    'GET_data' => $_GET
];

file_put_contents(__DIR__."/payu_success.log", print_r($logData, true) . "\n", FILE_APPEND);

$status = $_POST['status'] ?? '';
$txnid = $_POST['txnid'] ?? '';
$amount = $_POST['amount'] ?? '';
$udf1 = $_POST['udf1'] ?? ''; // appointment_id
$udf2 = $_POST['udf2'] ?? ''; // customer_id
$udf3 = $_POST['udf3'] ?? ''; // user_id
$udf4 = $_POST['udf4'] ?? ''; // appointment_date
$udf5 = $_POST['udf5'] ?? ''; // all details JSON

require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$db = getDbConnection();

if ($status == "success") {
    
    // Get the pending payment record
    $stmt = $db->prepare("
        SELECT * FROM customer_payment 
        WHERE payment_id = ? 
        AND user_id = ? 
        AND status = 'pending'
        LIMIT 1
    ");
    $stmt->execute([$txnid, $udf3]);
    $pendingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pendingRecord) {
        error_log("PayU Success: No pending record found for txnid: $txnid, user_id: $udf3");
        echo "<script>
            alert('Payment record not found! Please contact support.');
            window.location.href='http://localhost:3000/payment-failed?error=record_not_found';
        </script>";
        exit;
    }
    
    // Use values from pending record (including service_name JSON)
    $slot_from = $pendingRecord['slot_from'] ?? null;
    $slot_to = $pendingRecord['slot_to'] ?? null;
    $token_count = $pendingRecord['token_count'] ?? 1;
    $batch_id = $pendingRecord['batch_id'] ?? null;
    $category_id = $pendingRecord['service_reference_id'] ?? null;
    $receipt = $pendingRecord['receipt'] ?? null;
    $gst_type = $pendingRecord['gst_type'] ?? '';
    $gst_percent = $pendingRecord['gst_percent'] ?? 0;
    $gst_amount = $pendingRecord['gst_amount'] ?? 0;
    $sub_total = $pendingRecord['amount'] ?? $amount;
    
    // ⭐ GET EXISTING SERVICE JSON FROM PENDING RECORD
    $existing_service_json = $pendingRecord['service_name'] ?? null;
    
    // Decode UDF5 if available
    $details = [];
    if (!empty($udf5)) {
        $details = json_decode($udf5, true);
    }
    
    $signature = $_POST['hash'] ?? '';
    
    // ⭐ UPDATE WITH SIGNATURE AND STATUS - KEEP SERVICE JSON
    $updateStmt = $db->prepare("
        UPDATE customer_payment 
        SET status = 'paid',
            signature = ?
        WHERE payment_id = ? AND user_id = ? AND status = 'pending'
    ");
    
    $updateResult = $updateStmt->execute([$signature, $txnid, $udf3]);
    
    if (!$updateResult) {
        $errorInfo = $updateStmt->errorInfo();
        error_log("PayU Update Error: " . json_encode($errorInfo));
    }
    
    $rowsUpdated = $updateStmt->rowCount();
    error_log("PayU Updated rows: $rowsUpdated");
    
    // If no rows updated, check if already paid
    if ($rowsUpdated === 0) {
        $checkStmt = $db->prepare("SELECT status, service_name FROM customer_payment WHERE payment_id = ? AND user_id = ?");
        $checkStmt->execute([$txnid, $udf3]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($checkResult && $checkResult['status'] === 'paid') {
            error_log("PayU: Payment already marked as paid");
            // Still redirect to success with existing data
        }
    }
    
    /* -------------------------------
       ⭐ TOKEN UPDATE SECTION - COMPLETELY REMOVED
       No token subtraction from doctor_schedule for PayU
    -------------------------------- */

    // ⭐ GET SERVICE DISPLAY NAME FROM JSON
    $serviceDisplay = "Service";
    
    if ($existing_service_json) {
        try {
            $serviceData = json_decode($existing_service_json, true);
            if (isset($serviceData['department_name'])) {
                $serviceDisplay = $serviceData['department_name'];
            } elseif (isset($serviceData['doctor_name'])) {
                $serviceDisplay = $serviceData['doctor_name'];
                if (!empty($serviceData['specialization'])) {
                    $serviceDisplay .= " - " . $serviceData['specialization'];
                }
            } elseif (isset($serviceData['service_name'])) {
                $serviceDisplay = $serviceData['service_name'];
            }
        } catch (Exception $e) {
            error_log("Error parsing service JSON: " . $e->getMessage());
        }
    }
    
    // Debug log
    error_log("PayU Success - Service JSON: " . ($existing_service_json ? "Found" : "Not found"));
    error_log("PayU Success - Service Display: $serviceDisplay");

    // Redirect to success page
    echo "<script>
        alert('Payment Successful!\\\\nService: " . addslashes($serviceDisplay) . "');
        window.location.href='http://localhost:3001/payment-success?" . 
        "appointment_id=" . urlencode($udf1) . "&" .
        "receipt=" . urlencode($receipt) . "&" .
        "payment_id=" . urlencode($txnid) . "&" .
        "status=paid&" .
        "method=payu&" .
        "service_name=" . urlencode($serviceDisplay) . "&" .
        "has_json=" . ($existing_service_json ? 'yes' : 'no') . "';
    </script>";
    
} else {
    // Update as failed
    $stmt = $db->prepare("
        UPDATE customer_payment 
        SET status = 'failed'
        WHERE payment_id = ? AND user_id = ?
    ");
    
    $stmt->execute([$txnid, $udf3]);

    echo "<script>
        alert('Payment Failed!');
        window.location.href='http://localhost:3000/payment-failed?" .
        "appointment_id=" . urlencode($udf1) . "&" .
        "payment_id=" . urlencode($txnid) . "&" .
        "status=failed';
    </script>";
}
?>