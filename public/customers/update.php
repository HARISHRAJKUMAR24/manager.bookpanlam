<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../config/config.php";
require_once "../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   INPUT
================================ */
$customer_id = (int)($_GET["customer_id"] ?? 0);
$data = json_decode(file_get_contents("php://input"), true);

$name  = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$phone = trim($data["phone"] ?? "");
$photo = trim($data["photo"] ?? "");

if (!$customer_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing customer ID"
    ]);
    exit;
}

/* ===============================
   CHECK CUSTOMER
================================ */
$check = $pdo->prepare("
    SELECT id FROM customers WHERE customer_id = ?
");
$check->execute([$customer_id]);

if (!$check->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "Customer not found"
    ]);
    exit;
}

/* ===============================
   UPDATE
================================ */
$stmt = $pdo->prepare("
    UPDATE customers
    SET name = ?, email = ?, phone = ?, photo = ?
    WHERE customer_id = ?
");

$stmt->execute([$name, $email, $phone, $photo, $customer_id]);

/* ===============================
   RETURN UPDATED CUSTOMER
================================ */
$get = $pdo->prepare("
    SELECT id, customer_id, user_id, name, email, phone, photo
    FROM customers
    WHERE customer_id = ?
");
$get->execute([$customer_id]);
$customer = $get->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success"  => true,
    "message"  => "Profile updated successfully",
    "customer" => $customer
]);
