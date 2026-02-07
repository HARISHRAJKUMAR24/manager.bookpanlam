<?php
// seller/coupons/create.php
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
require_once "../../../src/functions.php"; // Make sure functions.php is included

$pdo = getDbConnection();

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit();
}

$required = ['name', 'code', 'discount_type', 'discount', 'start_date', 'end_date'];

foreach ($required as $f) {
    if (!isset($data[$f]) || $data[$f] === "") {
        echo json_encode(["success" => false, "message" => "$f is required"]);
        exit();
    }
}

$user_id = $_GET['user_id'] ?? 1;

// ✅ KEY CHANGE 1: Check coupon limit before creating
validateResourceLimit($user_id, 'coupons');

$coupon_id = "CPN_" . uniqid() . "_" . rand(1000, 9999);

$name = trim($data['name']);
$code = strtoupper(trim($data['code']));
$discount_type = $data['discount_type'];
$discount = (int) $data['discount'];

$start_ts = strtotime(str_replace("Z", "", $data['start_date']));
$end_ts = strtotime(str_replace("Z", "", $data['end_date']));

if (!$start_ts || !$end_ts) {
    echo json_encode(["success" => false, "message" => "Invalid date format"]);
    exit();
}

$start_date = date("Y-m-d H:i:s.v", $start_ts);
$end_date = date("Y-m-d H:i:s.v", $end_ts);

$usage_limit = $data['usage_limit'] !== "" ? (int)$data['usage_limit'] : null;
$min_booking_amount = $data['min_booking_amount'] !== "" ? (int)$data['min_booking_amount'] : null;

$check = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE code = :code AND user_id = :user_id");
$check->execute(['code' => $code, 'user_id' => $user_id]);

if ($check->fetchColumn() > 0) {
    echo json_encode(["success" => false, "message" => "Coupon code already exists"]);
    exit();
}

$sql = "INSERT INTO coupons (
            coupon_id, user_id, name, code, discount_type, discount,
            start_date, end_date, usage_limit, min_booking_amount, created_at
        ) VALUES (
            :coupon_id, :user_id, :name, :code, :discount_type, :discount,
            :start_date, :end_date, :usage_limit, :min_booking_amount, NOW(3)
        )";

$stmt = $pdo->prepare($sql);

$result = $stmt->execute([
    "coupon_id" => $coupon_id,
    "user_id" => $user_id,
    "name" => $name,
    "code" => $code,
    "discount_type" => $discount_type,
    "discount" => $discount,
    "start_date" => $start_date,
    "end_date" => $end_date,
    "usage_limit" => $usage_limit,
    "min_booking_amount" => $min_booking_amount
]);

if ($result) {
    echo json_encode([
        "success" => true,
        "message" => "Coupon created successfully",
        "coupon_id" => $coupon_id
    ]);
} else {
    $error = $stmt->errorInfo();
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $error[2]
    ]);
}
