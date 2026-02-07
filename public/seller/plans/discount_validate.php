<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log for debugging (remove in production)
error_log("Discount validation request: " . print_r($data, true));

$code = isset($data['code']) ? trim($data['code']) : '';
$planId = isset($data['planId']) ? intval($data['planId']) : null;

// Validate input
if (empty($code)) {
    echo json_encode([
        "success" => false,
        "message" => "Discount code is required"
    ]);
    exit;
}

// Check if discount code exists
$sql = "SELECT * FROM discounts WHERE code = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$code]);
$discount = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$discount) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid discount code"
    ]);
    exit;
}

// Check if discount is active (you might want to add an 'active' column)
// For now, we assume all discounts in the table are active

// Check eligibility
// If eligibility is NULL, discount is for everyone
// If eligibility has a plan ID, check if it matches the current plan
if ($discount['eligibility'] !== null) {
    if ($planId === null) {
        echo json_encode([
            "success" => false,
            "message" => "This discount requires a specific plan selection"
        ]);
        exit;
    }
    
    if ($discount['eligibility'] != $planId) {
        // Get plan name for better error message
        $planSql = "SELECT name FROM subscription_plans WHERE id = ?";
        $planStmt = $pdo->prepare($planSql);
        $planStmt->execute([$discount['eligibility']]);
        $eligiblePlan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        $planName = $eligiblePlan ? $eligiblePlan['name'] : 'specific plan';
        
        echo json_encode([
            "success" => false,
            "message" => "This discount code is only applicable for the '{$planName}' plan"
        ]);
        exit;
    }
}

// Return valid discount details
echo json_encode([
    "success" => true,
    "discount" => [
        "id" => (int)$discount['id'],
        "code" => $discount['code'],
        "type" => $discount['type'],
        "amount" => (int)$discount['discount'],
        "eligibility" => $discount['eligibility'] ? (int)$discount['eligibility'] : null
    ],
    "message" => "Discount applied successfully!"
]);
exit;