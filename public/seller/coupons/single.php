<?php
// coupons/single.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// Get coupon ID from GET parameter
$coupon_id = $_GET['coupon_id'] ?? '';

if (empty($coupon_id)) {
    echo json_encode(["success" => false, "message" => "Coupon ID required"]);
    exit();
}

$sql = "SELECT * FROM coupons WHERE coupon_id = :coupon_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':coupon_id' => $coupon_id]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(["success" => false, "message" => "Coupon not found"]);
    exit();
}

// Format dates for frontend
$coupon['start_date'] = date('Y-m-d\TH:i:s', strtotime($coupon['start_date']));
$coupon['end_date'] = date('Y-m-d\TH:i:s', strtotime($coupon['end_date']));

echo json_encode([
    'success' => true,
    'data' => $coupon
]);