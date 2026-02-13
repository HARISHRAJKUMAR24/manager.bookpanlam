<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Clean output buffer
while (ob_get_level()) {
    ob_end_clean();
}

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once "../../../config/config.php";
    require_once "../../../src/database.php";
    require_once "../../../src/functions.php";
    require_once "../../../vendor/fpdf/fpdf.php";

    $pdo = getDbConnection();

    // ================= AUTH =================
    $token = $_COOKIE["token"] ?? null;

    if (!$token) {
        throw new Exception("Unauthorized");
    }

    $stmt = $pdo->prepare("SELECT user_id, email FROM users WHERE api_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Invalid token");
    }

    $userId = $user["user_id"];
    $userEmail = $user["email"] ?? "N/A";

    // ================= INVOICE NUMBER =================
    $invoiceNumber = isset($_GET['invoice']) ? intval($_GET['invoice']) : 0;

    if (!$invoiceNumber) {
        throw new Exception("Invoice number is required");
    }

    // ================= COMPANY INFO =================
    $companyName = "1Milestone Technology Solution Private Limited";
    $companyAddress = "Tamilnadu, India";
    $companyEmail = "admin@1milestonetech.com";
    $companyPhone = "+919363601020";
    $companyGST = "33AACCZ2135N1Z8";
    $companyHSN = "998315";
    $currency_symbol = "RS";

    // ================= INVOICE DATA =================
    $sql = "SELECT 
                sh.invoice_number,
                sh.plan_id,
                sh.payment_method,
                sh.payment_id,
                sh.amount,
                sh.gst_amount,
                sh.gst_type,
                sh.gst_percentage,
                sh.gst_number,
                sh.discount,
                sh.name,
                sh.phone,
                sh.address_1,
                sh.address_2,
                sh.state,
                sh.city,
                sh.pin_code,
                sh.country,
                sh.created_at,
                sp.name as plan_name,
                sp.duration as plan_duration
            FROM subscription_histories sh
            LEFT JOIN subscription_plans sp ON sh.plan_id = sp.id
            WHERE sh.invoice_number = ? AND sh.user_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invoiceNumber, $userId]);
    $history = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$history) {
        throw new Exception("Invoice not found");
    }

    // ================= GST CALCULATIONS =================
    $totalAmount = intval($history['amount'] ?? 0);
    $gstPercentage = intval($history['gst_percentage'] ?? 0);
    $gstType = $history['gst_type'] ?? 'inclusive';
    $gstAmount = intval($history['gst_amount'] ?? 0);
    $discount = intval($history['discount'] ?? 0);

    $subtotal = $totalAmount - $gstAmount;
    $finalTotal = $totalAmount;

    if ($discount > 0) {
        $subtotal = $subtotal - $discount;
        $finalTotal = $finalTotal - $discount;
    }

    // Check if same state for GST breakdown
    $customerState = $history['state'] ?? '';
    $companyState = "Tamil Nadu";
    $isSameState = strcasecmp(trim($customerState), trim($companyState)) === 0;

    // Calculate GST components
    $sgstAmount = 0;
    $cgstAmount = 0;
    $igstAmount = 0;

    if ($gstType === 'exclusive' && $gstAmount > 0) {
        if ($isSameState) {
            $sgstAmount = round($gstAmount / 2);
            $cgstAmount = $gstAmount - $sgstAmount;
        } else {
            $igstAmount = $gstAmount;
        }
    }

    // ================= DATES =================
    $createdDate = new DateTime($history['created_at'] ?? date("Y-m-d"));
    $createdFormatted = $createdDate->format("d M Y");
    $dueDate = clone $createdDate;
    $dueDate->modify("+30 days");
    $dueFormatted = $dueDate->format("d M Y");
    $expiryDate = clone $createdDate;
    $expiryDate->modify("+" . intval($history['plan_duration'] ?? 30) . " days");
    $expiryFormatted = $expiryDate->format("d M Y");

    // ================= AMOUNT IN WORDS =================
    function amountInWords($number)
    {
        $no = round($number);
        $point = round($number - $no, 2) * 100;

        if ($no == 0) {
            return "Zero Rupees";
        }

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
        $digits_1 = strlen($no);
        $i = 0;
        $str = array();

        while ($i < $digits_1) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;

            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred
                    : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
            } else $str[] = null;
        }

        $str = array_reverse($str);
        $result = implode('', $str);
        $result = preg_replace('/\s+/', ' ', trim($result));
        $points = ($point > 0) ? " and " . ($point < 21 ? $words[$point] : $words[floor($point / 10) * 10] . " " . $words[$point % 10]) . " Paise" : "";

        return ucfirst($result) . " Rupees" . $points . " Only";
    }

    $amountInWords = amountInWords($finalTotal);

    // ================= CREATE PDF =================
    class PDF extends FPDF
    {
        function Header()
        {
            // No header needed
        }
        function Footer()
        {
            // No footer needed
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->SetMargins(20, 15, 20);
    $pdf->SetFont('Arial', '', 10);

    // ========== SECTION 1: Company Header ==========
    $pdf->SetFont('Arial', 'B', 22);
    $pdf->SetTextColor(41, 71, 121); // Dark blue
    $pdf->Cell(0, 12, $companyName, 0, 1, 'C');

    // Elegant divider line
    $pdf->SetDrawColor(70, 130, 200); // Steel blue
    $pdf->SetLineWidth(0.3);
    $lineWidth = 100;
    $pdf->Line(($pdf->GetPageWidth() - $lineWidth) / 2, $pdf->GetY(), ($pdf->GetPageWidth() + $lineWidth) / 2, $pdf->GetY());
    $pdf->Ln(3);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, $companyAddress, 0, 1, 'C');
    $pdf->Ln(8);
    $pdf->SetTextColor(0, 0, 0);

    // ========== SECTION 2: Payment Status and Invoice Details ==========
    $leftColX = 20;
    $rightColX = 125;
    $startY = $pdf->GetY();

    // Left side - Payment Status with badge style
    $pdf->SetXY($leftColX, $startY);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(35, 6, 'Payment Status:', 0, 0);

    // PAID badge
    $pdf->SetFillColor(39, 174, 96); // Green
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(20, 6, 'PAID', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);

    // Other payment details
    $pdf->SetX($leftColX);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(35, 6, 'Place of Supply:', 0, 0);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, $history['state'] ?? 'Delhi', 0, 1);

    $pdf->SetX($leftColX);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(35, 6, 'Country of Supply:', 0, 0);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, $history['country'] ?? 'India', 0, 1);

    $pdf->SetX($leftColX);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(35, 6, 'Payment Method:', 0, 0);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, strtoupper($history['payment_method'] ?? 'RAZORPAY'), 0, 1);

    $pdf->SetX($leftColX);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(35, 6, 'Payment ID:', 0, 0);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 6, $history['payment_id'] ?? 'pay_SFL8pzvDkHhgFe', 0, 1);
    $pdf->SetFont('Arial', '', 9);

    // Right side - Invoice Details
    $pdf->SetXY($rightColX, $startY);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(41, 71, 121);
    $pdf->Cell(0, 10, 'INVOICE #' . $invoiceNumber, 0, 1, 'R');

    // IMPORTANT: Reset font here
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0, 0, 0);

