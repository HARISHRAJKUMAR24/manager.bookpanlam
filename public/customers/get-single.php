<?php
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/config.php";
require_once "../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   INPUT
================================ */
$user_id     = $_GET['user_id'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;

if (!$user_id || !$customer_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing parameters"
    ]);
    exit();
}

/* ===============================
   FETCH CUSTOMER (SELLER SAFE)
================================ */
$stmt = $pdo->prepare("
    SELECT
        customer_id,
        name,
        email,
        phone,
        photo,
        created_at
    FROM customers
    WHERE customer_id = ?
      AND user_id = ?
    LIMIT 1
");

$stmt->execute([$customer_id, $user_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode([
        "success" => false,
        "message" => "Customer not found"
    ]);
    exit();
}

echo json_encode([
    "success" => true,
    "data" => $customer
]);
