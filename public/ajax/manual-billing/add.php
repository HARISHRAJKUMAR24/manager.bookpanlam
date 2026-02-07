<?php
require_once '../../../src/database.php';
require_once '../../../src/functions.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'error' => ''
];

try {
    $pdo = getDbConnection();
    
    // Get form data
    $plan_id = $_POST['plan_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $admin_total_amount = $_POST['amount'] ?? 0; // This is TOTAL amount from admin (199)
    $duration_value = $_POST['duration_value'] ?? 1;
    $duration_type = $_POST['duration_type'] ?? 'month';
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_id = $_POST['payment_id'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_mobile = $_POST['customer_mobile'] ?? '';
    $state = $_POST['state'] ?? '';
    $city = $_POST['city'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $address_1 = $_POST['address_1'] ?? '';
    $address_2 = $_POST['address_2'] ?? '';
    $currency = $_POST['currency'] ?? 'INR';
    $country_code = $_POST['country_code'] ?? 'IN';
    $gst_number = $_POST['gst_number'] ?? null;
    
    // Validate required fields
    if (!$plan_id || !$user_id || !$payment_method) {
        $response['message'] = 'Missing required fields';
        echo json_encode($response);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get plan details including GST type and BASE amount
    $stmt = $pdo->prepare("SELECT name, duration, amount, gst_type FROM subscription_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        throw new Exception('Plan not found');
    }
    
    $plan_name = $plan['name'];
    $plan_duration = $plan['duration'];
    $gst_type = $plan['gst_type'] ?? 'exclusive';
    $plan_base_amount = $plan['amount'] ?? 0; // This is 169 for Plan 4

    // Get GST percentage from settings
    $settings_stmt = $pdo->query("SELECT gst_percentage FROM settings LIMIT 1");
    $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
    $gst_percentage = (int) ($settings['gst_percentage'] ?? 18);
    
    // Calculate GST amount based on GST type and plan base amount
    $gst_amount = 0;
    $total_amount = 0;
    
    if ($gst_type === 'exclusive') {
        // GST exclusive: GST = Plan Base Amount × GST%
        // Example for Plan 4: GST = 169 × 18% = 30.42 ≈ 30
        $gst_amount = (int) round(($plan_base_amount * $gst_percentage) / 100);
        $total_amount = $plan_base_amount + $gst_amount; // 169 + 30 = 199
    } else if ($gst_type === 'inclusive') {
        // GST inclusive: GST is already included in plan amount
        $gst_amount = 0;
        $total_amount = $plan_base_amount;
    }
    
    // Debug logging
    error_log("Manual Billing Calculation:");
    error_log("Plan: $plan_name (ID: $plan_id)");
    error_log("Plan Base Amount: $plan_base_amount");
    error_log("GST Type: $gst_type, GST %: $gst_percentage");
    error_log("Total Amount from Admin: $admin_total_amount");
    error_log("Calculated GST Amount: $gst_amount");
    error_log("Calculated Total Amount: $total_amount");
    
    // Validate that admin entered amount matches calculated total
    if ($admin_total_amount != $total_amount) {
        error_log("Warning: Admin entered amount ($admin_total_amount) doesn't match calculated total ($total_amount)");
        // You might want to handle this discrepancy - for now, we'll use calculated total
    }
    
    // Calculate custom duration if provided
    if ($duration_type === 'year') {
        $duration_days = $duration_value * 365;
    } else {
        $duration_days = $duration_value * 30;
    }
    
    if ($duration_value > 0) {
        $final_duration_days = $duration_days;
    } else {
        $final_duration_days = $plan_duration;
    }
    
    // Get user's current plan and expiry
    $stmt = $pdo->prepare("SELECT plan_id, expires_on FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $current_plan_id = $user['plan_id'] ?? null;
    $current_expires_on = $user['expires_on'] ?? null;
    
    // Check if current plan is active
    $current_plan_active = false;
    if ($current_expires_on && $current_expires_on !== '0000-00-00 00:00:00') {
        try {
            $current_expiry_date = new DateTime($current_expires_on);
            $today = new DateTime('now');
            $current_plan_active = ($current_expiry_date > $today);
        } catch (Exception $e) {
            $current_plan_active = false;
        }
    }
    
    // Check for downgrade restriction (compare plan base amounts)
    if ($current_plan_id && $current_plan_active) {
        $current_plan_stmt = $pdo->prepare("SELECT amount FROM subscription_plans WHERE id = ?");
        $current_plan_stmt->execute([$current_plan_id]);
        $current_plan = $current_plan_stmt->fetch(PDO::FETCH_ASSOC);
        $current_plan_amount = $current_plan['amount'] ?? 0;
        
        if ($plan_base_amount < $current_plan_amount) {
            $pdo->rollBack();
            $response['success'] = false;
            $response['message'] = 'Downgrade not allowed. User cannot downgrade to a lower-priced plan while current plan is active.';
            echo json_encode($response);
            exit;
        }
    }
    
    // Determine subscription type and calculate new expiry
    $subscription_type = "new";
    $is_renewal = false;
    $is_upgrade = false;
    $is_switch = false;
    $expires_on = "";
    $subscription_message = "";
    
    if ($current_plan_id && $current_plan_active) {
        if ($current_plan_id == $plan_id) {
            $subscription_type = "renewal";
            $is_renewal = true;
            
            $current_date = new DateTime($current_expires_on);
            
            if ($final_duration_days > 0) {
                $current_date->modify("+$final_duration_days days");
                $expires_on = $current_date->format('Y-m-d H:i:s');
            }
            
            $subscription_message = "Plan renewed. Added " . convertDurationForDisplay($final_duration_days) . " to your subscription.";
        } else {
            $current_plan_stmt = $pdo->prepare("SELECT amount FROM subscription_plans WHERE id = ?");
            $current_plan_stmt->execute([$current_plan_id]);
            $current_plan_data = $current_plan_stmt->fetch(PDO::FETCH_ASSOC);
            $current_plan_amount = $current_plan_data['amount'] ?? 0;
            
            if ($plan_base_amount > $current_plan_amount) {
                $subscription_type = "upgrade";
                $is_upgrade = true;
                
                $today = new DateTime('now');
                if ($final_duration_days > 0) {
                    $today->modify("+$final_duration_days days");
                    $expires_on = $today->format('Y-m-d H:i:s');
                }
                
                $subscription_message = "Plan upgraded. You now have " . convertDurationForDisplay($final_duration_days) . " of " . $plan_name . ".";
            } else if ($plan_base_amount == $current_plan_amount) {
                $subscription_type = "switch";
                $is_switch = true;
                
                $today = new DateTime('now');
                if ($final_duration_days > 0) {
                    $today->modify("+$final_duration_days days");
                    $expires_on = $today->format('Y-m-d H:i:s');
                }
                
                $subscription_message = "Plan switched. You now have " . convertDurationForDisplay($final_duration_days) . " of " . $plan_name . ".";
            }
        }
    } else {
        $subscription_type = "new";
        
        $today = new DateTime('now');
        if ($final_duration_days > 0) {
            $today->modify("+$final_duration_days days");
            $expires_on = $today->format('Y-m-d H:i:s');
        }
        
        $subscription_message = "New subscription activated. You have " . convertDurationForDisplay($final_duration_days) . " of " . $plan_name . ".";
    }
    
    // Format duration for display
    $formatted_duration = convertDurationForDisplay($final_duration_days);
    
    // Get invoice number
    $stmt = $pdo->query("SELECT MAX(invoice_number) AS last_invoice FROM subscription_histories");
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    $invoice_number = ($last['last_invoice'] ?? 0) + 1;
    
    // Get currency symbol
    $currency_symbol = getCurrencySymbol($currency);
    
    // Format payment method for database
    $db_payment_method = 'MP_' . $payment_method;
    
    // Generate payment ID if not provided
    if (empty($payment_id)) {
        $payment_id = 'MP_' . time() . '_' . rand(1000, 9999);
    }
    
    // Insert into subscription_histories
    $insertSql = "INSERT INTO subscription_histories (
        invoice_number, plan_id, user_id, payment_method, payment_id,
        currency, currency_symbol, amount, gst_amount, gst_type, gst_number, gst_percentage,
        discount, name, email, phone, address_1, address_2, state, city,
        pin_code, country, created_at
    ) VALUES (
        :invoice_number, :plan_id, :user_id, :payment_method, :payment_id,
        :currency, :currency_symbol, :amount, :gst_amount, :gst_type, :gst_number, :gst_percentage,
        :discount, :name, :email, :phone, :address_1, :address_2, :state, :city,
        :pin_code, :country, NOW()
    )";

    $insert = $pdo->prepare($insertSql);

    $insertData = [
        ":invoice_number" => $invoice_number,
        ":plan_id" => $plan_id,
        ":user_id" => $user_id,
        ":payment_method" => $db_payment_method,
        ":payment_id" => $payment_id,
        ":currency" => $currency,
        ":currency_symbol" => $currency_symbol,
        ":amount" => $total_amount, // Store total amount including GST (199)
        ":gst_amount" => $gst_amount, // Store GST amount separately (30)
        ":gst_type" => $gst_type,
        ":gst_number" => $gst_number,
        ":gst_percentage" => $gst_percentage,
        ":discount" => 0,
        ":name" => $customer_name,
        ":email" => $customer_email,
        ":phone" => $customer_mobile,
        ":address_1" => $address_1,
        ":address_2" => $address_2,
        ":state" => $state,
        ":city" => $city,
        ":pin_code" => $pincode,
        ":country" => getCountryName($country_code)
    ];

    error_log("Manual Billing Insert Data: " . json_encode($insertData));
    $insert->execute($insertData);
    
    // Update users table
    $updateUserSql = "UPDATE users SET plan_id = :plan_id, expires_on = :expires_on WHERE user_id = :user_id";
    $updateUser = $pdo->prepare($updateUserSql);
    $updateUser->execute([
        ':plan_id' => $plan_id,
        ':expires_on' => $expires_on,
        ':user_id' => $user_id
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Format expiry date for response
    $expiry_date = new DateTime($expires_on);
    $formatted_expiry = $expiry_date->format('d M Y');
    
    $response['success'] = true;
    $response['message'] = 'Manual billing created successfully! Invoice #' . $invoice_number . ' generated.';
    $response['data'] = [
        'invoice_number' => $invoice_number,
        'subscription_type' => $subscription_type,
        'subscription_message' => $subscription_message,
        'user_id' => $user_id,
        'expires_on' => $expires_on,
        'expires_on_formatted' => $formatted_expiry,
        'plan_id' => $plan_id,
        'plan_name' => $plan_name,
        'duration_days' => $final_duration_days,
        'duration_display' => $formatted_duration,
        'is_renewal' => $is_renewal,
        'is_upgrade' => $is_upgrade,
        'is_switch' => $is_switch,
        'amount_details' => [
            'plan_base_amount' => $plan_base_amount, // 169
            'gst_amount' => $gst_amount, // 30
            'total_amount' => $total_amount, // 199
            'admin_entered_amount' => $admin_total_amount // What admin entered
        ],
        'gst_details' => [
            'gst_type' => $gst_type,
            'gst_percentage' => $gst_percentage,
            'gst_amount' => $gst_amount
        ]
    ];

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Error creating manual billing';
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

function getCountryName($country_code) {
    $countries = [
        'IN' => 'India',
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'AE' => 'United Arab Emirates',
        'SG' => 'Singapore',
        'MY' => 'Malaysia'
    ];
    
    return $countries[$country_code] ?? $country_code;
}