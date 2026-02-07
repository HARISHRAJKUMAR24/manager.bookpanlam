<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php"; // Include functions.php to use getCurrencySymbol()

$pdo = getDbConnection();

// Get settings including currency
$settingsSql = "SELECT currency, gst_percentage, gst_tax_type, app_name, address, gst_number FROM settings LIMIT 1";
$settingsStmt = $pdo->prepare($settingsSql);
$settingsStmt->execute();
$settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

// Get currency from settings
$currency = $settings['currency'] ?? 'INR';
$currencySymbol = getCurrencySymbol($currency);

// Get plans
$sql = "SELECT * FROM subscription_plans WHERE is_disabled = 1 ORDER BY amount ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to calculate display price based on GST type
function calculateDisplayPrice($amount, $gstRate, $gstType, $planGstType = null) {
    // If plan has its own gst_type, use that, otherwise use global gst_type
    $effectiveGstType = $planGstType ?: $gstType;
    
    if ($effectiveGstType === 'inclusive') {
        // Price in DB is already GST inclusive - show as is
        return [
            'display_price' => round($amount),
            'is_inclusive' => true,
            'gst_type' => 'inclusive'
        ];
    } else {
        // Price in DB is GST exclusive - add GST and round to nearest integer
        $gstAmount = ($amount * $gstRate) / 100;
        $finalPrice = $amount + $gstAmount;
        
        return [
            'display_price' => round($finalPrice),
            'is_inclusive' => false,
            'gst_type' => 'exclusive'
        ];
    }
}

// Process plans
foreach ($data as &$row) {
    // Process feature lists
    $list = $row["feature_lists"];
    $arr = array_filter(array_map('trim', explode(",", $list)));
    $row["feature_lists"] = $arr;
    
    // Calculate display price for current amount
    $gstRate = $settings['gst_percentage'] ?? 18;
    $gstType = $settings['gst_tax_type'] ?? 'exclusive';
    $planGstType = $row['gst_type'] ?? null;
    
    $priceCalc = calculateDisplayPrice($row['amount'], $gstRate, $gstType, $planGstType);
    
    // Add calculated price fields
    $row['display_price'] = $priceCalc['display_price'];
    $row['is_price_inclusive'] = $priceCalc['is_inclusive'];
    $row['gst_type'] = $priceCalc['gst_type'];
    
    // Send previous_amount as is (no GST calculation for previous amount)
    // If previous_amount exists, use it directly
    $row['previous_display_price'] = $row['previous_amount'] && $row['previous_amount'] > 0 
        ? round($row['previous_amount']) 
        : null;
    
    // Add payment gateway information
    $row['payment_gateways'] = [
        'razorpay' => (bool)$row['razorpay'],
        'phonepe' => (bool)$row['phonepe'],
        'payu' => (bool)$row['payu']
    ];
    
    // Remove individual gateway fields from response for cleaner output
    unset($row['razorpay'], $row['phonepe'], $row['payu']);
}

echo json_encode([
    "success" => true,
    "data" => $data,
    "currency_settings" => [
        "currency" => $currency,
        "currency_symbol" => $currencySymbol
    ],
    "company_settings" => [
        "app_name" => $settings['app_name'] ?? 'Book Pannu',
        "address" => $settings['address'] ?? '',
        "gst_number" => $settings['gst_number'] ?? ''
    ],
    "gst_settings" => [
        "gst_percentage" => $settings['gst_percentage'] ?? 18,
        "gst_tax_type" => $settings['gst_tax_type'] ?? 'exclusive'
    ]
]);