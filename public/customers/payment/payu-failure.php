<?php
// manager.bookpanlam/public/customers/payment/payu-failure.php

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$db = getDbConnection();

// Update pending payment status to failed if txnid is available
if (isset($_POST['txnid'])) {
    $txnid = $_POST['txnid'];
    
    $updateStmt = $db->prepare("
        UPDATE pending_payments 
        SET status = 'failed' 
        WHERE txnid = ?
    ");
    $updateStmt->execute([$txnid]);
}

// Redirect to failure page
$redirect_url = "http://localhost:3000/payment-failed?" . http_build_query([
    'error' => 'payment_cancelled',
    'message' => 'Payment was cancelled or failed',
    'method' => 'payu'
]);

echo "<script>window.location.href = '$redirect_url';</script>";
exit;
?>