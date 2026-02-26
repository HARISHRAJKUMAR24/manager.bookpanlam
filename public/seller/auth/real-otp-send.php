<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

session_start();

$input = $_POST;
if (!$input) {
    $input = json_decode(file_get_contents("php://input"), true);
}

$phone = trim($input['phone'] ?? '');

if (!$phone) {
    echo json_encode([
        "success" => false,
        "message" => "Phone is required"
    ]);
    exit;
}

// 🔐 Generate 6 digit OTP
$otp = rand(100000, 999999);

// 🔑 SMSLocal Credentials
$apiKey     = "27ad8c38db4dc04864c8c1948968cea9";
$route      = "transactional";
$sender     = "YOUR_SENDER_ID";       // Example: BOOKPN
$templateId = "YOUR_DLT_TEMPLATE_ID"; // Example: 1707161234567890123

// ⚠️ Message must EXACTLY match DLT template
$message = urlencode("Your OTP is $otp");

// 📡 SMSLocal API URL
$url = "https://app.smslocal.in/api/smsapi?key=$apiKey&route=$route&sender=$sender&number=$phone&sms=$message&templateid=$templateId";

// Use cURL (recommended)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to send SMS"
    ]);
    exit;
}

// Save OTP in session (5 min expiry)
$_SESSION['otp'] = $otp;
$_SESSION['otp_phone'] = $phone;
$_SESSION['otp_time'] = time();

echo json_encode([
    "success" => true,
    "message" => "OTP sent successfully"
]);