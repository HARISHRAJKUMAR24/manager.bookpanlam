<?php
// manager.bookpanlam/public/seller/payment/generate-invoice-pdf.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: text/html");

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

// Get invoice number from query parameter
$invoiceNumber = isset($_GET['invoice']) ? intval($_GET['invoice']) : 0;

if (!$invoiceNumber) {
    echo json_encode(["success" => false, "message" => "Invoice number is required"]);
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
        WHERE sh.invoice_number = ? AND sh.user_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$invoiceNumber, $userId]);
$paymentDetails = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paymentDetails) {
    echo json_encode(["success" => false, "message" => "Invoice not found"]);
    exit;
}

// Get company info from settings
$settingsSql = "SELECT 
                    app_name, 
                    address, 
                    logo, 
                    gst_number as company_gst_number,
                    currency,
                    email,
                    phone
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
$companyState = "Tamil Nadu";
$isSameState = strcasecmp(trim($customerState), trim($companyState)) === 0;

$totalAmount = intval($paymentDetails['amount']);
$gstPercentage = intval($paymentDetails['gst_percentage']);
$discount = intval($paymentDetails['discount']);

// Calculate GST based on GST type
$gstAmount = 0;
$baseAmount = 0;

if ($paymentDetails['gst_type'] === 'exclusive') {
    $gstAmount = round(($totalAmount * $gstPercentage) / (100 + $gstPercentage));
    $baseAmount = $totalAmount - $gstAmount;
} else {
    $gstAmount = intval($paymentDetails['gst_amount']);
    $baseAmount = $totalAmount - $gstAmount;
}

// Calculate GST components
$sgstAmount = 0;
$cgstAmount = 0;
$igstAmount = 0;

if ($paymentDetails['gst_type'] === 'exclusive' && $gstAmount > 0) {
    if ($isSameState) {
        $sgstAmount = round($gstAmount / 2);
        $cgstAmount = $gstAmount - $sgstAmount;
    } else {
        $igstAmount = $gstAmount;
    }
}

// Apply discount to base amount
$baseAmountAfterDiscount = $baseAmount - $discount;
$finalTotal = $baseAmountAfterDiscount + $gstAmount;

// Amount in words
function amountInWords($number)
{
    $no = round($number);
    $point = round($number - $no, 2) * 100;

    if ($no == 0) {
        return "Zero INR";
    }

    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        '0' => '',
        '1' => 'One',
        '2' => 'Two',
        '3' => 'Three',
        '4' => 'Four',
        '5' => 'Five',
        '6' => 'Six',
        '7' => 'Seven',
        '8' => 'Eight',
        '9' => 'Nine',
        '10' => 'Ten',
        '11' => 'Eleven',
        '12' => 'Twelve',
        '13' => 'Thirteen',
        '14' => 'Fourteen',
        '15' => 'Fifteen',
        '16' => 'Sixteen',
        '17' => 'Seventeen',
        '18' => 'Eighteen',
        '19' => 'Nineteen',
        '20' => 'Twenty',
        '30' => 'Thirty',
        '40' => 'Forty',
        '50' => 'Fifty',
        '60' => 'Sixty',
        '70' => 'Seventy',
        '80' => 'Eighty',
        '90' => 'Ninety'
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
            $str[] = ($number < 21) ? $words[$number] .
                " " . $digits[$counter] . $plural . " " . $hundred
                :
                $words[floor($number / 10) * 10]
                . " " . $words[$number % 10] . " "
                . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }

    $str = array_reverse($str);
    $result = implode('', $str);
    $result = preg_replace('/\s+/', ' ', trim($result));
    $points = ($point > 0) ? " and " . $words[$point] . " Paise" : "";

    return ucfirst($result) . " INR" . $points;
}

$amountInWords = amountInWords($finalTotal);

// Calculate expiry date
$startDate = new DateTime($paymentDetails['created_at']);
$expiryDate = clone $startDate;
$expiryDate->modify('+' . $paymentDetails['plan_duration'] . ' days');

