<?php
// coupons/update.php
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

// Get coupon ID and user ID
$coupon_id = $_GET['coupon_id'] ?? '';
$user_id   = $_GET['user_id'] ?? '';

if (empty($coupon_id)) {
    echo json_encode(["success" => false, "message" => "Coupon ID is required"]);
    exit();
}

if (empty($user_id)) {
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit();
}

// Read JSON body
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data)) {
    echo json_encode(["success" => false, "message" => "No data provided"]);
    exit();
}

// Check if coupon exists for this user
$checkSql = "SELECT COUNT(*) FROM coupons WHERE coupon_id = :coupon_id AND user_id = :user_id";
$checkStmt = $pdo->prepare($checkSql);
$checkStmt->execute([':coupon_id' => $coupon_id, ':user_id' => $user_id]);

if ($checkStmt->fetchColumn() == 0) {
    echo json_encode(["success" => false, "message" => "Coupon not found"]);
    exit();
}

$updateFields = [];
$params = [':coupon_id' => $coupon_id, ':user_id' => $user_id];

$fieldMap = [
    'name' => 'name',
    'code' => 'code',
    'discount_type' => 'discount_type',
    'discount' => 'discount',
    'start_date' => 'start_date',
    'end_date' => 'end_date',
    'usage_limit' => 'usage_limit',
    'min_booking_amount' => 'min_booking_amount'
];

foreach ($fieldMap as $field => $dbField) {
    if (isset($data[$field])) {
        $value = $data[$field];

        if ($field === 'code') {
            $value = strtoupper($value);
        }

        if (in_array($field, ['start_date', 'end_date'])) {
            $value = date('Y-m-d H:i:s', strtotime($value));
        }

        if (in_array($field, ['usage_limit', 'min_booking_amount']) &&
            ($value === '' || $value === null)) {
            $value = null;
        }

        $updateFields[] = "$dbField = :$field";
        $params[":$field"] = $value;
    }
}

if (empty($updateFields)) {
    echo json_encode(["success" => false, "message" => "No fields to update"]);
    exit();
}

$sql = "UPDATE coupons SET " . implode(', ', $updateFields) . " 
        WHERE coupon_id = :coupon_id AND user_id = :user_id";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute($params);

echo json_encode([
    "success" => $result,
    "message" => $result ? "Coupon updated successfully" : "Failed to update coupon"
]);
