<?php
// manager.bookpanlam/public/seller/payment/get-payment-details.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$pdo = getDbConnection();

// Get invoice number from query parameter
$invoiceNumber = isset($_GET['invoice']) ? intval($_GET['invoice']) : 0;

if (!$invoiceNumber) {
    echo json_encode([
        "success" => false,
        "message" => "Invoice number is required"
    ]);
    exit;
}

// Get payment details from subscription_histories
$sql = "SELECT 
            sh.*,
            sp.name as plan_name,
            sp.duration as plan_duration,
            u.email as user_email,
            u.name as user_name,
            u.phone as user_phone
        FROM subscription_histories sh
        LEFT JOIN subscription_plans sp ON sh.plan_id = sp.id
        LEFT JOIN users u ON sh.user_id = u.user_id
        WHERE sh.invoice_number = ?";
    
$stmt = $pdo->prepare($sql);
$stmt->execute([$invoiceNumber]);
$paymentDetails = $stmt->fetch(PDO::FETCH_ASSOC);

// If no record found, create a mock response
if (!$paymentDetails) {
    // Get company info from settings
    $settingsSql = "SELECT 
                        app_name, 
                        address, 
                        logo, 
                        gst_number as company_gst_number,
                        currency,
                        razorpay_key_id,
                        razorpay_key_secret
                    FROM settings 
                    LIMIT 1";
    $settingsStmt = $pdo->prepare($settingsSql);
    $settingsStmt->execute();
    $companyInfo = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    
    $currency = $companyInfo['currency'] ?? 'INR';
    $currency_symbol = getCurrencySymbol($currency);
    
    // Create mock payment details
    $currentTime = date('Y-m-d H:i:s');
    $dueDate = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Get plan info if available from GET parameters
    $planId = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
    $planName = "Unknown Plan";
    
    if ($planId > 0) {
        $planSql = "SELECT name FROM subscription_plans WHERE id = ?";
        $planStmt = $pdo->prepare($planSql);
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        if ($plan) {
            $planName = $plan['name'];
        }
    }
    
    // Calculate GST based on exclusive plan - Correct calculation
    $totalAmount = 199; // Example total amount (inclusive of GST)
    $gstPercentage = 18;
    
    // For exclusive GST: GST = (Total Amount * GST%) / (100 + GST%)
    $gstAmount = round(($totalAmount * $gstPercentage) / (100 + $gstPercentage));
    $baseAmount = $totalAmount - $gstAmount;
    
    // GST breakdown (Tamil Nadu example)
    $customerState = "Tamil Nadu";
    $companyState = "Tamil Nadu";
    $isSameState = strcasecmp(trim($customerState), trim($companyState)) === 0;
    
    if ($isSameState) {
        // For same state: CGST + SGST (50% each)
        $sgstAmount = round($gstAmount / 2);
        $cgstAmount = $gstAmount - $sgstAmount;
        $igstAmount = 0;
    } else {
        // For different state: IGST (full amount)
        $sgstAmount = 0;
        $cgstAmount = 0;
        $igstAmount = $gstAmount;
    }
    
    echo json_encode([
        "success" => true,
        "data" => [
            "invoice" => [
                "invoice_number" => $invoiceNumber,
                "date" => $currentTime,
                "due_date" => $dueDate,
                "status" => "Paid"
            ],
            "company" => [
                "name" => "1Milestone Technology Solution Private Limited",
                "address" => "Tamilnadu, India",
                "email" => "admin@1milestonetech.com",
                "phone" => "+919363601020",
                "gst_number" => "33AACCZ2135N1Z8",
                "hsn" => "998315"
            ],
            "customer" => [
                "name" => "Customer",
                "email" => "customer@example.com",
                "phone" => "9876543210",
                "address_1" => "Address line 1",
                "address_2" => "",
                "city" => "City",
                "state" => $customerState,
                "pin_code" => "123456",
                "country" => "India",
                "gst_number" => ""
            ],
            "plan" => [
                "name" => $planName,
                "duration" => 30,
                "plan_id" => $planId
            ],
            "payment" => [
                "method" => "razorpay",
                "payment_id" => "pay_" . $invoiceNumber,
                "currency" => $currency,
                "currency_symbol" => $currency_symbol,
                "amount" => $baseAmount, // Base amount without GST
                "gst_amount" => $gstAmount,
                "gst_type" => "exclusive",
                "gst_percentage" => $gstPercentage,
                "discount" => 0,
                "total" => $totalAmount,
                "place_of_supply" => $customerState,
                "country_of_supply" => "India",
                "amount_in_words" => "One Hundred And Ninety Nine INR",
                "sgst_amount" => $sgstAmount,
                "cgst_amount" => $cgstAmount,
                "igst_amount" => $igstAmount
            ],
            "items" => [
                [
                    "description" => $planName . " Subscription",
                    "quantity" => 1,
                    "unit_price" => $totalAmount, // Show total price for the item
                    "total" => $totalAmount
                ]
            ]
        ]
    ]);
    exit;
}

// Get company info from settings
$settingsSql = "SELECT 
                    app_name, 
                    address, 
                    gst_number as company_gst_number,
                    currency,
                    razorpay_key_id,
                    razorpay_key_secret
                FROM settings 
                LIMIT 1";
$settingsStmt = $pdo->prepare($settingsSql);
$settingsStmt->execute();
$companyInfo = $settingsStmt->fetch(PDO::FETCH_ASSOC);

