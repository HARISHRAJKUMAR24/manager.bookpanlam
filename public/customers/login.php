<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===============================
   CORS
================================ */
$allowedOrigin = "http://localhost:3001";

header("Access-Control-Allow-Origin: " . $allowedOrigin);
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/config.php";
require_once "../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   READ INPUT
================================ */
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

$phone = trim($data["phone"] ?? $data["user"] ?? "");
$password = $data["password"] ?? "";
$site = $_GET["site"] ?? ""; // seller slug

/* ===============================
   VALIDATION
================================ */
if ($phone === "" || $password === "") {
    echo json_encode([
        "success" => false,
        "message" => "Mobile number and password are required"
    ]);
    exit();
}

if (!$site) {
    echo json_encode([
        "success" => false,
        "message" => "Site slug missing"
    ]);
    exit();
}

/* ===============================
   FIND SELLER BY SLUG
================================ */
$stmt = $pdo->prepare("
    SELECT user_id 
    FROM users 
    WHERE site_slug = ? 
    LIMIT 1
");
$stmt->execute([$site]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid seller site"
    ]);
    exit();
}

$sellerUserId = (int)$seller["user_id"];

/* ===============================
   FIND CUSTOMER (BY PHONE + SELLER)
================================ */
$stmt = $pdo->prepare("
    SELECT customer_id, name, phone, photo, password, user_id
    FROM customers
    WHERE phone = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$phone, $sellerUserId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "success" => false,
        "message" => "Customer not found for this business"
    ]);
    exit();
}

/* ===============================
   VERIFY PASSWORD
================================ */
if (!password_verify($password, $customer["password"])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid mobile number or password"
    ]);
    exit();
}

/* ===============================
   TOKEN
================================ */
$tokenPayload = [
    "customer_id" => $customer["customer_id"],
    "user_id"     => $sellerUserId, // lock token to this seller
    "iat"         => time()
];

$token = base64_encode(json_encode($tokenPayload));

/* ===============================
   SET COOKIE
================================ */
setcookie(
    "customer_token",
    $token,
    [
        "expires"  => time() + (60 * 60 * 24 * 7), // 7 days
        "path"     => "/",
        "httponly" => true,
        "samesite" => "Lax"
    ]
);

/* ===============================
   SUCCESS RESPONSE
================================ */
echo json_encode([
    "success"  => true,
    "message"  => "Login successful",
    "token"    => $token,
    "customer" => [
        "customer_id" => $customer["customer_id"],
        "user_id"     => $sellerUserId,
        "name"        => $customer["name"],
        "phone"       => $customer["phone"]
    ]
]);

exit();

?>
