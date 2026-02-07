<?php
// manager.bookpanlam/public/seller/payment/verify-razorpay-payment.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Debug raw input
error_log("RAW PAYMENT VERIFY INPUT: " . $input);

// Validate required Razorpay fields
if (
    empty($data['razorpay_payment_id']) ||
    empty($data['razorpay_order_id']) ||
    empty($data['razorpay_signature'])
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing payment verification data"
    ]);
    exit;
}

$payment_id = $data['razorpay_payment_id'];
$order_id = $data['razorpay_order_id'];
$signature = $data['razorpay_signature'];
$billing_data = $data['billing_data'] ?? [];
$plan_data = $data['plan_data'] ?? [];

$logged_in_user_id = $data['logged_in_user_id'] ?? null;

if (!$logged_in_user_id) {
    error_log("❌ logged_in_user_id not received!");
    echo json_encode([
        "success" => false,
        "message" => "User identity missing. Please re-login."
    ]);
    exit;
}

error_log("Logged-in user_id received: " . $logged_in_user_id);

// DB Connection + Razorpay settings
$pdo = getDbConnection();
$sql = "SELECT razorpay_key_secret, currency FROM settings LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings || empty($settings['razorpay_key_secret'])) {
    echo json_encode([
        "success" => false,
        "message" => "Razorpay credentials not configured"
    ]);
    exit;
}

$key_secret = $settings['razorpay_key_secret'];
$currency = $settings['currency'] ?? 'INR';
$currency_symbol = getCurrencySymbol($currency);

// Validate signature
$generated_signature = hash_hmac("sha256", $order_id . "|" . $payment_id, $key_secret);

if ($generated_signature !== $signature) {
    echo json_encode([
        "success" => false,
        "message" => "Payment verification failed - invalid signature"
    ]);
    exit;
}