// Date
$pdf->SetX($rightColX);
$pdf->Cell(0, 6, 'Date: ' . $createdFormatted, 0, 1, 'R');

// Due Date
$pdf->SetX($rightColX);
$pdf->Cell(0, 6, 'Due Date: ' . $dueFormatted, 0, 1, 'R');

    $pdf->Ln(12);

    // ========== SECTION 3: Company and Customer Information Side by Side ==========

$currentY = $pdf->GetY();
$leftColX = 20;
$rightColX = 115;

// ----- LEFT COLUMN: COMPANY INFORMATION -----
$pdf->SetXY($leftColX, $currentY);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(41, 71, 121);
$pdf->Cell(45, 8, 'Company Information', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$companyBoxY = $pdf->GetY();

// ----- RIGHT COLUMN TITLE -----
$pdf->SetXY($rightColX, $currentY);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(41, 71, 121);
$pdf->Cell(45, 8, 'Customer Information', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$customerBoxY = $pdf->GetY();


// ===== CALCULATE BOX HEIGHTS =====

$companyBoxHeight = 48; // fixed company content height

$customerBoxHeight = 62;
if (!empty($history['address_2'])) $customerBoxHeight += 5;
if (!empty($history['gst_number'])) $customerBoxHeight += 5;

// Use same height for both
$boxHeight = max($companyBoxHeight, $customerBoxHeight);


// ===== DRAW BOTH BOXES =====

$pdf->SetDrawColor(200, 200, 200);
$pdf->SetFillColor(250, 250, 252);

$pdf->Rect($leftColX, $companyBoxY, 88, $boxHeight, 'FD');
$pdf->Rect($rightColX, $customerBoxY, 75, $boxHeight, 'FD');


// ===== COMPANY CONTENT =====

$pdf->SetXY($leftColX + 5, $companyBoxY + 5);

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Name:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $companyName, 0, 1);

$pdf->SetX($leftColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Address:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $companyAddress, 0, 1);

$pdf->SetX($leftColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Email:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $companyEmail, 0, 1);

$pdf->SetX($leftColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Phone:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $companyPhone, 0, 1);

$pdf->SetX($leftColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'GST:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $companyGST, 0, 1);

$pdf->SetX($leftColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'HSN:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $companyHSN, 0, 1);


// ===== CUSTOMER CONTENT =====

$pdf->SetXY($rightColX + 5, $customerBoxY + 5);

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Name:', 0, 0);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $history['name'], 0, 1);

$pdf->SetX($rightColX + 5);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Address:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(52, 5, $history['address_1']);


if (!empty($history['address_2'])) {
    $pdf->SetX($rightColX + 23);
    $pdf->Cell(0, 5, $history['address_2'], 0, 1);
}

$pdf->SetX($rightColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'City:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $history['city'], 0, 1);

$pdf->SetX($rightColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'State:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $history['state'], 0, 1);

$pdf->SetX($rightColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Pin Code:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $history['pin_code'], 0, 1);

$pdf->SetX($rightColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Country:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $history['country'], 0, 1);

$pdf->SetX($rightColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Email:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $userEmail, 0, 1);

$pdf->SetX($rightColX + 5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(18, 5, 'Phone:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $history['phone'], 0, 1);

if (!empty($history['gst_number'])) {
    $pdf->SetX($rightColX + 5);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(18, 5, 'GST:', 0, 0);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5, $history['gst_number'], 0, 1);
}


// ===== MOVE BELOW BOXES =====

$pdf->SetY($companyBoxY + $boxHeight + 15);


    // ========== SECTION 5: Amount Summary ==========
    $summaryX = $pdf->GetPageWidth() - 95;
    $pdf->SetX($summaryX);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(41, 71, 121); // Dark blue
    $pdf->Cell(75, 8, 'Amount Summary', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);

    // Subtotal
    $pdf->SetX($summaryX);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(45, 7, 'Subtotal:', 0, 0, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(30, 7, $currency_symbol . ' ' . number_format($subtotal, 2), 0, 1, 'R');

    // Discount
    if ($discount > 0) {
        $pdf->SetX($summaryX);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(45, 7, 'Discount:', 0, 0, 'L');
        $pdf->SetTextColor(39, 174, 96); // Green
        $pdf->Cell(30, 7, '-' . $currency_symbol . ' ' . number_format($discount, 2), 0, 1, 'R');
        $pdf->SetTextColor(0, 0, 0);
    }

    // GST Breakdown
    if ($gstAmount > 0) {
        if ($gstType === 'exclusive' && $isSameState && $cgstAmount > 0 && $sgstAmount > 0) {
            $pdf->SetX($summaryX);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->Cell(45, 7, 'CGST (' . ($gstPercentage / 2) . '%):', 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(30, 7, $currency_symbol . ' ' . number_format($cgstAmount, 2), 0, 1, 'R');

            $pdf->SetX($summaryX);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->Cell(45, 7, 'SGST (' . ($gstPercentage / 2) . '%):', 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(30, 7, $currency_symbol . ' ' . number_format($sgstAmount, 2), 0, 1, 'R');
        } elseif ($gstType === 'exclusive' && !$isSameState && $igstAmount > 0) {
            $pdf->SetX($summaryX);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->Cell(45, 7, 'IGST (' . $gstPercentage . '%):', 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(30, 7, $currency_symbol . ' ' . number_format($igstAmount, 2), 0, 1, 'R');
        } else {
            $pdf->SetX($summaryX);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->Cell(45, 7, 'GST (' . $gstPercentage . '%):', 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(30, 7, $currency_symbol . ' ' . number_format($gstAmount, 2), 0, 1, 'R');
        }
    }

    // Divider line
    $pdf->Ln(2);
    $pdf->SetX($summaryX);
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX() + 75, $pdf->GetY());
    $pdf->Ln(4);

    // Total Amount
    $pdf->SetX($summaryX);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(45, 8, 'Total Amount:', 0, 0, 'L');
    $pdf->SetTextColor(41, 71, 121); // Dark blue
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(30, 8, $currency_symbol . ' ' . number_format($finalTotal, 2), 0, 1, 'R');
    $pdf->SetTextColor(0, 0, 0);

    // ========== SECTION 6: Footer ==========
    $pdf->Ln(15);
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetPageWidth() - 20, $pdf->GetY());
    $pdf->Ln(8);

    // Amount in words
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(38, 6, 'Amount In Words:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCell(0, 6, $amountInWords, 0, 'L');
    $pdf->Ln(4);

    // Computer generated notice
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 4, 'This is a computer generated invoice. No signature is required.', 0, 1, 'C');
    $pdf->Ln(8);

    // Thank you message
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(41, 71, 121); // Dark blue
    $pdf->Cell(0, 10, 'Thank you for your business!', 0, 1, 'C');

    // ========== OUTPUT PDF ==========
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice-' . $invoiceNumber . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    $pdf->Output('D', 'invoice-' . $invoiceNumber . '.pdf');
    exit;
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
    exit;
}
