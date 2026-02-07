<?php
// manager.bookpanlam/public/seller/payment/payu-success.php

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* -------------------------------------------------
   1. Read PayU POST data
------------------------------------------------- */
$status = $_POST['status'] ?? '';
$txnid  = $_POST['txnid'] ?? '';

if ($status !== 'success' || !$txnid) {
    header("Location: http://localhost:3000/payment-failed");
    exit;
}

/* -------------------------------------------------
   2. Prevent duplicate inserts
------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT invoice_number 
    FROM subscription_histories 
    WHERE payment_id = ?
");
$stmt->execute([$txnid]);

if ($existing = $stmt->fetch(PDO::FETCH_ASSOC)) {
    header("Location: http://localhost:3000/payment-success?invoice=" . $existing['invoice_number']);
    exit;
}

/* -------------------------------------------------
   3. Extract REQUIRED PayU fields
------------------------------------------------- */
$plan_id = $_POST['udf1'] ?? null;
$user_id = $_POST['udf2'] ?? null;

if (!$plan_id || !$user_id) {
    error_log("PayU ERROR: Missing plan_id or user_id");
    header("Location: http://localhost:3000/payment-failed");
    exit;
}

/* -------------------------------------------------
   4. Get GST data from UDF fields (same as Razorpay - no calculation)
------------------------------------------------- */
// Get plan details to get gst_type
$stmt = $pdo->prepare("SELECT gst_type FROM subscription_plans WHERE id = ?");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);
$gst_type = $plan['gst_type'] ?? 'exclusive';

// Get GST percentage and currency from settings
$stmt = $pdo->query("SELECT gst_percentage, currency FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$gst_percentage = (int) ($settings['gst_percentage'] ?? 18);
$currency = $settings['currency'] ?? 'INR';

// Get currency symbol
if (function_exists('getCurrencySymbol')) {
    $currency_symbol = getCurrencySymbol($currency);
} else {
    $currency_symbol = ($currency === 'INR') ? '₹' : '$';
}

/* -------------------------------------------------
   5. Generate invoice number
------------------------------------------------- */
$stmt = $pdo->query("SELECT MAX(invoice_number) AS last_invoice FROM subscription_histories");
$last = $stmt->fetch(PDO::FETCH_ASSOC);
$invoice_number = ($last['last_invoice'] ?? 0) + 1;

/* -------------------------------------------------
   6. Get amounts from UDF fields (NO GST CALCULATION - same as Razorpay)
------------------------------------------------- */
$amount = (int) ($_POST['amount'] ?? 0);
$gst_amount = (int) ($_POST['udf4'] ?? 0); // GST amount should come from frontend via udf4
$gst_number = $_POST['udf6'] ?? null; // GSTIN from udf6
// udf3 could be plan base amount if needed

// Debug log
error_log("PayU No GST Calculation - Storing as is:");
error_log("Total Amount: $amount, GST Amount: $gst_amount (from udf4)");

/* -------------------------------------------------
   7. INSERT subscription_histories (NO GST CALCULATION)
------------------------------------------------- */
$insert = $pdo->prepare("
    INSERT INTO subscription_histories (
        invoice_number, plan_id, user_id, payment_method, payment_id,
        currency, currency_symbol, amount, gst_amount, gst_type, gst_number, gst_percentage, discount,
        name, email, phone,
        address_1, address_2, state, city, pin_code, country,
        created_at
    ) VALUES (
        :invoice, :plan_id, :user_id, 'payu', :payment_id,
        :currency, :currency_symbol, :amount, :gst_amount, :gst_type, :gst_number, :gst_percentage, :discount,
        :name, :email, :phone,
        :address_1, :address_2, :state, :city, :pin_code, :country,
        NOW()
    )
");

$insert->execute([
    ':invoice'         => $invoice_number,
    ':plan_id'         => $plan_id,
    ':user_id'         => $user_id,
    ':payment_id'      => $txnid,
    ':currency'        => $currency,
    ':currency_symbol' => $currency_symbol,
    ':amount'          => $amount,       // Store total amount as is
    ':gst_amount'      => $gst_amount,   // Store GST amount from udf4
    ':gst_type'        => $gst_type,
    ':gst_number'      => $gst_number,
    ':gst_percentage'  => $gst_percentage,
    ':discount'        => 0,
    ':name'            => $_POST['firstname'] ?? '',
    ':email'           => $_POST['email'] ?? '',
    ':phone'           => $_POST['phone'] ?? '',
    ':address_1'       => $_POST['address1'] ?? '',
    ':address_2'       => $_POST['address2'] ?? '',
    ':state'           => $_POST['state'] ?? '',
    ':city'            => $_POST['city'] ?? '',
    ':pin_code'        => $_POST['zipcode'] ?? '',
    ':country'         => $_POST['country'] ?? 'India'
]);

/* -------------------------------------------------
   8. UPDATE USERS TABLE (PLAN + EXPIRY) - same as before
------------------------------------------------- */
$stmt = $pdo->prepare("SELECT duration FROM subscription_plans WHERE id = ?");
$stmt->execute([$plan_id]);
$plan_duration = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan_duration) {
    error_log("PayU ERROR: Plan not found");
    header("Location: http://localhost:3000/payment-failed");
    exit;
}

$duration_days = (int) $plan_duration['duration'];

$stmt = $pdo->prepare("SELECT plan_id, expires_on FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$expires_on = new DateTime();

if (
    !empty($user['expires_on']) &&
    new DateTime($user['expires_on']) > new DateTime() &&
    (int)$user['plan_id'] === (int)$plan_id
) {
    $expires_on = new DateTime($user['expires_on']);
}

$expires_on->modify("+{$duration_days} days");

$update = $pdo->prepare("
    UPDATE users
    SET plan_id = ?, expires_on = ?
    WHERE user_id = ?
");

$update->execute([
    $plan_id,
    $expires_on->format("Y-m-d H:i:s"),
    $user_id
]);

if ($update->rowCount() === 0) {
    error_log("PayU WARNING: User update affected 0 rows (user_id={$user_id})");
}

/* -------------------------------------------------
   9. Log successful payment
------------------------------------------------- */
error_log("PayU SUCCESS: Invoice $invoice_number, Amount: $amount, GST Amount: $gst_amount");

/* -------------------------------------------------
   10. Redirect to frontend success page
------------------------------------------------- */
header("Location: http://localhost:3000/payment-success?invoice={$invoice_number}");
exit;