<?php
// manager.bookpanlam/public/seller/payment/check-downgrade.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// Get parameters
$user_id = $_GET['user_id'] ?? null;
$plan_id = $_GET['plan_id'] ?? null;

if (!$user_id || !$plan_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID and Plan ID are required"
    ]);
    exit;
}

try {
    // Get user's current plan amount
    $userSql = "SELECT 
        u.plan_id,
        sp.amount as current_plan_amount,
        sp.name as current_plan_name
    FROM users u
    LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
    WHERE u.user_id = :user_id";
    
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([':user_id' => $user_id]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get selected plan amount and payment gateways
    $planSql = "SELECT amount, name, razorpay, phonepe, payu FROM subscription_plans WHERE id = :plan_id";
    $planStmt = $pdo->prepare($planSql);
    $planStmt->execute([':plan_id' => $plan_id]);
    $planData = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$planData) {
        echo json_encode([
            "success" => false,
            "message" => "Plan not found"
        ]);
        exit;
    }
    
    $current_plan_amount = $userData['current_plan_amount'] ?? 0;
    $selected_plan_amount = $planData['amount'] ?? 0;
    $current_plan_name = $userData['current_plan_name'] ?? 'Current Plan';
    $selected_plan_name = $planData['name'] ?? '';
    
    // Get available payment gateways for this plan
    $available_gateways = [];
    if ($planData['razorpay'] == 1) $available_gateways[] = 'razorpay';
    if ($planData['phonepe'] == 1) $available_gateways[] = 'phonepe';
    if ($planData['payu'] == 1) $available_gateways[] = 'payu';
    
    // Determine if this is a downgrade
    $is_downgrade = false;
    $is_disabled = false;
    $message = "";
    
    if ($userData && $userData['plan_id']) {
        // User has a current plan
        if ($userData['plan_id'] == $plan_id) {
            // Same plan - renewal
            $is_disabled = false;
            $message = "Renew your current plan";
        } elseif ($selected_plan_amount < $current_plan_amount) {
            // Downgrade - lower price
            $is_downgrade = true;
            $is_disabled = true;
            $message = "Downgrade not allowed. You can only upgrade to higher-priced plans.";
        } elseif ($selected_plan_amount > $current_plan_amount) {
            // Upgrade - higher price
            $is_disabled = false;
            $message = "Upgrade to a better plan";
        } else {
            // Same price different plan
            $is_disabled = false;
            $message = "Switch to a different plan";
        }
    } else {
        // New user - no current plan
        $is_disabled = false;
        $message = "Start your subscription";
    }
    
    echo json_encode([
        "success" => true,
        "is_downgrade" => $is_downgrade,
        "is_disabled" => $is_disabled,
        "message" => $message,
        "current_plan_name" => $current_plan_name,
        "selected_plan_name" => $selected_plan_name,
        "current_plan_amount" => $current_plan_amount,
        "selected_plan_amount" => $selected_plan_amount,
        "available_gateways" => $available_gateways
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>