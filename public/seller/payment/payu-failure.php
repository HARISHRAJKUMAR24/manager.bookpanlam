<?php
// manager.bookpanlam/public/seller/payment/payu-failure.php

$txnid = $_POST['txnid'] ?? '';
$error = $_POST['error'] ?? 'Payment failed';

error_log("PayU FAILED | TXNID={$txnid} | ERROR={$error}");

header("Location: http://localhost:3000/payment-failed");
exit;
