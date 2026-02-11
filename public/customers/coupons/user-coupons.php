<?php
// public/coupons/user-coupons.php
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

// Get user_id from GET parameter
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode([
        "success" => false, 
        "data" => [], 
        "count" => 0,
        "message" => "User ID required"
    ]);
    exit();
}

// Get current date
$current_date = date('Y-m-d H:i:s');

try {
    // Fetch all active coupons for this user
    $sql = "SELECT * FROM coupons 
            WHERE user_id = :user_id 
            AND start_date <= :current_date 
            AND end_date >= :current_date
            AND (usage_limit IS NULL OR usage_limit > 0)
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':current_date' => $current_date
    ]);
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates for frontend
    foreach ($coupons as &$coupon) {
        $coupon['start_date'] = date('Y-m-d\TH:i:s', strtotime($coupon['start_date']));
        $coupon['end_date'] = date('Y-m-d\TH:i:s', strtotime($coupon['end_date']));
        $coupon['formatted_start'] = date('d M Y', strtotime($coupon['start_date']));
        $coupon['formatted_end'] = date('d M Y', strtotime($coupon['end_date']));
    }

    echo json_encode([
        'success' => true,
        'data' => $coupons,
        'count' => count($coupons)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'data' => [],
        'count' => 0,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>