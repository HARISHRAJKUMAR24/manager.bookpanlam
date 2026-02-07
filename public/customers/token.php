<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$allowedOrigin = "http://localhost:3001";
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "../../config/config.php";
require_once "../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   1️⃣ GET SITE SLUG
================================ */
$site = $_GET['site'] ?? '';
if (!$site) {
    echo json_encode(null);
    exit;
}

/* ===============================
   2️⃣ FIND SELLER BY SLUG
================================ */
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE site_slug = ? LIMIT 1");
$stmt->execute([$site]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    echo json_encode(null);
    exit;
}

$sellerUserId = (int)$seller['user_id'];

/* ===============================
   3️⃣ TOKEN FROM COOKIE
================================ */
if (!isset($_COOKIE['customer_token'])) {
    echo json_encode(null);
    exit;
}

$payload = json_decode(base64_decode($_COOKIE['customer_token']), true);

if (!$payload || !isset($payload['customer_id']) || !isset($payload['user_id'])) {
    echo json_encode(null);
    exit;
}

/* ===============================
   4️⃣ SECURITY CHECK
   - Token must belong to this seller ONLY
================================ */
if ((int)$payload['user_id'] !== $sellerUserId) {
    // Customer belongs to another seller → reject
    echo json_encode(null);
    exit;
}

/* ===============================
   5️⃣ GET CUSTOMER
================================ */
$stmt = $pdo->prepare("
  SELECT customer_id, name, phone, email, photo
  FROM customers
  WHERE customer_id = ? AND user_id = ?
  LIMIT 1
");
$stmt->execute([$payload['customer_id'], $sellerUserId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($customer ?: null);
