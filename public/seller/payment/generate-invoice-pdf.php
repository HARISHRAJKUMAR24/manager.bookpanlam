<?php
// manager.bookpanlam/public/seller/payment/generate-invoice-pdf.php
require_once "../../../config/config.php";
require_once "../../../src/database.php";

// Include TCPDF library (you need to install it)
// For now, we'll create a simple HTML to PDF conversion
// You can use a library like TCPDF, DomPDF, or mPDF

$pdo = getDbConnection();

// Get invoice number from query parameter
$invoiceNumber = isset($_GET['invoice']) ? intval($_GET['invoice']) : 0;

if (!$invoiceNumber) {
    die("Invoice number is required");
}

// Get payment details
$sql = "SELECT 
            sh.*,
            sp.name as plan_name,
            u.email as user_email,
            u.name as user_name
        FROM subscription_histories sh
        LEFT JOIN subscription_plans sp ON sh.plan_id = sp.id
        LEFT JOIN users u ON sh.user_id = u.id
        WHERE sh.invoice_number = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$invoiceNumber]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Payment details not found");
}

// Get company info
$settingsSql = "SELECT app_name, address, logo, gst_number FROM settings LIMIT 1";
$settingsStmt = $pdo->prepare($settingsSql);
$settingsStmt->execute();
$company = $settingsStmt->fetch(PDO::FETCH_ASSOC);

// Calculate dates
$invoiceDate = new DateTime($payment['created_at']);
$dueDate = clone $invoiceDate;
$dueDate->modify('+30 days');

// Format amounts
$subtotal = intval($payment['amount']);
$gstAmount = intval($payment['gst_amount']);
$discount = intval($payment['discount']);
$total = $subtotal + $gstAmount - $discount;

// Create HTML invoice
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #' . $invoiceNumber . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        .invoice-container { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .company-info h1 { margin: 0; color: #3B82F6; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0; color: #666; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .section { margin-bottom: 20px; }
        .section h3 { border-bottom: 2px solid #3B82F6; padding-bottom: 5px; margin-bottom: 15px; color: #3B82F6; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #3B82F6; color: white; padding: 12px; text-align: left; }
        .items-table td { padding: 12px; border-bottom: 1px solid #ddd; }
        .total-section { text-align: right; }
        .total-row { display: flex; justify-content: flex-end; margin-bottom: 10px; }
        .total-label { width: 150px; text-align: right; padding-right: 20px; }
        .total-value { width: 150px; text-align: right; font-weight: bold; }
        .grand-total { font-size: 18px; color: #3B82F6; border-top: 2px solid #3B82F6; padding-top: 10px; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px; }
        .gst-section { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px; }
        .no-gst { color: #666; font-style: italic; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <h1>' . htmlspecialchars($company['app_name'] ?? 'Book Pannu') . '</h1>
                <p>' . nl2br(htmlspecialchars($company['address'] ?? '')) . '</p>';
                
if (!empty($company['gst_number'])) {
    $html .= '<p><strong>GSTIN:</strong> ' . htmlspecialchars($company['gst_number']) . '</p>';
}

$html .= '
            </div>
            <div class="invoice-title">
                <h2>TAX INVOICE</h2>
                <p><strong>Invoice #:</strong> ' . $invoiceNumber . '</p>
                <p><strong>Date:</strong> ' . $invoiceDate->format('d/m/Y') . '</p>
                <p><strong>Due Date:</strong> ' . $dueDate->format('d/m/Y') . '</p>
            </div>
        </div>

        <div class="details-grid">
            <div class="section">
                <h3>Bill To</h3>
                <p><strong>' . htmlspecialchars($payment['name']) . '</strong></p>
                <p>' . htmlspecialchars($payment['email']) . '</p>
                <p>' . htmlspecialchars($payment['phone']) . '</p>
                <p>' . htmlspecialchars($payment['address_1']) . '</p>';
                
if (!empty($payment['address_2'])) {
    $html .= '<p>' . htmlspecialchars($payment['address_2']) . '</p>';
}

$html .= '
                <p>' . htmlspecialchars($payment['city']) . ', ' . htmlspecialchars($payment['state']) . ' - ' . htmlspecialchars($payment['pin_code']) . '</p>
                <p>' . htmlspecialchars($payment['country']) . '</p>';

// Show GSTIN only if provided
if (!empty($payment['gst_number'])) {
    $html .= '<div class="gst-section"><strong>GSTIN:</strong> ' . htmlspecialchars($payment['gst_number']) . '</div>';
} else {
    $html .= '<div class="gst-section no-gst">GSTIN: Not provided</div>';
}

$html .= '
            </div>

            <div class="section">
                <h3>Payment Details</h3>
                <p><strong>Payment Method:</strong> ' . htmlspecialchars($payment['payment_method']) . '</p>
                <p><strong>Payment ID:</strong> ' . htmlspecialchars($payment['payment_id']) . '</p>
                <p><strong>Plan:</strong> ' . htmlspecialchars($payment['plan_name']) . '</p>
                <p><strong>Status:</strong> <span style="color: green;">PAID</span></p>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>' . htmlspecialchars($payment['plan_name']) . ' Subscription</td>
                    <td>1</td>
                    <td>₹' . number_format($subtotal, 2) . '</td>
                    <td>₹' . number_format($subtotal, 2) . '</td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-value">₹' . number_format($subtotal, 2) . '</div>
            </div>';

if ($discount > 0) {
    $html .= '
            <div class="total-row">
                <div class="total-label">Discount:</div>
                <div class="total-value" style="color: green;">-₹' . number_format($discount, 2) . '</div>
            </div>';
}

if ($gstAmount > 0) {
    $gstType = $payment['gst_type'] == 'inclusive' ? 'Inclusive' : 'Exclusive';
    $html .= '
            <div class="total-row">
                <div class="total-label">GST (' . intval($payment['gst_percentage']) . '% ' . $gstType . '):</div>
                <div class="total-value">₹' . number_format($gstAmount, 2) . '</div>
            </div>';
}

$html .= '
            <div class="total-row grand-total">
                <div class="total-label">Grand Total:</div>
                <div class="total-value">₹' . number_format($total, 2) . '</div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice. No signature required.</p>
            <p>' . htmlspecialchars($company['app_name'] ?? 'Book Pannu') . ' | ' . htmlspecialchars($company['address'] ?? '') . '</p>
        </div>
    </div>
</body>
</html>';

// For now, output as HTML
// In production, you would convert this HTML to PDF using a library like TCPDF, DomPDF, or mPDF

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="invoice-' . $invoiceNumber . '.pdf"');

// Since we're not using a PDF library, let's output HTML for now
// You'll need to implement PDF generation with a proper library
echo $html;
exit;