// Payment verified successfully
try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get invoice number
    $stmt = $pdo->query("SELECT MAX(invoice_number) AS last_invoice FROM subscription_histories");
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    $invoice_number = ($last['last_invoice'] ?? 0) + 1;
    
    // Get plan details from subscription_plans table
    $plan_id = $plan_data["plan_id"] ?? null;
    $duration_days = 0;
    $plan_name = "";
    
    if ($plan_id) {
        $stmt = $pdo->prepare("SELECT duration, name FROM subscription_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        $duration_days = $plan['duration'] ?? 0;
        $plan_name = $plan['name'] ?? "";
    }
    
    // Check if user already has a plan
    $stmt = $pdo->prepare("SELECT plan_id, expires_on FROM users WHERE user_id = ?");
    $stmt->execute([$logged_in_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $current_plan_id = $user['plan_id'] ?? null;
    $current_expires_on = $user['expires_on'] ?? null;
    
    // Determine subscription type and calculate new expiry
    $subscription_type = "new";
    $is_renewal = false;
    $is_upgrade = false;
    $is_switch = false;
    $expires_on = "";
    $subscription_message = "";
    
    // Check if current expiry is in future (plan is still active)
    $current_plan_active = false;
    if ($current_expires_on && $current_expires_on !== '0000-00-00 00:00:00') {
        $current_expiry_date = new DateTime($current_expires_on);
        $today = new DateTime('now');
        $current_plan_active = ($current_expiry_date > $today);
    }
    
    if ($current_plan_id && $current_plan_active) {
        // User has an active plan
        
        if ($current_plan_id == $plan_id) {
            // RENEWAL: Same plan - add duration to existing expiry date
            $subscription_type = "renewal";
            $is_renewal = true;
            
            $current_date = new DateTime($current_expires_on);
            
            if ($duration_days > 0) {
                $current_date->modify("+$duration_days days");
                $expires_on = $current_date->format('Y-m-d H:i:s');
            }
            
            $subscription_message = "Plan renewed. Added " . convertDurationForDisplay($duration_days) . " to your subscription.";
            
            error_log("Renewal detected: User ID $logged_in_user_id renewing $plan_name");
            error_log("Current expiry: $current_expires_on, Adding $duration_days days, New expiry: $expires_on");
        } else {
            // Check if it's upgrade or switch based on price
            $current_plan_stmt = $pdo->prepare("SELECT amount FROM subscription_plans WHERE id = ?");
            $current_plan_stmt->execute([$current_plan_id]);
            $current_plan = $current_plan_stmt->fetch(PDO::FETCH_ASSOC);
            $current_plan_amount = $current_plan['amount'] ?? 0;
            
            $new_plan_amount = $plan_data["amount"] ?? 0;
            
            if ($new_plan_amount > $current_plan_amount) {
                // UPGRADE: Higher priced plan - start from today with new plan duration
                $subscription_type = "upgrade";
                $is_upgrade = true;
                
                $today = new DateTime('now');
                if ($duration_days > 0) {
                    $today->modify("+$duration_days days");
                    $expires_on = $today->format('Y-m-d H:i:s');
                }
                
                $subscription_message = "Plan upgraded. You now have " . convertDurationForDisplay($duration_days) . " of " . $plan_name . ".";
                
                error_log("Upgrade detected: User ID $logged_in_user_id upgrading to $plan_name");
                error_log("Starting from today, New duration: $duration_days days, New expiry: $expires_on");
            } else if ($new_plan_amount == $current_plan_amount) {
                // SWITCH: Same price different plan - start from today with new plan duration
                $subscription_type = "switch";
                $is_switch = true;
                
                $today = new DateTime('now');
                if ($duration_days > 0) {
                    $today->modify("+$duration_days days");
                    $expires_on = $today->format('Y-m-d H:i:s');
                }
                
                $subscription_message = "Plan switched. You now have " . convertDurationForDisplay($duration_days) . " of " . $plan_name . ".";
                
                error_log("Switch detected: User ID $logged_in_user_id switching to $plan_name");
                error_log("Starting from today, New duration: $duration_days days, New expiry: $expires_on");
            } else {
                // DOWNGRADE: Lower priced plan - start from today with new plan duration
                $subscription_type = "downgrade";
                
                $today = new DateTime('now');
                if ($duration_days > 0) {
                    $today->modify("+$duration_days days");
                    $expires_on = $today->format('Y-m-d H:i:s');
                }
                
                $subscription_message = "Plan changed. You now have " . convertDurationForDisplay($duration_days) . " of " . $plan_name . ".";
                
                error_log("Downgrade detected: User ID $logged_in_user_id changing to $plan_name");
                error_log("Starting from today, New duration: $duration_days days, New expiry: $expires_on");
            }
        }
    } else {
        // NEW USER or EXPIRED PLAN: No active plan - start from today
        $subscription_type = "new";
        
        $today = new DateTime('now');
        if ($duration_days > 0) {
            $today->modify("+$duration_days days");
            $expires_on = $today->format('Y-m-d H:i:s');
        }
        
        $subscription_message = "New subscription activated. You have " . convertDurationForDisplay($duration_days) . " of " . $plan_name . ".";
        
        error_log("New subscription for user ID $logged_in_user_id: $plan_name");
    }
    
    // Format duration for display using function from functions.php
    $formatted_duration = convertDurationForDisplay($duration_days);
    
    // Insert subscription record
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
        ":user_id" => $logged_in_user_id,
        ":payment_method" => "razorpay",
        ":payment_id" => $payment_id,
        ":currency" => $plan_data["currency"] ?? $currency,
        ":currency_symbol" => $plan_data["currency_symbol"] ?? $currency_symbol,
        ":amount" => $plan_data["amount"] ?? 0,
        ":gst_amount" => $plan_data["gst_amount"] ?? 0,
        ":gst_type" => $plan_data["gst_type"] ?? null,
        ":gst_number" => $billing_data["gstin"] ?? null,
        ":gst_percentage" => $plan_data["gst_percentage"] ?? null,
        ":discount" => $plan_data["discount"] ?? 0,
        ":name" => $billing_data["name"] ?? "",
        ":email" => $billing_data["email"] ?? "",
        ":phone" => $billing_data["phone"] ?? "",
        ":address_1" => $billing_data["address_1"] ?? "",
        ":address_2" => $billing_data["address_2"] ?? "",
        ":state" => $billing_data["state"] ?? "",
        ":city" => $billing_data["city"] ?? "",
        ":pin_code" => $billing_data["pin_code"] ?? "",
        ":country" => $billing_data["country"] ?? "India"
    ];

    error_log("Inserting subscription history: " . json_encode($insertData));
    $insert->execute($insertData);
    
    // Update users table with plan_id and expiration date
    $updateUserSql = "UPDATE users SET plan_id = :plan_id, expires_on = :expires_on WHERE user_id = :user_id";
    $updateUser = $pdo->prepare($updateUserSql);
    $updateUser->execute([
        ':plan_id' => $plan_id,
        ':expires_on' => $expires_on,
        ':user_id' => $logged_in_user_id
    ]);
    
    $rowsAffected = $updateUser->rowCount();
    error_log("Updated users table. Rows affected: " . $rowsAffected);
    error_log("New expires_on: " . $expires_on . " for user_id: " . $logged_in_user_id);
    
    // Commit transaction
    $pdo->commit();

    // Format expiry date for response
    $expiry_date = new DateTime($expires_on);
    $formatted_expiry = $expiry_date->format('d M Y');
    
    echo json_encode([
        "success" => true,
        "message" => "Payment verified and subscription updated successfully",
        "subscription_type" => $subscription_type,
        "subscription_message" => $subscription_message,
        "invoice_number" => $invoice_number,
        "payment_id" => $payment_id,
        "user_id" => $logged_in_user_id,
        "expires_on" => $expires_on,
        "expires_on_formatted" => $formatted_expiry,
        "plan_id" => $plan_id,
        "plan_name" => $plan_name,
        "duration_days" => $duration_days,
        "duration_display" => $formatted_duration,
        "is_renewal" => $is_renewal,
        "is_upgrade" => $is_upgrade,
        "is_switch" => $is_switch,
        "redirect_url" => "/payment-success?invoice={$invoice_number}"
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("❌ Payment Insert Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>