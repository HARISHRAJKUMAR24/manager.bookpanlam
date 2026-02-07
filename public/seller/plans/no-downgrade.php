<?php
// manager.bookpanlam/public/seller/plans/no-downgrade.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$pdo = getDbConnection();

// Get user ID from query parameter or session
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required"
    ]);
    exit;
}

try {
    // Get user's current plan details
    $userSql = "SELECT 
        u.plan_id,
        u.expires_on,
        sp.amount as current_plan_amount,
        sp.name as current_plan_name,
        sp.duration as current_plan_duration
    FROM users u
    LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
    WHERE u.user_id = :user_id";

    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([':user_id' => $user_id]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        exit;
    }

    // Check if plan is expired
    $plan_expired = false;
    $expires_on = $userData['expires_on'];
    $current_plan_id = $userData['plan_id'];
    
    if ($expires_on && $expires_on !== '0000-00-00 00:00:00' && $expires_on !== null) {
        $expiry_date = new DateTime($expires_on);
        $today = new DateTime('now');
        $plan_expired = ($expiry_date < $today);
    }
    
    // Get all available plans
    $planSql = "SELECT id, name, amount, duration FROM subscription_plans 
                WHERE is_disabled = 1 
                ORDER BY amount ASC";
    $planStmt = $pdo->prepare($planSql);
    $planStmt->execute();
    $allPlans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get settings for GST calculation
    $settingsSql = "SELECT gst_percentage, gst_tax_type FROM settings LIMIT 1";
    $settingsStmt = $pdo->prepare($settingsSql);
    $settingsStmt->execute();
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

    $gstPercentage = $settings['gst_percentage'] ?? 18;
    $gstType = $settings['gst_tax_type'] ?? 'exclusive';

    // Duration format function
    function formatDurationDisplay($days)
    {
        if (!$days || $days <= 0) {
            return "N/A";
        }
        
        if ($days == 365) return "1 Year";
        elseif ($days == 730) return "2 Years";
        elseif ($days == 1095) return "3 Years";
        elseif ($days == 30) return "1 Month";
        elseif ($days == 60) return "2 Months";
        elseif ($days == 90) return "3 Months";
        elseif ($days == 180) return "6 Months";
        elseif ($days < 30) return "$days Days";
        elseif ($days < 365) {
            $months = floor($days / 30);
            return "$months Months";
        } else {
            $years = floor($days / 365);
            $remaining_days = $days % 365;
            if ($remaining_days > 0) {
                $remaining_months = floor($remaining_days / 30);
                return "$years Years, $remaining_months Months";
            }
            return "$years Years";
        }
    }

    // Calculate current plan's final price (with GST)
    $currentPlanAmount = $userData['current_plan_amount'] ?? 0;
    $currentPlanFinalPrice = 0;
    
    if ($current_plan_id) {
        if ($gstType === 'inclusive') {
            $currentPlanFinalPrice = $currentPlanAmount;
        } else {
            $currentPlanFinalPrice = $currentPlanAmount + ($currentPlanAmount * $gstPercentage / 100);
        }
    }

    // Analyze each plan for upgrade eligibility
    $planStatuses = [];
    foreach ($allPlans as $plan) {
        $planAmount = $plan['amount'];

        // Calculate plan's final price (with GST)
        if ($gstType === 'inclusive') {
            $planFinalPrice = $planAmount;
        } else {
            $planFinalPrice = $planAmount + ($planAmount * $gstPercentage / 100);
        }

        // Determine button status and text
        $buttonStatus = 'available';
        $buttonText = 'Choose Plan';
        $isDisabled = false;

        if ($current_plan_id) {
            // User has a plan (may be expired or active)
            
            if ($plan['id'] == $current_plan_id) {
                // Same plan
                if ($plan_expired) {
                    // Plan expired - show "Renew Your Plan"
                    $buttonStatus = 'expired';
                    $buttonText = 'Renew Your Plan';
                    $isDisabled = false;
                } else {
                    // Plan active - show "Current Plan"
                    $buttonStatus = 'current';
                    $buttonText = 'Renew Current Plan';
                    $isDisabled = false;
                }
            } else if ($planFinalPrice < $currentPlanFinalPrice) {
                // Downgrade - show message with current plan (ALWAYS DISABLED)
                $buttonStatus = 'downgrade';
                $buttonText = 'You are in ' . ($userData['current_plan_name'] ?? 'Current Plan');
                $isDisabled = true;
            } else if ($planFinalPrice > $currentPlanFinalPrice) {
                // Upgrade - enable button
                $buttonStatus = 'upgrade';
                $buttonText = 'Upgrade Now';
                $isDisabled = false;
            } else {
                // Same price but different plan
                $buttonStatus = 'available';
                $buttonText = 'Switch Plan';
                $isDisabled = false;
            }
        } else {
            // User has no plan (new user)
            $buttonStatus = 'available';
            $buttonText = 'Choose Plan';
            $isDisabled = false;
        }

        $planStatuses[] = [
            'plan_id' => $plan['id'],
            'plan_name' => $plan['name'],
            'plan_amount' => $planAmount,
            'plan_duration' => $plan['duration'],
            'plan_duration_display' => formatDurationDisplay($plan['duration']),
            'plan_final_price' => round($planFinalPrice),
            'button_status' => $buttonStatus,
            'button_text' => $buttonText,
            'is_disabled' => $isDisabled
        ];
    }

    // Format current plan duration
    $currentPlanDurationDisplay = $userData['current_plan_duration'] ?
        formatDurationDisplay($userData['current_plan_duration']) : 'N/A';

    // Format expiry message
    $expiry_message = '';
    $plan_status = '';
    
    if ($plan_expired) {
        $expiry_message = 'Your plan has expired. Please renew to continue using all features.';
        $plan_status = 'expired';
    } else if ($current_plan_id) {
        $days_remaining = '';
        if ($expires_on && $expires_on !== '0000-00-00 00:00:00') {
            $expiry_date = new DateTime($expires_on);
            $today = new DateTime('now');
            $interval = $today->diff($expiry_date);
            $days_remaining = $interval->days;
            
            if ($days_remaining <= 15) {
                $plan_status = 'expiring_soon';
                $expiry_message = "Your plan expires in {$days_remaining} days";
            } else {
                $plan_status = 'active';
                $expiry_message = "Expires on " . date('d M Y', strtotime($expires_on));
            }
        }
    }

    echo json_encode([
        "success" => true,
        "user_data" => [
            "user_id" => $user_id,
            "current_plan_id" => $current_plan_id,
            "current_plan_name" => $userData['current_plan_name'] ?? 'No Plan',
            "current_plan_amount" => $currentPlanAmount,
            "current_plan_duration" => $userData['current_plan_duration'] ?? 0,
            "current_plan_duration_display" => $currentPlanDurationDisplay,
            "current_plan_final_price" => round($currentPlanFinalPrice),
            "expires_on" => $expires_on,
            "expires_on_date" => $expires_on ? date('d M Y', strtotime($expires_on)) : 'N/A',
            "plan_expired" => $plan_expired,
            "plan_status" => $plan_status,
            "expiry_message" => $expiry_message
        ],
        "plan_statuses" => $planStatuses
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>