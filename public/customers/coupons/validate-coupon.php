<?php
// public/coupons/validate-coupon.php
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Adjust path based on your file structure
require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    exit();
}

$coupon_code = isset($input['coupon_code']) ? trim($input['coupon_code']) : '';
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$total_amount = isset($input['total_amount']) ? floatval($input['total_amount']) : 0;

if (empty($coupon_code) || $user_id <= 0) {
    echo json_encode(["success" => false, "message" => "Coupon code and User ID required"]);
    exit();
}

// Get current date
$current_date = date('Y-m-d H:i:s');

try {
    // First, try exact match
    $sql = "SELECT * FROM coupons 
            WHERE user_id = :user_id 
            AND code = :code
            AND start_date <= :current_date 
            AND end_date >= :current_date";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':code' => $coupon_code,
        ':current_date' => $current_date
    ]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid or expired coupon code"
        ]);
        exit();
    }

    // Check usage limit
    if ($coupon['usage_limit'] !== null && $coupon['usage_limit'] <= 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Coupon usage limit exceeded"
        ]);
        exit();
    }

    // Check minimum booking amount
    if ($coupon['min_booking_amount'] > 0 && $total_amount < $coupon['min_booking_amount']) {
        echo json_encode([
            "success" => false, 
            "message" => "Minimum booking amount of ₹{$coupon['min_booking_amount']} required"
        ]);
        exit();
    }

    // Calculate discount
    $discount_amount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount_amount = ($total_amount * $coupon['discount']) / 100;
    } else {
        $discount_amount = $coupon['discount'];
    }

    // Ensure discount doesn't exceed total amount
    $discount_amount = min($discount_amount, $total_amount);
    $total_after_discount = $total_amount - $discount_amount;

    // Format dates for frontend
    $coupon['start_date'] = date('Y-m-d\TH:i:s', strtotime($coupon['start_date']));
    $coupon['end_date'] = date('Y-m-d\TH:i:s', strtotime($coupon['end_date']));
    $coupon['formatted_start'] = date('d M Y', strtotime($coupon['start_date']));
    $coupon['formatted_end'] = date('d M Y', strtotime($coupon['end_date']));

    echo json_encode([
        'success' => true,
        'data' => $coupon,
        'discount' => [
            'type' => $coupon['discount_type'],
            'value' => $coupon['discount'],
            'amount' => round($discount_amount, 2),
            'total_after_discount' => round($total_after_discount, 2)
        ],
        'message' => "Coupon applied successfully!"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>