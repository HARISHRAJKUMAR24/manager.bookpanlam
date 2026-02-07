<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once '../../../config/config.php';
require_once '../../../src/database.php';

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

// FIXED DEV OTP
$otp = "111111";

echo json_encode([
    "success" => true,
    "message" => "OTP sent successfully (dev mode)",
    "otp" => $otp
]);
