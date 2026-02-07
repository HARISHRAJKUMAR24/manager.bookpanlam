<?php
// manager.bookpanlam/public/seller/payment/email-invoice.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$invoiceNumber = $data['invoice_number'] ?? 0;
$email = $data['email'] ?? '';

if (!$invoiceNumber || !$email) {
    echo json_encode([
        "success" => false,
        "message" => "Invoice number and email are required"
    ]);
    exit;
}

$pdo = getDbConnection();

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
    echo json_encode([
        "success" => false,
        "message" => "Payment details not found"
    ]);
    exit;
}

// Get company info
$settingsSql = "SELECT app_name FROM settings LIMIT 1";
$settingsStmt = $pdo->prepare($settingsSql);
$settingsStmt->execute();
$company = $settingsStmt->fetch(PDO::FETCH_ASSOC);

// For now, simulate email sending
// In production, implement actual email sending with PHPMailer or similar
$companyName = $company['app_name'] ?? 'Book Pannu';

// Simulate email success
$emailSent = true; // In production, this would be the result of mail() function

if ($emailSent) {
    echo json_encode([
        "success" => true,
        "message" => "Invoice sent to " . $email
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to send email"
    ]);
}

exit;