// Set filename for download
$filename = "invoice-{$invoiceNumber}.pdf";

// HTML Template for PDF
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $invoiceNumber; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background: white;
            padding: 40px;
            color: #1e293b;
        }

        .invoice-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .divider {
            width: 200px;
            height: 1px;
            background: #cbd5e1;
            margin: 15px auto;
        }

        .company-address {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            font-size: 14px;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .info-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 20px;
            height: 100%;
        }

        .info-row {
            margin-bottom: 8px;
            font-size: 13px;
        }

        .info-label {
            font-weight: bold;
            color: #475569;
            display: inline-block;
            width: 80px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .table th {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #1e293b;
        }

        .table td {
            border: 1px solid #cbd5e1;
            padding: 12px;
            font-size: 14px;
        }

        .amount-right {
            text-align: right;
        }

        .summary-table {
            width: 100%;
            margin-top: 20px;
        }

        .summary-table td {
            padding: 8px;
        }

        .total-row {
            border-top: 1px solid #94a3b8;
            font-weight: bold;
        }

        .grand-total {
            font-size: 18px;
            color: #2563eb;
        }

        .amount-words {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 15px;
            margin: 30px 0;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            border-top: 1px solid #cbd5e1;
            padding-top: 30px;
            margin-top: 30px;
        }

        .footer-text {
            font-style: italic;
            color: #64748b;
            margin-bottom: 10px;
        }

        .thankyou {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
        }

        @media print {
            body {
                padding: 20px;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Company Name -->
        <div class="text-center">
            <div class="company-name"><?php echo $companyInfo['app_name'] ?? '1Milestone Technology Solution Private Limited'; ?></div>
            <div class="divider"></div>
            <div class="company-address"><?php echo $companyInfo['address'] ?? 'Tamilnadu, India'; ?></div>
        </div>

        <!-- Status and Invoice Details -->
        <table width="100%" cellpadding="5" cellspacing="0">
            <tr>
                <td width="50%" valign="top">
                    <table cellpadding="3" cellspacing="0">
                        <tr>
                            <td width="120"><strong>Payment Status:</strong></td>
                            <td><span class="status-paid">PAID</span></td>
                        </tr>
                        <tr>
                            <td><strong>Place of Supply:</strong></td>
                            <td><?php echo $paymentDetails['state'] ?? 'Tamil Nadu'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Country of Supply:</strong></td>
                            <td>India</td>
                        </tr>
                        <tr>
                            <td><strong>Payment Method:</strong></td>
                            <td><?php echo strtoupper($paymentDetails['payment_method']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Payment ID:</strong></td>
                            <td style="font-family: monospace;"><?php echo $paymentDetails['payment_id']; ?></td>
                        </tr>
                    </table>
                </td>
                <td width="50%" valign="top" class="text-right">
                    <div class="invoice-title">INVOICE #<?php echo $invoiceNumber; ?></div>
                    <table cellpadding="3" cellspacing="0" style="float: right;">
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td><?php echo date('d M Y', strtotime($paymentDetails['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Due Date:</strong></td>
                            <td><?php echo date('d M Y', strtotime('+30 days', strtotime($paymentDetails['created_at']))); ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="height: 20px;"></div>

        <!-- Company and Customer Info -->
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%" style="padding-right: 10px;" valign="top">
                    <div class="info-box">
                        <div class="section-title">Company Information</div>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <?php echo $companyInfo['app_name'] ?? '1Milestone Technology Solution Private Limited'; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <?php echo $companyInfo['address'] ?? 'Tamilnadu, India'; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <?php echo $companyInfo['email'] ?? 'admin@1milestonetech.com'; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <?php echo $companyInfo['phone'] ?? '+919363601020'; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">GST:</span>
                            <?php echo $companyInfo['company_gst_number'] ?? '33AACCZ2135N1Z8'; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">HSN:</span>
                            998315
                        </div>
                    </div>
                </td>
                <td width="50%" style="padding-left: 10px;" valign="top">
                    <div class="info-box">
                        <div class="section-title">Customer Information</div>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <?php echo $paymentDetails['name']; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <?php echo $paymentDetails['address_1']; ?>
                        </div>
                        <?php if ($paymentDetails['address_2']): ?>
                            <div class="info-row">
                                <span class="info-label">Address 2:</span>
                                <?php echo $paymentDetails['address_2']; ?>
                            </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">City:</span>
                            <?php echo $paymentDetails['city']; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">State:</span>
                            <?php echo $paymentDetails['state']; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Postal:</span>
                            <?php echo $paymentDetails['pin_code']; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Country:</span>
                            <?php echo $paymentDetails['country']; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <?php echo $paymentDetails['email']; ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <?php echo $paymentDetails['phone']; ?>
                        </div>
                        <?php if ($paymentDetails['gst_number']): ?>
                            <div class="info-row">
                                <span class="info-label">GST:</span>
                                <?php echo $paymentDetails['gst_number']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50%;">Plan Name</th>
                    <th style="width: 25%;">Expiry Date</th>
                    <th style="width: 25%;" class="amount-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $paymentDetails['plan_name'] ?? 'Subscription Plan'; ?></td>
                    <td><?php echo $expiryDate->format('d M Y'); ?></td>
                    <td class="amount-right"><?php echo $currency_symbol . number_format($finalTotal); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Amount Summary -->
        <table class="summary-table" style="float: right; width: 300px;">
            <tr>
                <td><strong>Subtotal</strong></td>
                <td class="amount-right"><?php echo $currency_symbol . number_format($baseAmountAfterDiscount); ?></td>
            </tr>

            <?php if ($discount > 0): ?>
                <tr>
                    <td><strong>Discount</strong></td>
                    <td class="amount-right" style="color: #16a34a;">-<?php echo $currency_symbol . number_format($discount); ?></td>
                </tr>
            <?php endif; ?>

            <?php if ($paymentDetails['gst_type'] === 'exclusive' && $gstAmount > 0): ?>
                <?php if ($cgstAmount > 0 && $sgstAmount > 0): ?>
                    <tr>
                        <td>CGST (<?php echo $gstPercentage / 2; ?>%)</td>
                        <td class="amount-right"><?php echo $currency_symbol . number_format($cgstAmount); ?></td>
                    </tr>
                    <tr>
                        <td>SGST (<?php echo $gstPercentage / 2; ?>%)</td>
                        <td class="amount-right"><?php echo $currency_symbol . number_format($sgstAmount); ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ($igstAmount > 0): ?>
                    <tr>
                        <td>IGST (<?php echo $gstPercentage; ?>%)</td>
                        <td class="amount-right"><?php echo $currency_symbol . number_format($igstAmount); ?></td>
                    </tr>
                <?php endif; ?>

                <tr style="border-top: 1px solid #cbd5e1;">
                    <td><strong>Total GST</strong></td>
                    <td class="amount-right"><strong><?php echo $currency_symbol . number_format($gstAmount); ?></strong></td>
                </tr>
            <?php endif; ?>

            <?php if ($paymentDetails['gst_type'] === 'inclusive' && $gstAmount > 0): ?>
                <tr>
                    <td>GST (<?php echo $gstPercentage; ?>% Inclusive)</td>
                    <td class="amount-right"><?php echo $currency_symbol . number_format($gstAmount); ?></td>
                </tr>
            <?php endif; ?>

            <tr style="border-top: 2px solid #94a3b8;">
                <td><strong style="font-size: 16px;">Total Amount</strong></td>
                <td class="amount-right"><strong style="font-size: 16px; color: #2563eb;"><?php echo $currency_symbol . number_format($finalTotal); ?></strong></td>
            </tr>
        </table>

        <div style="clear: both;"></div>

        <!-- Amount in Words -->
        <div class="amount-words">
            <strong>Amount In Words:</strong> <?php echo $amountInWords; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                This is a computer generated invoice and does not require a physical signature.
            </div>
            <div class="thankyou">
                Thank you for your business!
            </div>
        </div>
    </div>

    <!-- Print Script -->
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>

</html>
<?php
?>