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

// Get company info from settings for currency
$settingsSql = "SELECT currency FROM settings LIMIT 1";
$settingsStmt = $pdo->prepare($settingsSql);
$settingsStmt->execute();
$companyInfo = $settingsStmt->fetch(PDO::FETCH_ASSOC);
$currency = $companyInfo['currency'] ?? 'INR';
$currency_symbol = getCurrencySymbol($currency);

// Get all subscription histories for this user
$sql = "SELECT 
            sh.*,
            sp.name as plan_name,
            sp.duration as plan_duration
        FROM subscription_histories sh
        LEFT JOIN subscription_plans sp ON sh.plan_id = sp.id
        WHERE sh.user_id = ?
        ORDER BY sh.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$histories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process each history to calculate proper GST breakdown
foreach ($histories as &$history) {
    $totalAmount = intval($history['amount']);
    $gstPercentage = intval($history['gst_percentage'] ?? 0);
    $gstType = $history['gst_type'] ?? 'inclusive';
    $gstAmount = intval($history['gst_amount'] ?? 0);
    $discount = intval($history['discount'] ?? 0);
    
    // Calculate GST based on type if not already calculated
    if ($gstType === 'exclusive' && $gstAmount === 0 && $gstPercentage > 0) {
        // GST = (Total Amount * GST%) / (100 + GST%)
        $gstAmount = round(($totalAmount * $gstPercentage) / (100 + $gstPercentage));
        $history['gst_amount'] = $gstAmount;
    }
    
    // Calculate GST components based on state
    $customerState = $history['state'] ?? '';
    $companyState = "Tamil Nadu"; // Company is in Tamil Nadu
    $isSameState = strcasecmp(trim($customerState), trim($companyState)) === 0;
    
    $sgstAmount = 0;
    $cgstAmount = 0;
    $igstAmount = 0;
    
    if ($gstType === 'exclusive' && $gstAmount > 0) {
        if ($isSameState) {
            // For same state: CGST + SGST (50% each)
            $sgstAmount = round($gstAmount / 2);
            $cgstAmount = $gstAmount - $sgstAmount;
        } else {
            // For different state: IGST (full amount)
            $igstAmount = $gstAmount;
        }
    }
    
    // Calculate base amount
    $baseAmount = $totalAmount - $gstAmount;
    $baseAmountAfterDiscount = $baseAmount - $discount;
    $finalTotal = $baseAmountAfterDiscount + $gstAmount;
    
    // Add calculated fields to the history
    $history['calculated_gst_amount'] = $gstAmount;
    $history['calculated_sgst'] = $sgstAmount;
    $history['calculated_cgst'] = $cgstAmount;
    $history['calculated_igst'] = $igstAmount;
    $history['calculated_base_amount'] = $baseAmountAfterDiscount;
    $history['calculated_final_total'] = $finalTotal;
    $history['currency_symbol'] = $currency_symbol;
    $history['is_same_state'] = $isSameState;
}

// Format the response with calculated GST fields
echo json_encode([
    "success" => true,
    "data" => $histories
]);