<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// FIX: use coupon_id instead of id
$coupon_id = $_GET['coupon_id'] ?? '';

if (!$coupon_id) {
    echo json_encode(["success" => false, "message" => "Coupon ID is required"]);
    exit();
}

// TEMP user_id
$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit();
}

// Delete
$sql = "DELETE FROM coupons WHERE coupon_id = :coupon_id";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([':coupon_id' => $coupon_id]);

if ($result && $stmt->rowCount() > 0) {
    echo json_encode(["success" => true, "message" => "Coupon deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Coupon not found or already deleted"]);
}