// Get currency symbol
$currency = $companyInfo['currency'] ?? 'INR';
$currency_symbol = getCurrencySymbol($currency);

// Calculate GST breakdown based on state
$customerState = $paymentDetails['state'] ?? '';
$companyState = "Tamil Nadu"; // Company is in Tamil Nadu
$isSameState = strcasecmp(trim($customerState), trim($companyState)) === 0;

$totalAmount = intval($paymentDetails['amount']); // Total amount received
$gstPercentage = intval($paymentDetails['gst_percentage']);
$discount = intval($paymentDetails['discount']);

// Calculate GST based on GST type
$gstAmount = 0;
$baseAmount = 0;

if ($paymentDetails['gst_type'] === 'exclusive') {
    // For exclusive GST: Calculate GST from total amount
    // GST = (Total Amount * GST%) / (100 + GST%)
    $gstAmount = round(($totalAmount * $gstPercentage) / (100 + $gstPercentage));
    $baseAmount = $totalAmount - $gstAmount;
} else {
    // For inclusive GST: GST is already included in the amount
    $gstAmount = intval($paymentDetails['gst_amount']);
    $baseAmount = $totalAmount - $gstAmount;
}

// Calculate GST components
$sgstAmount = 0;
$cgstAmount = 0;
$igstAmount = 0;

if ($paymentDetails['gst_type'] === 'exclusive' && $gstAmount > 0) {
    if ($isSameState) {
        // For same state: SGST + CGST (50% each)
        $sgstAmount = round($gstAmount / 2);
        $cgstAmount = $gstAmount - $sgstAmount;
    } else {
        // For different state: IGST (full amount)
        $igstAmount = $gstAmount;
    }
}

// Apply discount to base amount
$baseAmountAfterDiscount = $baseAmount - $discount;

// Calculate final total
$finalTotal = $baseAmountAfterDiscount + $gstAmount;

// Amount in words function
function amountInWords($number) {
    $no = round($number);
    $point = round($number - $no, 2) * 100;
    
    // Special case for zero
    if ($no == 0) {
        return "Zero INR";
    }
    
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four',
        '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen',
        '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen',
        '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen',
        '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
        '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety'
    );
    
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] .
                " " . $digits[$counter] . $plural . " " . $hundred
                :
                $words[floor($number / 10) * 10]
                . " " . $words[$number % 10] . " "
                . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    
    $str = array_reverse($str);
    $result = implode('', $str);
    
    // Remove extra spaces
    $result = preg_replace('/\s+/', ' ', trim($result));
    
    // Add currency
    $points = ($point > 0) ? " and " . $words[$point] . " Paise" : "";
    
    return ucfirst($result) . " INR" . $points;
}

$amountInWords = amountInWords($finalTotal);

// Format the response
$formattedResponse = [
    "success" => true,
    "data" => [
        "invoice" => [
            "invoice_number" => $paymentDetails['invoice_number'],
            "date" => $paymentDetails['created_at'],
            "due_date" => date('Y-m-d H:i:s', strtotime($paymentDetails['created_at'] . ' + 30 days')),
            "status" => "Paid"
        ],
        "company" => [
            "name" => "1Milestone Technology Solution Private Limited",
            "address" => "Tamilnadu, India",
            "email" => "admin@1milestonetech.com",
            "phone" => "+919363601020",
            "gst_number" => "33AACCZ2135N1Z8",
            "hsn" => "998315"
        ],
        "customer" => [
            "name" => $paymentDetails['name'],
            "email" => $paymentDetails['email'],
            "phone" => $paymentDetails['phone'],
            "address_1" => $paymentDetails['address_1'],
            "address_2" => $paymentDetails['address_2'],
            "city" => $paymentDetails['city'],
            "state" => $paymentDetails['state'],
            "pin_code" => $paymentDetails['pin_code'],
            "country" => $paymentDetails['country'],
            "gst_number" => $paymentDetails['gst_number'] ?? ""
        ],
        "plan" => [
            "name" => $paymentDetails['plan_name'],
            "duration" => $paymentDetails['plan_duration'],
            "plan_id" => $paymentDetails['plan_id']
        ],
        "payment" => [
            "method" => $paymentDetails['payment_method'],
            "payment_id" => $paymentDetails['payment_id'],
            "currency" => $paymentDetails['currency'] ?? $currency,
            "currency_symbol" => $paymentDetails['currency_symbol'] ?? $currency_symbol,
            "amount" => $baseAmountAfterDiscount, // Base amount after discount (without GST)
            "gst_amount" => $gstAmount,
            "gst_type" => $paymentDetails['gst_type'],
            "gst_percentage" => $gstPercentage,
            "discount" => $discount,
            "total" => $finalTotal,
            "place_of_supply" => $paymentDetails['state'] ?? "Tamil Nadu",
            "country_of_supply" => $paymentDetails['country'] ?? "India",
            "amount_in_words" => $amountInWords,
            "sgst_amount" => $sgstAmount,
            "cgst_amount" => $cgstAmount,
            "igst_amount" => $igstAmount,
            "subtotal" => $baseAmountAfterDiscount
        ],
        "items" => [
            [
                "description" => $paymentDetails['plan_name'] . " Subscription",
                "quantity" => 1,
                "unit_price" => $finalTotal, // Show total price
                "total" => $finalTotal
            ]
        ]
    ]
];

echo json_encode($formattedResponse);
exit;