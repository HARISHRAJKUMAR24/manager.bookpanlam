<?php
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

/* ✅ Handle OPTIONS */
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../../config/config.php";
require_once "../../src/database.php";

$data = json_decode(file_get_contents("php://input"), true);

/* ===============================
   DUMMY OTP CHECK ONLY
================================ */
$otp = trim((string)($data["otp"] ?? ""));

if ($otp !== "111111") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid OTP"
    ]);
    exit;
}

/* ===============================
   REQUIRED DATA
================================ */
$name     = trim($data["name"] ?? "");
$email    = trim($data["email"] ?? "");
$phone    = trim($data["phone"] ?? "");
$password = $data["password"] ?? "";
$slug     = trim($data["slug"] ?? "");

if (!$name || !$phone || !$password || !$slug) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

try {
    $pdo = getDbConnection();

    /* ===============================
       FIND SELLER BY SLUG
    =============================== */
    $stmt = $pdo->prepare(
        "SELECT user_id FROM users WHERE site_slug = ? LIMIT 1"
    );
    $stmt->execute([$slug]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid seller"
        ]);
        exit;
    }

    /* ===============================
       DUPLICATE PHONE CHECK
    =============================== */
    $stmt = $pdo->prepare(
        "SELECT id FROM customers WHERE phone = ? AND user_id = ? LIMIT 1"
    );
    $stmt->execute([$phone, $seller["user_id"]]);

    if ($stmt->fetch()) {
        echo json_encode([
            "success" => false,
            "message" => "Phone number already registered"
        ]);
        exit;
    }

    /* ===============================
       CREATE CUSTOMER
    =============================== */
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $customer_id = time() . rand(100, 999);

    $stmt = $pdo->prepare("
        INSERT INTO customers
        (customer_id, user_id, name, email, phone, password)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $customer_id,
        $seller["user_id"],
        $name,
        $email ?: null,
        $phone,
        $hashed
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Signup successful"
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}
