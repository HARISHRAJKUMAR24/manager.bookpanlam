<?php
require_once '../../../src/database.php';
require_once '../../../src/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$response = [
    'success' => false,
    'data' => [],
    'error' => ''
];

try {
    $pdo = getDbConnection();

    /* ==========================
       SETTINGS (Currency)
    ========================== */
    $defaultCurrency = 'INR';
    
    $stmt = $pdo->prepare("SELECT currency FROM settings LIMIT 1");
    if ($stmt->execute()) {
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($settings['currency'])) {
            $defaultCurrency = $settings['currency'];
        }
    }

    /* ==========================
       SUBSCRIPTION PLANS
    ========================== */
    $stmt = $pdo->prepare("
        SELECT id, name, amount, duration
        FROM subscription_plans
        WHERE is_disabled = 1
        ORDER BY amount ASC
    ");
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ==========================
       USERS - CORRECTED QUERY
    ========================== */
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id AS id,
            u.name,
            u.email,
            u.phone AS mobile,
            u.site_name,
            u.country,
            -- Get latest subscription history for this user
            sh.name AS last_name,
            sh.email AS last_email,
            sh.phone AS last_mobile,
            sh.address_1,
            sh.address_2,
            sh.state,
            sh.city,
            sh.pin_code,
            sh.country AS last_country,
            sh.currency,
            sh.currency_symbol,
            sh.gst_number
        FROM users u
        LEFT JOIN subscription_histories sh ON sh.user_id = u.user_id
        AND sh.created_at = (
            SELECT MAX(created_at) 
            FROM subscription_histories 
            WHERE user_id = u.user_id
        )
        WHERE u.is_suspended = 0
        ORDER BY u.name ASC
    ");
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute users query: " . implode(", ", $stmt->errorInfo()));
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ==========================
       PAYMENT METHODS (STATIC)
    ========================== */
    $paymentMethods = [
        ['value' => 'razorpay', 'label' => 'Razorpay'],
        ['value' => 'phonepe', 'label' => 'PhonePe'],
        ['value' => 'payu', 'label' => 'PayU'],
        ['value' => 'upi', 'label' => 'UPI'],
        ['value' => 'cash_on_hand', 'label' => 'Cash on Hand'],
        ['value' => 'bank_transfer', 'label' => 'Bank Transfer']
    ];

    /* ==========================
       CURRENCIES (STATIC)
    ========================== */
    $currencies = [
        ['code' => 'INR', 'symbol' => '₹', 'name' => 'Indian Rupee'],
        ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar'],
        ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound']
    ];

    /* ==========================
       FINAL RESPONSE
    ========================== */
    $response['success'] = true;
    $response['data'] = [
        'plans' => $plans,
        'users' => $users,
        'payment_methods' => $paymentMethods,
        'currencies' => $currencies,
        'default_currency' => $defaultCurrency
    ];

} catch (Throwable $e) {
    $response['error'] = $e->getMessage();
    error_log("Manual Billing Get Error: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>