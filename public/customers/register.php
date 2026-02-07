<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===============================
   CORS HEADERS (WITH CREDENTIALS)
================================ */

// 👇 CHANGE THIS to match your frontend exactly
$allowedOrigins = [
    "http://localhost:3001"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===============================
   BOOTSTRAP
================================ */
require_once "../../config/config.php";
require_once "../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   READ & VALIDATE INPUT
================================ */
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON payload"
    ]);
    exit;
}

$name     = trim($data["name"] ?? "");
$email    = trim($data["email"] ?? "");
$phone    = trim($data["phone"] ?? "");
$password = $data["password"] ?? "";
$slug     = trim($data["slug"] ?? "");

if ($name === "" || $phone === "" || $password === "" || $slug === "") {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

/* ===============================
   1️⃣ FIND SELLER BY SLUG
================================ */
$stmt = $pdo->prepare("
    SELECT user_id
    FROM users
    WHERE site_slug = ?
    LIMIT 1
");
$stmt->execute([$slug]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid seller"
    ]);
    exit;
}

$user_id = (int)$seller["user_id"];

/* ===============================
   2️⃣ PREVENT DUPLICATE PHONE
================================ */
$chk = $pdo->prepare("
    SELECT id
    FROM customers
    WHERE phone = ? AND user_id = ?
    LIMIT 1
");
$chk->execute([$phone, $user_id]);

if ($chk->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "Phone already registered"
    ]);
    exit;
}

/* ===============================
   3️⃣ CREATE CUSTOMER
================================ */
$customer_id    = random_int(100000, 999999);
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
    INSERT INTO customers
        (customer_id, user_id, name, email, phone, password)
    VALUES
        (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $customer_id,
    $user_id,
    $name,
    $email !== "" ? $email : null,
    $phone,
    $hashedPassword
]);

/* ===============================
   SUCCESS RESPONSE
================================ */
echo json_encode([
    "success" => true,
    "message" => "Registration successful",
    "customer_id" => $customer_id
]);
exit;
