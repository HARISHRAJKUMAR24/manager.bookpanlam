<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once '../../../config/config.php';
require_once '../../../src/database.php';

$pdo = getDbConnection();

/* --------------------------------
   READ INPUT (JSON or FORM)
--------------------------------- */
$input = $_POST;
if (!$input) {
    $input = json_decode(file_get_contents("php://input"), true);
}

/* --------------------------------
   READ FIELDS
--------------------------------- */
$name        = trim($input['name'] ?? "");
$email       = trim($input['email'] ?? "");
$phone       = trim($input['phone'] ?? "");
$country     = trim($input['country'] ?? "IN");
$siteName    = trim($input['siteName'] ?? "");
$password    = trim($input['password'] ?? "");
$otp         = trim($input['otp'] ?? "");
$serviceTypeId = isset($input['serviceTypeId'])
    ? (int)$input['serviceTypeId']
    : null;

/* --------------------------------
   DEFAULT SITE NAME
--------------------------------- */
if (!$siteName) {
    $siteName = $name . "'s Site";
}

/* --------------------------------
   VALIDATIONS
--------------------------------- */
if (!$name || !$phone || !$password || !$otp) {
    echo json_encode([
        "success" => false,
        "message" => "All fields required"
    ]);
    exit;
}

if (!$serviceTypeId) {
    echo json_encode([
        "success" => false,
        "message" => "Service type is required"
    ]);
    exit;
}

if ($otp !== "111111") {
    echo json_encode([
        "success" => false,
        "message" => "Please enter correct OTP"
    ]);
    exit;
}

/* --------------------------------
   CHECK DUPLICATE PHONE
--------------------------------- */
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);

if ($stmt->fetchColumn()) {
    echo json_encode([
        "success" => false,
        "message" => "Phone already registered"
    ]);
    exit;
}

/* --------------------------------
   CREATE SLUG
--------------------------------- */
$siteSlug = strtolower(preg_replace('/\s+/', '-', $siteName));

/* --------------------------------
   HASH PASSWORD
--------------------------------- */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/* --------------------------------
   INSERT USER (🔥 FIX HERE)
--------------------------------- */
$userId = rand(10000, 99999);

$stmt = $pdo->prepare("
    INSERT INTO users (
        user_id,
        name,
        email,
        phone,
        password,
        country,
        site_name,
        site_slug,
        service_type_id,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$ok = $stmt->execute([
    $userId,
    $name,
    $email ?: null,
    $phone,
    $hashedPassword,
    $country ?: "IN",
    $siteName,
    $siteSlug,
    $serviceTypeId
]);

if (!$ok) {
    echo json_encode([
        "success" => false,
        "message" => "Registration failed"
    ]);
    exit;
}

/* --------------------------------
   SUCCESS RESPONSE
--------------------------------- */
echo json_encode([
    "success" => true,
    "message" => "Registration successful",
    "data" => [
        "user_id" => $userId,
        "name" => $name,
        "email" => $email,
        "phone" => $phone,
        "country" => $country,
        "site_name" => $siteName,
        "site_slug" => $siteSlug,
        "service_type_id" => $serviceTypeId
    ]
]);
