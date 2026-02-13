<?php
// manager.bookpanlam/public/seller/payment/get-purchase-history.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$pdo = getDbConnection();

// Auth by token
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

// First, check if user has ANY subscription history
$checkSubscription = $pdo->prepare("SELECT COUNT(*) as count FROM subscription_histories WHERE user_id = ?");
$checkSubscription->execute([$userId]);
$subscriptionCount = $checkSubscription->fetch(PDO::FETCH_ASSOC);

// If no subscription history at all - show purchase plan message
if (!$subscriptionCount || $subscriptionCount['count'] == 0) {
    echo json_encode([
        "success" => true,
        "data" => [],
        "count" => 0,
        "has_subscription" => false,
        "message" => "No subscription found"
    ]);
    exit;
}

// Check if customer payments exist
$checkPayments = $pdo->prepare("SELECT COUNT(*) as count FROM customer_payment WHERE user_id = ? AND status = 'paid'");
$checkPayments->execute([$userId]);
$paymentCount = $checkPayments->fetch(PDO::FETCH_ASSOC);
$hasCustomerPayments = ($paymentCount && $paymentCount['count'] > 0);

// If NO customer payments - return empty array (hide subscription invoices as per requirement)
if (!$hasCustomerPayments) {
    echo json_encode([
        "success" => true,
        "data" => [],
        "count" => 0,
        "has_subscription" => true,
        "has_customer_payments" => false,
        "message" => "No customer payments found - subscription invoices hidden"
    ]);
    exit;
}

// ONLY IF CUSTOMER PAYMENTS EXIST, get and show subscription history
$sql = "SELECT 
            sh.id,
            sh.invoice_number,
            COALESCE(sp.name, 'Subscription Plan') as plan_name,
            sh.amount,
            sh.payment_method,
            sh.payment_id,
            sh.created_at,
            sh.name as customer_name,
            sh.email as customer_email,
            '₹' as currency_symbol
        FROM subscription_histories sh
        LEFT JOIN subscription_plans sp ON sh.plan_id = sp.id
        WHERE sh.user_id = ?
        ORDER BY sh.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for frontend
$formatted = [];
foreach ($subscriptions as $sub) {
    $formatted[] = [
        'id' => (int)$sub['id'],
        'invoice_number' => (string)$sub['invoice_number'],
        'plan_name' => (string)$sub['plan_name'],
        'amount' => (int)$sub['amount'],
        'currency_symbol' => '₹',
        'payment_method' => (string)$sub['payment_method'],
        'payment_id' => (string)$sub['payment_id'],
        'created_at' => (string)$sub['created_at'],
        'status' => 'Paid',
        'name' => (string)$sub['customer_name'],
        'email' => (string)$sub['customer_email'],
        'purchase_type' => 'Subscription'
    ];
}

echo json_encode([
    "success" => true,
    "data" => $formatted,
    "count" => count($formatted),
    "has_subscription" => true,
    "has_customer_payments" => true,
    "debug" => [
        "user_id" => $userId,
        "subscription_exists" => true,
        "customer_payments_found" => $paymentCount['count'],
        "subscription_invoices_shown" => count($formatted)
    ]
]);