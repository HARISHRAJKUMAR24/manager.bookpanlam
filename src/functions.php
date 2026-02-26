<?php

session_start();

function renderTemplate($template, $data = [])
{
    extract($data);
    include __DIR__ . "/../templates/$template.php";
}

function isLoggedIn()
{
    // Check if the user is logged in by verifying if the session variable is set
    return isset($_SESSION['SESSION_EMAIL']);
}

function redirect($url)
{
    echo '<script>window.location.href="' . $url . '"</script>';
}

/**
 * Get duration value from total days
 */
function getDurationValue($totalDays)
{
    if ($totalDays % 365 === 0) {
        return $totalDays / 365; // Years
    } elseif ($totalDays % 30 === 0) {
        return $totalDays / 30; // Months
    }
    return 1; // Default
}

/**
 * Get duration type from total days
 */
function getDurationType($totalDays)
{
    if ($totalDays % 365 === 0) {
        return 'year';
    } elseif ($totalDays % 30 === 0) {
        return 'month';
    }
    return 'year'; // Default
}

/**
 * Convert duration value and type to total days
 */
function convertToDays($value, $type)
{
    if ($type === 'month') {
        return $value * 30; // Approximate month as 30 days
    } elseif ($type === 'year') {
        return $value * 365; // Approximate year as 365 days
    }
    return $value; // Default to days if somehow other type
}

/**
 * Convert total days to display format (e.g., "1 Year", "6 Months")
 */
function convertDurationForDisplay($totalDays)
{
    if ($totalDays % 365 === 0) {
        $years = $totalDays / 365;
        return $years . ($years > 1 ? ' Years' : ' Year');
    } elseif ($totalDays % 30 === 0) {
        $months = $totalDays / 30;
        return $months . ($months > 1 ? ' Months' : ' Month');
    }
    return $totalDays . ' Days'; // Fallback
}

function getCurrencySymbol($currency)
{
    if ($currency == 'USD') {
        return '$';
    } elseif ($currency == 'INR') {
        return '₹';
    } else {
        return $currency;
    }
}

function uuid()
{
    return sprintf(
        '%s-%s-%s-%s-%s',
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(6))
    );
}

function uploadImage($file, $folder = 'uploads')
{
    // Get the absolute path to uploads folder
    $uploadPath = __DIR__ . '/../uploads/' . $folder . '/';

    // Create directory if it doesn't exist
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $fileExtension;
    $filePath = $uploadPath . $fileName;

    // Check file type
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($fileExtension), $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large'];
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_name' => $folder . '/' . $fileName];
    }

    return ['success' => false, 'error' => 'Upload failed'];
}

// ---------------------- get data from database ---------------------- //
function getData($column, $table, $condition = "")
{
    $pdo = getDbConnection();

    if (empty($condition)) {
        $sql = "SELECT $column FROM $table LIMIT 1";
    } else {
        $sql = "SELECT $column FROM $table WHERE $condition LIMIT 1";
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount()) {
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            return $row->$column;
        }

        return null;
    } catch (PDOException $e) {
        error_log("Database error in getData(): " . $e->getMessage());
        return null;
    }
}

// ---------------------- Get timezone from settings ---------------------- //
function getAppTimezone()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT timezone FROM settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_OBJ);
        return $settings->timezone ?? 'Asia/Kolkata';
    } catch (Exception $e) {
        return 'Asia/Kolkata';
    }
}

// ---------------------- Get current time in app timezone ---------------------- //
function getCurrentAppTime($format = 'Y-m-d H:i:s')
{
    $timezone = getAppTimezone();
    try {
        $date = new DateTime('now', new DateTimeZone($timezone));
        return $date->format($format);
    } catch (Exception $e) {
        $date = new DateTime('now');
        return $date->format($format);
    }
}

// ---------------------- Convert any datetime to app timezone ---------------------- //
function convertToAppTimezone($datetime, $format = 'Y-m-d H:i:s')
{
    $timezone = getAppTimezone();
    try {
        $date = new DateTime($datetime, new DateTimeZone('UTC')); // Assuming datetime is stored as UTC
        $date->setTimezone(new DateTimeZone($timezone));
        return $date->format($format);
    } catch (Exception $e) {
        // If conversion fails, try without assuming UTC
        try {
            $date = new DateTime($datetime);
            $date->setTimezone(new DateTimeZone($timezone));
            return $date->format($format);
        } catch (Exception $e2) {
            return $datetime;
        }
    }
}

//  ---------------------- Calculate expiry date using app timezone - FIXED VERSION ---------------------- //
function calculateExpiryDate($value, $type)
{
    $timezone = getAppTimezone();

    try {
        // Create current time in app timezone
        $currentTime = new DateTime('now', new DateTimeZone($timezone));

        // Add the interval based on type
        switch ($type) {
            case 'hours':
                $interval = new DateInterval("PT{$value}H");
                break;
            case 'days':
                $interval = new DateInterval("P{$value}D");
                break;
            case 'weeks':
                $interval = new DateInterval("P{$value}W");
                break;
            case 'months':
                $interval = new DateInterval("P{$value}M");
                break;
            default:
                $interval = new DateInterval("PT1H"); // Default 1 hour
        }

        $currentTime->add($interval);

        // Convert to UTC before storing in database
        $currentTime->setTimezone(new DateTimeZone('UTC'));
        return $currentTime->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        // Fallback - use UTC directly
        $utcNow = new DateTime('now', new DateTimeZone('UTC'));
        $utcNow->add(new DateInterval("PT{$value}H")); // Default to hours
        return $utcNow->format('Y-m-d H:i:s');
    }
}

//  ---------------------- Check if message is expired considering timezone - FIXED ---------------------- //
function isMessageExpired($expiryDate)
{
    try {
        $timezone = getAppTimezone();

        // Expiry date is stored as UTC in database
        $expiryTime = new DateTime($expiryDate, new DateTimeZone('UTC'));
        $expiryTime->setTimezone(new DateTimeZone($timezone));

        // Current time in app timezone
        $currentTime = new DateTime('now', new DateTimeZone($timezone));

        return $currentTime > $expiryTime;
    } catch (Exception $e) {
        // Fallback string comparison
        $currentAppTime = getCurrentAppTime('Y-m-d H:i:s');
        return strtotime($currentAppTime) > strtotime($expiryDate);
    }
}

//  ---------------------- Check if seller is newly created (for just_created_seller feature) ---------------------- 
function isNewlyCreatedSeller($sellerCreatedAt, $messageCreatedAt)
{
    try {
        $timezone = getAppTimezone();

        $sellerTime = new DateTime($sellerCreatedAt, new DateTimeZone('UTC'));
        $sellerTime->setTimezone(new DateTimeZone($timezone));

        $messageTime = new DateTime($messageCreatedAt, new DateTimeZone('UTC'));
        $messageTime->setTimezone(new DateTimeZone($timezone));

        return $sellerTime > $messageTime;
    } catch (Exception $e) {
        return strtotime($sellerCreatedAt) > strtotime($messageCreatedAt);
    }
}


//---------------------- Get user's plan limit for a specific resource WITH EXPIRY CHECK  ---------------------- //

function getUserPlanLimit($user_id, $resource_type)
{
    $pdo = getDbConnection();

    // First check if user's plan is expired
    $expirySql = "SELECT plan_id, expires_on FROM users WHERE user_id = ?";
    $expiryStmt = $pdo->prepare($expirySql);
    $expiryStmt->execute([$user_id]);
    $userData = $expiryStmt->fetch(PDO::FETCH_ASSOC);

    $plan_expired = false;
    $plan_expired_message = '';

    if ($userData && $userData['expires_on'] && $userData['expires_on'] !== '0000-00-00 00:00:00') {
        $expiry_date = new DateTime($userData['expires_on']);
        $today = new DateTime('now');
        $plan_expired = ($expiry_date < $today);

        if ($plan_expired) {
            $plan_expired_message = "Your plan has expired. Please renew to continue using all features.";
        }
    }

    // Map resource types to column names - ADDED: upi_payment_methods
    $column_map = [
        'appointments' => 'appointments_limit',
        'customers' => 'customers_limit',
        'services' => 'services_limit',
        'menu' => 'menu_limit',
        'menu_items' => 'menu_limit',
        'coupons' => 'coupons_limit',
        'manual_payment_methods' => 'manual_payment_methods_limit',
        'upi_payment_methods' => 'upi_payment_methods_limit', // ✅ ADDED
        'free_credits' => 'free_credits'
    ];

    // Alias mapping for backward compatibility
    if ($resource_type === 'menu') {
        $resource_type = 'menu_items';
    }

    if (!isset($column_map[$resource_type])) {
        return [
            'can_add' => false,
            'message' => 'Invalid resource type: ' . $resource_type,
            'current' => 0,
            'limit' => 0,
            'remaining' => 0,
            'plan_expired' => $plan_expired,
            'expiry_message' => $plan_expired_message
        ];
    }

    $column = $column_map[$resource_type];

    // Get user's current plan
    $stmt = $pdo->prepare("
        SELECT u.plan_id, sp.$column as limit_value
        FROM users u 
        LEFT JOIN subscription_plans sp ON u.plan_id = sp.id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If plan expired, restrict to 0 for all resources
    if ($plan_expired) {
        // Get current count for this resource
        $current_count = 0;

        if ($resource_type === 'services') {
            // Count only departments + categories for services limit
            $stmt = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM departments WHERE user_id = ?) +
                    (SELECT COUNT(*) FROM categories WHERE user_id = ?) as total_count
            ");
            $stmt->execute([$user_id, $user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $current_count = $result['total_count'] ?? 0;
        } elseif ($resource_type === 'menu_items') {
            // Count menu items for menu limit
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM menu_items WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $current_count = $result['count'] ?? 0;
        } else {
            // Normal counting for other resources
            $table_map = [
                'appointments' => 'appointments',
                'customers' => 'customers',
                'coupons' => 'coupons',
                'manual_payment_methods' => 'manual_payment_methods',
                'upi_payment_methods' => 'upi_payment_methods' // ✅ ADDED
            ];

            $current_count = 0;
            if (isset($table_map[$resource_type])) {
                $table = $table_map[$resource_type];
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $current_count = $result['count'] ?? 0;
            }
        }

        return [
            'can_add' => false,
            'message' => $plan_expired_message,
            'current' => $current_count,
            'limit' => 0,
            'remaining' => 0,
            'plan_expired' => true,
            'expiry_message' => $plan_expired_message
        ];
    }

    // ✅ User without a plan (new user) - allow 1 resource
    if (!$user || $user['plan_id'] === null) {
        $limit = 1; // Allow only 1 resource for users without plan

        // Get current count for this resource
        if ($resource_type === 'services') {
            // Count only departments + categories for services limit
            $stmt = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM departments WHERE user_id = ?) +
                    (SELECT COUNT(*) FROM categories WHERE user_id = ?) as total_count
            ");
            $stmt->execute([$user_id, $user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $current_count = $result['total_count'] ?? 0;

            if ($current_count >= $limit) {
                return [
                    'can_add' => false,
                    'message' => "You can only create 1 without a plan.<br>Please subscribe to a plan to add more.",
                    'current' => $current_count,
                    'limit' => $limit,
                    'remaining' => 0,
                    'plan_expired' => false,
                    'expiry_message' => ''
                ];
            }

            return [
                'can_add' => true,
                'message' => "You can only create 1 without a plan.<br>Please subscribe to a plan to add more.",
                'current' => $current_count,
                'limit' => $limit,
                'remaining' => $limit - $current_count,
                'plan_expired' => false,
                'expiry_message' => ''
            ];
        } elseif ($resource_type === 'menu_items') {
            // Count menu items for menu limit
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM menu_items WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $current_count = $result['count'] ?? 0;

            if ($current_count >= $limit) {
                return [
                    'can_add' => false,
                    'message' => "You can only create 1 menu item without a plan.<br>Please subscribe to a plan to add more.",
                    'current' => $current_count,
                    'limit' => $limit,
                    'remaining' => 0,
                    'plan_expired' => false,
                    'expiry_message' => ''
                ];
            }

            return [
                'can_add' => true,
                'message' => "You can create 1 menu item without a plan. Subscribe to add more.",
                'current' => $current_count,
                'limit' => $limit,
                'remaining' => $limit - $current_count,
                'plan_expired' => false,
                'expiry_message' => ''
            ];
        } else {
            // Normal counting for other resources
            $table_map = [
                'appointments' => 'appointments',
                'customers' => 'customers',
                'coupons' => 'coupons',
                'manual_payment_methods' => 'manual_payment_methods',
                'upi_payment_methods' => 'upi_payment_methods' // ✅ ADDED
            ];

            $current_count = 0;
            if (isset($table_map[$resource_type])) {
                $table = $table_map[$resource_type];
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $current_count = $result['count'] ?? 0;
            }

            if ($current_count >= $limit) {
                return [
                    'can_add' => false,
                    'message' => "You can only create 1 {$resource_type} without a plan.<br>Please subscribe to a plan to add more.",
                    'current' => $current_count,
                    'limit' => $limit,
                    'remaining' => 0,
                    'plan_expired' => false,
                    'expiry_message' => ''
                ];
            }

            return [
                'can_add' => true,
                'message' => "You can create 1 {$resource_type} without a plan. Subscribe to add more.",
                'current' => $current_count,
                'limit' => $limit,
                'remaining' => $limit - $current_count,
                'plan_expired' => false,
                'expiry_message' => ''
            ];
        }
    }

    $limit_value = $user['limit_value'];

    // Check if unlimited
    if ($limit_value === 'unlimited') {
        return [
            'can_add' => true,
            'message' => 'Unlimited usage',
            'current' => 0,
            'limit' => 'unlimited',
            'remaining' => 'unlimited',
            'plan_expired' => false,
            'expiry_message' => ''
        ];
    }

    $limit = (int)$limit_value;

    // Get current count for this resource
    $current_count = 0;

    if ($resource_type === 'services') {
        // ✅ SERVICES: Count only departments + categories
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM departments WHERE user_id = ?) as dept_count,
                (SELECT COUNT(*) FROM categories WHERE user_id = ?) as cat_count
        ");
        $stmt->execute([$user_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $current_count = ($result['dept_count'] ?? 0) + ($result['cat_count'] ?? 0);
    } elseif ($resource_type === 'menu_items') {
        // ✅ MENU ITEMS: Count menu_items only
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM menu_items WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_count = $result['count'] ?? 0;
    } else {
        // Normal counting for other resources
        $table_map = [
            'appointments' => 'appointments',
            'customers' => 'customers',
            'coupons' => 'coupons',
            'manual_payment_methods' => 'manual_payment_methods',
            'upi_payment_methods' => 'upi_payment_methods' // ✅ ADDED
        ];

        if (isset($table_map[$resource_type])) {
            $table = $table_map[$resource_type];
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $current_count = $result['count'] ?? 0;
        }
    }

    // For free_credits, handle differently (not a count but a value)
    if ($resource_type === 'free_credits') {
        return [
            'can_add' => true,
            'message' => "Your plan includes {$limit_value} free credits",
            'current' => 0,
            'limit' => $limit_value,
            'remaining' => $limit_value,
            'plan_expired' => false,
            'expiry_message' => ''
        ];
    }

    if ($current_count >= $limit) {
        return [
            'can_add' => false,
            'message' => "You have reached your limit ({$limit}).<br>Please upgrade your plan to add more.",
            'current' => $current_count,
            'limit' => $limit,
            'remaining' => 0,
            'plan_expired' => false,
            'expiry_message' => ''
        ];
    }

    return [
        'can_add' => true,
        'message' => "You can add " . ($limit - $current_count) . " more.",
        'current' => $current_count,
        'limit' => $limit,
        'remaining' => $limit - $current_count,
        'plan_expired' => false,
        'expiry_message' => ''
    ];
}

// ----------------------  Check if user can add a specific resource ---------------------- //

function canUserAddResource($user_id, $resource_type)
{
    $result = getUserPlanLimit($user_id, $resource_type);
    return $result['can_add'];
}

// ----------------------  Get user's resource usage summary WITH EXPIRY CHECK ---------------------- 

function getUserResourceUsage($user_id, $resource_type = null)
{
    if ($resource_type) {
        // Get specific resource usage
        return getUserPlanLimit($user_id, $resource_type);
    } else {
        // Get all resource usage (now includes menu_items)
        $resources = ['appointments', 'customers', 'services', 'coupons', 'manual_payment_methods', 'menu_items'];
        $usage = [];

        foreach ($resources as $resource) {
            $usage[$resource] = getUserPlanLimit($user_id, $resource);
        }

        return $usage;
    }
}

// ----------------------  Validate resource limit before adding (use in your API files) WITH EXPIRY CHECK ---------------------- 

function validateResourceLimit($user_id, $resource_type)
{
    $result = getUserPlanLimit($user_id, $resource_type);

    if (!$result['can_add']) {
        http_response_code(403); // Forbidden
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'current' => $result['current'],
            'limit' => $result['limit'],
            'remaining' => $result['remaining'],
            'plan_expired' => $result['plan_expired'] ?? false,
            'expiry_message' => $result['expiry_message'] ?? ''
        ]);
        exit();
    }

    return $result;
}


// ------------- Get actual resource count for user based on service type  ---------------------- 
function getUserActualResourcesCount($user_id)
{
    $pdo = getDbConnection();

    // First get user's service_type_id
    $stmt = $pdo->prepare("SELECT service_type_id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return [
            'services_count' => 0,
            'services_label' => 'Services',
            'menu_items_count' => 0,
            'menu_items_label' => 'Menu Items'
        ];
    }

    $service_type_id = $user['service_type_id'];
    $services_count = 0;
    $menu_items_count = 0;
    $services_label = 'Services';
    $menu_items_label = 'Menu Items';

    // Determine what to count based on service_type_id
    switch ($service_type_id) {
        case 1: // HOSPITAL - count categories
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $services_count = $result['count'] ?? 0;
            $services_label = 'Categories';
            break;

        case 2: // HOTEL - count menu_items
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM menu_items WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $menu_items_count = $result['count'] ?? 0;
            $services_label = 'Menu Items';
            break;

        case 3: // OTHER - count departments
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM departments WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $services_count = $result['count'] ?? 0;
            $services_label = 'Services';
            break;

        default:
            // Count both as fallback
            $stmt = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM categories WHERE user_id = ?) as cat_count,
                    (SELECT COUNT(*) FROM departments WHERE user_id = ?) as dept_count,
                    (SELECT COUNT(*) FROM menu_items WHERE user_id = ?) as menu_count
            ");
            $stmt->execute([$user_id, $user_id, $user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $services_count = ($result['cat_count'] ?? 0) + ($result['dept_count'] ?? 0);
            $menu_items_count = $result['menu_count'] ?? 0;
            break;
    }

    return [
        'services_count' => $services_count,
        'services_label' => $services_label,
        'menu_items_count' => $menu_items_count,
        'menu_items_label' => $menu_items_label
    ];
}

//  ----------------------  Enhanced version of getUserPlanLimit with actual counts display  ---------------------- //
function getUserPlanLimitWithActual($user_id, $resource_type)
{
    // First get the standard plan limit
    $planLimit = getUserPlanLimit($user_id, $resource_type);

    // Get actual resource counts based on service type
    $actualCounts = getUserActualResourcesCount($user_id);

    // Update the response based on resource type
    if ($resource_type === 'services') {
        $planLimit['actual_count'] = $actualCounts['services_count'];
        $planLimit['label'] = $actualCounts['services_label'];
    } elseif ($resource_type === 'menu_items') {
        $planLimit['actual_count'] = $actualCounts['menu_items_count'];
        $planLimit['label'] = $actualCounts['menu_items_label'];
    } else {
        // For other resources, get actual count from database
        $pdo = getDbConnection();
        $table_map = [
            'appointments' => 'appointments',
            'customers' => 'customers',
            'coupons' => 'coupons',
            'manual_payment_methods' => 'manual_payment_methods',
            'upi_payment_methods' => 'upi_payment_methods' // ✅ ADDED
        ];

        if (isset($table_map[$resource_type])) {
            $table = $table_map[$resource_type];
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $planLimit['actual_count'] = $result['count'] ?? 0;
            $planLimit['label'] = ucfirst(str_replace('_', ' ', $resource_type));
        }
    }

    return $planLimit;
}



// ------------- Get actual customer count for a specific user This counts how many customers belong to a specific user_id  ---------------------- //
function getActualCustomerCount($user_id)
{
    $pdo = getDbConnection();

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as customer_count FROM customers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['customer_count'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting customer count: " . $e->getMessage());
        return 0;
    }
}

// ----------------------  Get customer limit info with actual count  ---------------------- //
function getCustomerLimitWithCount($user_id)
{
    // Get plan limit
    $planLimit = getUserPlanLimit($user_id, 'customers');

    // Get actual count
    $actualCount = getActualCustomerCount($user_id);

    // Combine the data
    $planLimit['actual_count'] = $actualCount;
    $planLimit['label'] = 'Customers';

    return $planLimit;
}


// ---------------------- Generate Appoimnet id Universal work  ---------------------- //
function generateAppointmentId($user_id, $db)
{

    // Section 1: User ID
    $section1 = (string)$user_id;

    // Section 2: Get service type code
    $stmt = $db->prepare("
        SELECT st.code, st.id as service_type_id 
        FROM users u 
        LEFT JOIN service_types st ON u.service_type_id = st.id 
        WHERE u.user_id = ? 
        LIMIT 1
    ");

    if ($stmt->execute([$user_id])) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['code'])) {
            // Get first 3 letters of service type code
            $code = strtoupper(substr($result['code'], 0, 3));
            $service_type_id = $result['service_type_id'] ?? 1;
            $section2 = $code . $service_type_id;
        } else {
            // Fallback if no service type found
            $section2 = 'DEF1';
        }
    } else {
        // Error in query, use fallback
        $section2 = 'DEF1';
    }

    // Section 3: Random string (4-5 chars, lowercase only)
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $length = rand(4, 5);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }

    $section3 = $randomString;

    // Combine all sections
    $appointmentId = $section1 . $section2 . $section3;

    return $appointmentId;
}

//  ----------------------  Get service information based on user's service type  ---------------------- //


function getServiceInformation($db, $user_id, $service_type, $category_id = null, $service_name = '', $services_json = null)
{
    try {
        // First get user's service_type_id
        $stmt = $db->prepare("
            SELECT u.service_type_id, st.code, st.name as service_type_name
            FROM users u 
            LEFT JOIN service_types st ON u.service_type_id = st.id 
            WHERE u.user_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                "success" => false,
                "message" => "User not found"
            ];
        }

        $service_type_id = $user['service_type_id'];
        $service_type_name = $user['service_type_name'] ?? 'Others';

        $reference_id = null;
        $reference_type = null;
        $service_name_json = '';

        // ⭐ If services_json is provided, use it
        if ($services_json) {
            $service_name_json = json_encode($services_json);

            // Determine reference based on service type
            if ($service_type === 'department') {
                $reference_type = 'department_id';
                $reference_id = $category_id;
            } else {
                $reference_type = 'category_id';
                $reference_id = $category_id;
            }

            return [
                "success" => true,
                "reference_id" => $reference_id,
                "reference_type" => $reference_type,
                "service_name_json" => $service_name_json,
                "service_name_display" => $service_name_json['department_name'] ?? $service_name,
                "service_type_id" => $service_type_id,
                "service_type_name" => $service_type_name
            ];
        }

        // For HOSPITAL type (service_type_id = 1)
        if ($service_type_id == 1) {
            if ($service_type === 'department') {
                // Department booking
                if ($category_id) {
                    $stmt = $db->prepare("
                        SELECT department_id, name 
                        FROM departments 
                        WHERE department_id = ? 
                        AND user_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$category_id, $user_id]);
                    $department = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($department) {
                        $reference_id = $department['department_id'];
                        $reference_type = 'department_id';
                        $service_name_json = json_encode([
                            "type" => "department",
                            "department_name" => $department['name'] ?? $service_name,
                            "service_type" => "Hospital Department"
                        ]);
                    }
                }
            } else {
                // Category/Doctor booking
                if ($category_id) {
                    $stmt = $db->prepare("
                        SELECT category_id, name, doctor_name, specialization 
                        FROM categories 
                        WHERE category_id = ? 
                        AND user_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$category_id, $user_id]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($category) {
                        $reference_id = $category['category_id'];
                        $reference_type = 'category_id';
                        $service_name_json = json_encode([
                            "type" => "doctor",
                            "doctor_name" => $category['doctor_name'] ?? $category['name'],
                            "specialization" => $category['specialization'] ?? '',
                            "service_type" => "Hospital Consultation"
                        ]);
                    }
                }
            }
        } else {
            // For other service types (HOTEL=2, OTHERS=3)
            if ($category_id) {
                $stmt = $db->prepare("
                    SELECT category_id, name 
                    FROM categories 
                    WHERE category_id = ? 
                    AND user_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$category_id, $user_id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($category) {
                    $reference_id = $category['category_id'];
                    $reference_type = 'category_id';

                    if ($service_type_id == 2) { // HOTEL
                        $service_name_json = json_encode([
                            "type" => "hotel_service",
                            "service_name" => $category['name'] ?? $service_name,
                            "service_type" => "Hotel Service"
                        ]);
                    } else { // OTHERS
                        $service_name_json = json_encode([
                            "type" => "service",
                            "service_name" => $category['name'] ?? $service_name,
                            "service_type" => "General Service"
                        ]);
                    }
                }
            }
        }

        // If no specific service found
        if (!$reference_id) {
            if ($service_name) {
                $reference_id = 'CUSTOM_' . uniqid();
                $reference_type = 'custom_service';
                $service_name_json = json_encode([
                    "type" => "custom",
                    "service_name" => $service_name,
                    "service_type" => $service_type_name
                ]);
            } else {
                $reference_id = 'GENERIC_' . $user_id;
                $reference_type = 'generic_service';
                $service_name_json = json_encode([
                    "type" => "generic",
                    "service_name" => "Service Booking",
                    "service_type" => $service_type_name
                ]);
            }
        }

        return [
            "success" => true,
            "reference_id" => $reference_id,
            "reference_type" => $reference_type,
            "service_name_json" => $service_name_json,
            "service_name_display" => $service_name ?: "Service Booking",
            "service_type_id" => $service_type_id,
            "service_type_name" => $service_type_name
        ];
    } catch (Exception $e) {
        error_log("getServiceInformation error: " . $e->getMessage());
        return [
            "success" => false,
            "message" => "Error getting service information: " . $e->getMessage()
        ];
    }
}


// ---------------------- Check token availability for a specific doctor's batch ---------------------- //

function checkTokenAvailability($userId, $batchId, $appointmentDate, $pdo = null)
{
    try {
        if ($pdo === null) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/database.php";
            $pdo = getDbConnection();
        }

        // Get doctor's schedule to find token limit for this batch
        $scheduleStmt = $pdo->prepare("
            SELECT token_limit, weekly_schedule 
            FROM doctor_schedule 
            WHERE user_id = ?
        ");
        $scheduleStmt->execute([$userId]);
        $doctor = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            return [
                'available' => false,
                'message' => 'Doctor schedule not found',
                'booked' => 0,
                'total' => 0,
                'remaining' => 0
            ];
        }

        $tokenLimit = (int)$doctor['token_limit'];

        // Parse weekly schedule to get token for this specific batch
        $weeklySchedule = !empty($doctor['weekly_schedule'])
            ? json_decode($doctor['weekly_schedule'], true)
            : [];

        $batchToken = 0;

        // Find the token count for this batch from weekly schedule
        foreach ($weeklySchedule as $day => $daySchedule) {
            if (!empty($daySchedule['slots'])) {
                foreach ($daySchedule['slots'] as $slot) {
                    if (isset($slot['batch_id']) && $slot['batch_id'] == $batchId) {
                        $batchToken = isset($slot['token']) ? (int)$slot['token'] : $tokenLimit;
                        break 2;
                    }
                }
            }
        }

        // If batch not found in schedule
        if ($batchToken === 0) {
            return [
                'available' => false,
                'message' => 'Batch not found in schedule',
                'booked' => 0,
                'total' => 0,
                'remaining' => 0
            ];
        }

        // Count how many appointments are already booked for this batch and date
        $bookingStmt = $pdo->prepare("
            SELECT SUM(token_count) as total_booked 
            FROM customer_payment 
            WHERE user_id = ? 
            AND batch_id = ? 
            AND appointment_date = ? 
            AND status IN ('paid', 'pending', 'confirmed')
        ");

        $bookingStmt->execute([$userId, $batchId, $appointmentDate]);
        $result = $bookingStmt->fetch(PDO::FETCH_ASSOC);

        $bookedCount = (int)($result['total_booked'] ?? 0);

        // Calculate remaining tokens
        $remainingTokens = max(0, $batchToken - $bookedCount);

        // Determine availability
        $isAvailable = $remainingTokens > 0;

        return [
            'available' => $isAvailable,
            'message' => $isAvailable
                ? "Available ($remainingTokens tokens left)"
                : "Appointment full ($bookedCount/$batchToken)",
            'booked' => $bookedCount,
            'total' => $batchToken,
            'remaining' => $remainingTokens,
            'token_limit' => $tokenLimit,
            'batch_token' => $batchToken
        ];
    } catch (Exception $e) {
        return [
            'available' => false,
            'message' => 'Error checking availability: ' . $e->getMessage(),
            'booked' => 0,
            'total' => 0,
            'remaining' => 0
        ];
    }
}

// ---------------------- Get available slots for a specific date with token availability  ---------------------- //
function getAvailableSlotsForDate($userId, $date, $pdo = null)
{
    try {
        if ($pdo === null) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/database.php";
            $pdo = getDbConnection();
        }

        // Step 1: Get doctor's schedule
        $scheduleStmt = $pdo->prepare("
            SELECT weekly_schedule, leave_dates, token_limit
            FROM doctor_schedule 
            WHERE user_id = ?
        ");
        $scheduleStmt->execute([$userId]);
        $doctor = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            return ['available' => false, 'slots' => [], 'message' => 'Doctor not found'];
        }

        // Check if date is a leave day
        $leaveDates = !empty($doctor['leave_dates'])
            ? json_decode($doctor['leave_dates'], true)
            : [];

        if (in_array($date, $leaveDates)) {
            return ['available' => false, 'slots' => [], 'message' => 'Doctor is on leave'];
        }

        // Get day of week for this date
        $dateTime = new DateTime($date);
        $dayOfWeek = $dateTime->format('D'); // Returns "Sun", "Mon", etc.

        $weeklySchedule = !empty($doctor['weekly_schedule'])
            ? json_decode($doctor['weekly_schedule'], true)
            : [];

        // Check if doctor has schedule for this day
        if (!isset($weeklySchedule[$dayOfWeek]) || !$weeklySchedule[$dayOfWeek]['enabled']) {
            return ['available' => false, 'slots' => [], 'message' => 'No schedule for this day'];
        }

        $daySchedule = $weeklySchedule[$dayOfWeek];
        $availableSlots = [];

        // Step 2: Check each slot's availability
        if (!empty($daySchedule['slots'])) {
            foreach ($daySchedule['slots'] as $slot) {
                $batchId = $slot['batch_id'] ?? '';

                if (!$batchId) {
                    continue;
                }

                // Check token availability for this batch
                $availability = checkTokenAvailability($userId, $batchId, $date, $pdo);

                if ($availability['available']) {
                    $slot['availability'] = $availability;
                    $slot['available_tokens'] = $availability['remaining'];
                    $slot['booked_tokens'] = $availability['booked'];
                    $slot['total_tokens'] = $availability['total'];
                    $availableSlots[] = $slot;
                }
            }
        }

        return [
            'available' => count($availableSlots) > 0,
            'slots' => $availableSlots,
            'message' => count($availableSlots) > 0
                ? count($availableSlots) . ' slot(s) available'
                : 'No available slots',
            'date' => $date,
            'day' => $dayOfWeek
        ];
    } catch (Exception $e) {
        return [
            'available' => false,
            'slots' => [],
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * API endpoint to check slot availability
 */
function apiCheckSlotAvailability()
{
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");

    try {
        require_once __DIR__ . "/../../../config/config.php";
        require_once __DIR__ . "/../../../src/database.php";
        $pdo = getDbConnection();

        $userId = (int)($_GET['user_id'] ?? 0);
        $batchId = $_GET['batch_id'] ?? '';
        $date = $_GET['date'] ?? '';

        if (!$userId || !$batchId || !$date) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing parameters: user_id, batch_id, and date are required'
            ]);
            exit;
        }

        // Validate date format
        if (!DateTime::createFromFormat('Y-m-d', $date)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date format. Use YYYY-MM-DD'
            ]);
            exit;
        }

        $availability = checkTokenAvailability($userId, $batchId, $date, $pdo);

        echo json_encode([
            'success' => true,
            'data' => $availability
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}


// ---------------------- Token Histroy send to db token_history ---------------------- //
function compareAndLogTokenUpdates($oldSchedule, $newSchedule, $scheduleId, $pdo, $categoryId)
{
    $oldSlots = [];
    $newSlots = [];

    // Parse old slots
    $oldScheduleArray = json_decode($oldSchedule, true);
    foreach ($oldScheduleArray as $day => $dayData) {
        if ($dayData['enabled'] && !empty($dayData['slots'])) {
            foreach ($dayData['slots'] as $slot) {
                if (isset($slot['batch_id'])) {
                    $oldSlots[$slot['batch_id']] = [
                        'token' => (int)($slot['token'] ?? 0),
                        'batch_id' => $slot['batch_id']
                    ];
                }
            }
        }
    }

    // Parse new slots
    $newScheduleArray = json_decode($newSchedule, true);
    foreach ($newScheduleArray as $day => $dayData) {
        if ($dayData['enabled'] && !empty($dayData['slots'])) {
            foreach ($dayData['slots'] as $slot) {
                if (isset($slot['batch_id'])) {
                    $newSlots[$slot['batch_id']] = [
                        'token' => (int)($slot['token'] ?? 0),
                        'batch_id' => $slot['batch_id']
                    ];
                }
            }
        }
    }

    // Find differences
    $updates = [];
    foreach ($newSlots as $batchId => $newData) {
        $oldData = $oldSlots[$batchId] ?? null;
        $oldToken = $oldData['token'] ?? 0;
        $newToken = $newData['token'];

        if ($oldToken !== $newToken) {
            // Parse slot_index from batch_id
            $slotIndex = null;
            $parts = explode(':', $batchId);
            if (isset($parts[1])) {
                $slotIndex = (int)$parts[1];
            }

            $updates[] = [
                'batch_id' => $batchId,
                'slot_index' => $slotIndex,
                'old_token' => $oldToken,
                'new_token' => $newToken,
                'total_token' => $newToken
            ];
        }
    }

    // Log to history table
    if (!empty($updates)) {
        $historyStmt = $pdo->prepare("
            INSERT INTO doctor_token_history 
            (category_id, slot_batch_id, slot_index, old_token, new_token, total_token, doctor_schedule_id_temp, updated_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($updates as $update) {
            $historyStmt->execute([
                $categoryId,
                $update['batch_id'],
                $update['slot_index'],
                $update['old_token'],
                $update['new_token'],
                $update['total_token'],
                $scheduleId,
                $_SESSION['user_id'] ?? null
            ]);
        }
    }
}

// ---------------------- Check if COH should be shown to user ---------------------- //
// ---------------------- Check if COH should be shown based on user's plan limit ---------------------- //
function canShowCOH($user_id)
{
    $pdo = getDbConnection();

    // Get user's plan
    $stmt = $pdo->prepare("
        SELECT u.plan_id, sp.manual_payment_methods_limit 
        FROM users u 
        LEFT JOIN subscription_plans sp ON u.plan_id = sp.id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no plan or unlimited, show COH
    if (!$user || $user['manual_payment_methods_limit'] === 'unlimited') {
        return true;
    }

    // ✅ FIXED: Check current count of CASH payments in customer_payment table
    $limit = (int)$user['manual_payment_methods_limit'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customer_payment WHERE user_id = ? AND payment_method = 'cash'");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_count = $result['count'] ?? 0;

    // Show COH only if under limit
    return $current_count < $limit;
}

// ---------------------- Check if UPI should be shown based on user's plan limit ---------------------- //
function canShowUPI($user_id)
{
    $pdo = getDbConnection();

    // Get user's plan
    $stmt = $pdo->prepare("
        SELECT u.plan_id, sp.upi_payment_methods_limit 
        FROM users u 
        LEFT JOIN subscription_plans sp ON u.plan_id = sp.id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no plan or unlimited, show UPI
    if (!$user || $user['upi_payment_methods_limit'] === 'unlimited') {
        return true;
    }

    // ✅ FIXED: Check current count of UPI payments in customer_payment table
    $limit = (int)$user['upi_payment_methods_limit'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customer_payment WHERE user_id = ? AND payment_method = 'upi'");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_count = $result['count'] ?? 0;

    // Show UPI only if under limit
    return $current_count < $limit;
}



//============================================================================//
/**
 * Get subscription purchase history ONLY when customer payments exist
 * This shows seller's subscription invoices only if they have received customer payments
 */
function getSubscriptionPurchaseHistory($pdo, $userId)
{
    try {
        $currency_symbol = '₹';

        // First check if this seller has ANY customer payments
        $checkCustomerSql = "SELECT COUNT(*) as payment_count 
                            FROM customer_payment 
                            WHERE user_id = ? AND status = 'paid'";
        $checkStmt = $pdo->prepare($checkCustomerSql);
        $checkStmt->execute([$userId]);
        $customerPayments = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // If no customer payments, return empty array (no subscription invoices shown)
        if (!$customerPayments || $customerPayments['payment_count'] == 0) {
            error_log("No customer payments found for user: " . $userId . " - hiding subscription invoices");
            return [];
        }

        // Only get subscription histories if customer payments exist
        $sql = "SELECT 
                    sh.id,
                    sh.invoice_number,
                    sh.plan_id,
                    COALESCE(sp.name, 'Subscription Plan') as plan_name,
                    sh.amount,
                    sh.payment_method,
                    sh.payment_id,
                    sh.created_at,
                    sh.name as customer_name,
                    sh.email as customer_email,
                    sh.phone,
                    sh.address_1,
                    sh.state,
                    sh.city,
                    sh.pin_code,
                    sh.gst_amount,
                    sh.gst_percentage,
                    sh.gst_type,
                    sh.discount,
                    ? as currency_symbol,
                    cp.customer_id,
                    cp.appointment_id,
                    cp.appointment_date,
                    cp.service_name
                FROM subscription_histories sh
                LEFT JOIN subscription_plans sp ON sh.plan_id = sp.id
                LEFT JOIN customer_payment cp ON cp.user_id = sh.user_id AND cp.status = 'paid'
                WHERE sh.user_id = ?
                GROUP BY sh.id
                ORDER BY sh.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currency_symbol, $userId]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format for frontend
        $result = [];
        foreach ($subscriptions as $sub) {
            $result[] = [
                'id' => (int)$sub['id'],
                'invoice_number' => (string)$sub['invoice_number'],
                'plan_name' => (string)$sub['plan_name'],
                'amount' => (int)$sub['amount'],
                'currency_symbol' => (string)$sub['currency_symbol'],
                'payment_method' => (string)$sub['payment_method'],
                'payment_id' => (string)$sub['payment_id'],
                'created_at' => (string)$sub['created_at'],
                'status' => 'Paid',
                'name' => (string)$sub['customer_name'],
                'email' => (string)$sub['customer_email'],
                'purchase_type' => 'Subscription',
                'phone' => $sub['phone'] ?? '',
                'address' => $sub['address_1'] ?? '',
                'state' => $sub['state'] ?? '',
                'city' => $sub['city'] ?? '',
                'pin_code' => $sub['pin_code'] ?? '',
                'gst_amount' => (int)($sub['gst_amount'] ?? 0),
                'gst_percentage' => (int)($sub['gst_percentage'] ?? 0),
                'gst_type' => $sub['gst_type'] ?? 'inclusive',
                'discount' => (int)($sub['discount'] ?? 0),
                'has_customer_payments' => true
            ];
        }

        return $result;
    } catch (PDOException $e) {
        error_log("Error in getSubscriptionPurchaseHistory: " . $e->getMessage());
        return [];
    }
}





// Add this function to your existing functions.php file

/**
 * Get available coupons for a user (automatically filters out those that reached usage limit)
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return array Array of available coupons
 */
function getAvailableCoupons($pdo, $user_id)
{

    $current_date = date('Y-m-d H:i:s');

    $sql = "
        SELECT *
        FROM coupons
        WHERE user_id = :user_id
        AND start_date <= :current_date
        AND end_date >= :current_date
        ORDER BY created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':current_date' => $current_date
    ]);

    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $availableCoupons = [];

    foreach ($coupons as $coupon) {

        // Check usage limit
        if ($coupon['usage_limit'] !== null) {

            $countSql = "
                SELECT COUNT(*) as used_count
                FROM customer_payment
                WHERE coupon_id COLLATE utf8mb4_unicode_ci = :coupon_id
                AND coupon_used = 1
                AND status = 'paid'
            ";

            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute([
                ':coupon_id' => $coupon['coupon_id']
            ]);

            $used = $countStmt->fetch(PDO::FETCH_ASSOC)['used_count'];

            if ($used >= $coupon['usage_limit']) {
                continue; // ❌ Hide coupon completely
            }

            $coupon['remaining_uses'] = $coupon['usage_limit'] - $used;
        } else {
            $coupon['remaining_uses'] = null;
        }

        // Format dates
        $coupon['formatted_start'] = date('d M Y', strtotime($coupon['start_date']));
        $coupon['formatted_end']   = date('d M Y', strtotime($coupon['end_date']));

        $availableCoupons[] = $coupon;
    }

    return [
        "success" => true,
        "data" => $availableCoupons,
        "count" => count($availableCoupons)
    ];
}
/**
 * Check if a coupon has reached its usage limit
 * 
 * @param PDO $pdo Database connection
 * @param string $coupon_id Coupon ID
 * @return bool True if limit reached, false otherwise
 */
function isCouponLimitReached($pdo, $coupon_id)
{

    // Get usage limit
    $stmt = $pdo->prepare("
        SELECT usage_limit 
        FROM coupons 
        WHERE coupon_id = :coupon_id
    ");
    $stmt->execute([':coupon_id' => $coupon_id]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        return true;
    }

    // If unlimited
    if ($coupon['usage_limit'] === null) {
        return false;
    }

    // ✅ COUNT ONLY SUCCESSFUL PAYMENTS (IMPORTANT FIX)
    $countSql = "
        SELECT COUNT(*) as used_count
        FROM customer_payment
        WHERE coupon_id COLLATE utf8mb4_unicode_ci = :coupon_id
        AND coupon_used = 1
        AND status = 'paid'
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([':coupon_id' => $coupon_id]);
    $used = $countStmt->fetch(PDO::FETCH_ASSOC)['used_count'];

    return $used >= $coupon['usage_limit'];
}

//============================================================================//
//Filter user records based on search and conditions for User Management page

function getFilteredUserRecords($searchValue = '', $conditions = [])
{
    $pdo = getDbConnection();

    $sql = "SELECT COUNT(*) as count 
            FROM users u
            LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
            WHERE 1=1";

    $params = [];

    // Handle is_suspended condition
    if (isset($conditions['is_suspended']) && $conditions['is_suspended'] !== '') {
        $sql .= " AND u.is_suspended = :is_suspended";
        $params[':is_suspended'] = $conditions['is_suspended'];
    }

    // Handle plan_id condition - including NULL for "No Plan"
    // FIX: Check if it's set and not empty string, but allow "NULL" as a valid value
    if (isset($conditions['plan_id']) && $conditions['plan_id'] !== '') {
        if ($conditions['plan_id'] === 'no_plan') {
            $sql .= " AND u.plan_id IS NULL";
        } else {
            $sql .= " AND u.plan_id = :plan_id";
            $params[':plan_id'] = $conditions['plan_id'];
        }
    }

    // Add search value conditions
    if (!empty($searchValue)) {
        $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.user_id LIKE :search OR u.site_name LIKE :search OR u.site_slug LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}


//============================================================================//
// ==================== DASHBOARD STATISTICS FUNCTIONS ======================//

/**
 * Get total sellers count
 */
function getTotalSellers($pdo)
{
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getTotalSellers: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get new sellers for a specific period
 * @param string $period 'today', 'this_week', 'this_month', 'last_month'
 */
function getNewSellers($pdo, $period = 'this_month')
{
    try {
        $timezone = getAppTimezone();
        $now = new DateTime('now', new DateTimeZone($timezone));

        switch ($period) {
            case 'today':
                $start = $now->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'this_week':
                // Start from Monday
                $monday = clone $now;
                $monday->modify('monday this week');
                $start = $monday->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'this_month':
                $start = $now->format('Y-m-01 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'last_month':
                $lastMonth = clone $now;
                $lastMonth->modify('first day of last month');
                $start = $lastMonth->format('Y-m-01 00:00:00');

                $lastMonthEnd = clone $now;
                $lastMonthEnd->modify('last day of last month');
                $end = $lastMonthEnd->format('Y-m-d 23:59:59');
                break;

            default:
                return 0;
        }

        // Convert to UTC for database query
        $utcStart = convertToUTC($start, $timezone);
        $utcEnd = convertToUTC($end, $timezone);

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$utcStart, $utcEnd]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getNewSellers: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get suspended sellers count
 */
function getSuspendedSellers($pdo)
{
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_suspended = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getSuspendedSellers: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get total earnings from subscription histories
 */
function getTotalEarnings($pdo)
{
    try {
        $stmt = $pdo->query("SELECT SUM(amount) as total FROM subscription_histories WHERE status != 'refunded'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getTotalEarnings: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get earnings for a specific period
 * @param string $period 'today', 'this_week', 'this_month', 'last_month'
 */
function getEarningsByPeriod($pdo, $period = 'this_month')
{
    try {
        $timezone = getAppTimezone();
        $now = new DateTime('now', new DateTimeZone($timezone));

        switch ($period) {
            case 'today':
                $start = $now->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'this_week':
                $monday = clone $now;
                $monday->modify('monday this week');
                $start = $monday->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'this_month':
                $start = $now->format('Y-m-01 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'last_month':
                $lastMonth = clone $now;
                $lastMonth->modify('first day of last month');
                $start = $lastMonth->format('Y-m-01 00:00:00');

                $lastMonthEnd = clone $now;
                $lastMonthEnd->modify('last day of last month');
                $end = $lastMonthEnd->format('Y-m-d 23:59:59');
                break;

            default:
                return 0;
        }

        // Convert to UTC for database query
        $utcStart = convertToUTC($start, $timezone);
        $utcEnd = convertToUTC($end, $timezone);

        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM subscription_histories WHERE status != 'refunded' AND created_at BETWEEN ? AND ?");
        $stmt->execute([$utcStart, $utcEnd]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getEarningsByPeriod: " . $e->getMessage());
        return 0;
    }
}
/**
 * Get total GST from subscription histories
 */
function getTotalGST($pdo)
{
    try {
        $stmt = $pdo->query("SELECT SUM(gst_amount) as total FROM subscription_histories WHERE gst_amount IS NOT NULL AND status != 'refunded'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getTotalGST: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get GST for a specific period
 * @param string $period 'today', 'this_month', 'last_month'
 */
function getGSTByPeriod($pdo, $period = 'this_month')
{
    try {
        $timezone = getAppTimezone();
        $now = new DateTime('now', new DateTimeZone($timezone));

        switch ($period) {
            case 'today':
                $start = $now->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'this_month':
                $start = $now->format('Y-m-01 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'last_month':
                $lastMonth = clone $now;
                $lastMonth->modify('first day of last month');
                $start = $lastMonth->format('Y-m-01 00:00:00');

                $lastMonthEnd = clone $now;
                $lastMonthEnd->modify('last day of last month');
                $end = $lastMonthEnd->format('Y-m-d 23:59:59');
                break;

            default:
                return 0;
        }

        // Convert to UTC for database query
        $utcStart = convertToUTC($start, $timezone);
        $utcEnd = convertToUTC($end, $timezone);

        $stmt = $pdo->prepare("SELECT SUM(gst_amount) as total FROM subscription_histories WHERE created_at BETWEEN ? AND ? AND gst_amount IS NOT NULL AND status != 'refunded'");
        $stmt->execute([$utcStart, $utcEnd]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getGSTByPeriod: " . $e->getMessage());
        return 0;
    }
}

/**
 * Helper function to convert local time to UTC
 */
function convertToUTC($datetime, $fromTimezone)
{
    try {
        $date = new DateTime($datetime, new DateTimeZone($fromTimezone));
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Get all dashboard statistics in one call
 */
function getDashboardStatistics($pdo)
{
    return [
        'total_sellers' => getTotalSellers($pdo),
        'new_sellers_today' => getNewSellers($pdo, 'today'),
        'new_sellers_this_week' => getNewSellers($pdo, 'this_week'),
        'new_sellers_this_month' => getNewSellers($pdo, 'this_month'),
        'new_sellers_last_month' => getNewSellers($pdo, 'last_month'),
        'suspended_sellers' => getSuspendedSellers($pdo),
        'total_earnings' => getTotalEarnings($pdo),
        'earnings_today' => getEarningsByPeriod($pdo, 'today'),
        'earnings_this_week' => getEarningsByPeriod($pdo, 'this_week'),
        'earnings_this_month' => getEarningsByPeriod($pdo, 'this_month'),
        'earnings_last_month' => getEarningsByPeriod($pdo, 'last_month'),
        'total_gst' => getTotalGST($pdo),
        'gst_today' => getGSTByPeriod($pdo, 'today'),
        'gst_this_month' => getGSTByPeriod($pdo, 'this_month'),
        'gst_last_month' => getGSTByPeriod($pdo, 'last_month')
    ];
}


//============================================================================//
// ==================== SUBSCRIPTION REFUND STATISTICS ======================//

/**
 * Get total refund amount from subscription histories
 */
function getTotalSubscriptionRefunds($pdo)
{
    try {
        $stmt = $pdo->query("SELECT SUM(amount) as total FROM subscription_histories WHERE status = 'refunded'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getTotalSubscriptionRefunds: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get refund count from subscription histories
 */
function getTotalSubscriptionRefundCount($pdo)
{
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscription_histories WHERE status = 'refunded'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        error_log("Error in getTotalSubscriptionRefundCount: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get refund statistics for a specific period from subscription histories
 * @param string $period 'today', 'this_week', 'this_month', 'last_month'
 */
function getSubscriptionRefundsByPeriod($pdo, $period = 'this_month')
{
    try {
        $timezone = getAppTimezone();
        $now = new DateTime('now', new DateTimeZone($timezone));

        switch ($period) {
            case 'today':
                $start = $now->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'this_week':
                $monday = clone $now;
                $monday->modify('monday this week');
                $start = $monday->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'this_month':
                $start = $now->format('Y-m-01 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;

            case 'last_month':
                $lastMonth = clone $now;
                $lastMonth->modify('first day of last month');
                $start = $lastMonth->format('Y-m-01 00:00:00');
                
                $lastMonthEnd = clone $now;
                $lastMonthEnd->modify('last day of last month');
                $end = $lastMonthEnd->format('Y-m-d 23:59:59');
                break;

            default:
                return ['amount' => 0, 'count' => 0];
        }

        // Convert to UTC for database query
        $utcStart = convertToUTC($start, $timezone);
        $utcEnd = convertToUTC($end, $timezone);

        // Get refund amount
        $amountStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM subscription_histories WHERE status = 'refunded' AND created_at BETWEEN ? AND ?");
        $amountStmt->execute([$utcStart, $utcEnd]);
        $amount = (float)$amountStmt->fetchColumn();

        // Get refund count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscription_histories WHERE status = 'refunded' AND created_at BETWEEN ? AND ?");
        $countStmt->execute([$utcStart, $utcEnd]);
        $count = (int)$countStmt->fetchColumn();

        return [
            'amount' => $amount,
            'count' => $count
        ];
    } catch (Exception $e) {
        error_log("Error in getSubscriptionRefundsByPeriod: " . $e->getMessage());
        return ['amount' => 0, 'count' => 0];
    }
}

/**
 * Get all refund statistics in one call
 */
function getRefundStatistics($pdo)
{
    return [
        'total_refund_amount' => getTotalSubscriptionRefunds($pdo),
        'total_refund_count' => getTotalSubscriptionRefundCount($pdo),
        'today_refunds' => getSubscriptionRefundsByPeriod($pdo, 'today'),
        'this_week_refunds' => getSubscriptionRefundsByPeriod($pdo, 'this_week'),
        'this_month_refunds' => getSubscriptionRefundsByPeriod($pdo, 'this_month'),
        'last_month_refunds' => getSubscriptionRefundsByPeriod($pdo, 'last_month')
    ];
}

//============================================================================//
// ==================== SUBSCRIPTION PLAN STATISTICS ========================//

/**
 * Get count of users on each subscription plan
 * Returns array with plan names as keys and count as values
 */
function getSubscriptionPlanCounts($pdo)
{
    try {
        $sql = "SELECT 
                    COALESCE(sp.name, 'No Plan') as plan_name,
                    COUNT(u.id) as user_count
                FROM users u
                LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
                GROUP BY sp.id, sp.name
                ORDER BY user_count DESC";

        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $planCounts = [];
        foreach ($results as $row) {
            $planCounts[$row['plan_name']] = (int)$row['user_count'];
        }

        return $planCounts;
    } catch (Exception $e) {
        error_log("Error in getSubscriptionPlanCounts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get count of active vs expired subscriptions
 */
function getSubscriptionStatusCounts($pdo)
{
    try {
        $timezone = getAppTimezone();
        $now = new DateTime('now', new DateTimeZone($timezone));
        $currentDate = $now->format('Y-m-d H:i:s');

        // Convert to UTC for database comparison
        $utcNow = convertToUTC($currentDate, $timezone);

        $sql = "SELECT 
                    COUNT(CASE WHEN expires_on > ? OR expires_on IS NULL OR expires_on = '0000-00-00 00:00:00' THEN 1 END) as active_count,
                    COUNT(CASE WHEN expires_on <= ? AND expires_on != '0000-00-00 00:00:00' AND expires_on IS NOT NULL THEN 1 END) as expired_count
                FROM users";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$utcNow, $utcNow]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'active' => (int)($result['active_count'] ?? 0),
            'expired' => (int)($result['expired_count'] ?? 0)
        ];
    } catch (Exception $e) {
        error_log("Error in getSubscriptionStatusCounts: " . $e->getMessage());
        return ['active' => 0, 'expired' => 0];
    }
}

/**
 * Get detailed plan statistics with percentages
 */
function getDetailedPlanStats($pdo)
{
    $totalUsers = getTotalSellers($pdo);
    $planCounts = getSubscriptionPlanCounts($pdo);
    $statusCounts = getSubscriptionStatusCounts($pdo);

    $stats = [];
    foreach ($planCounts as $planName => $count) {
        $percentage = $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0;
        $stats[] = [
            'plan_name' => $planName,
            'count' => $count,
            'percentage' => $percentage,
            'color' => getPlanColor($planName) // Function to assign colors
        ];
    }

    return [
        'plans' => $stats,
        'total_users' => $totalUsers,
        'active_subscriptions' => $statusCounts['active'],
        'expired_subscriptions' => $statusCounts['expired']
    ];
}

/**
 * Helper function to assign colors to plans
 */
function getPlanColor($planName)
{
    $colors = [
        'Free' => 'primary',
        'Welcome' => 'success',
        'Basic' => 'info',
        'Premium' => 'warning',
        'Enterprise' => 'danger',
        'No Plan' => 'secondary'
    ];

    foreach ($colors as $key => $color) {
        if (stripos($planName, $key) !== false) {
            return $color;
        }
    }

    return 'primary'; // Default color
}

//============================================================================//
// ==================== PLATFORM EARNINGS STATISTICS =========================//

/**
 * Get platform earnings statistics from customer_payment table
 */
function getPlatformEarningsStats($pdo)
{
    try {
        $timezone = getAppTimezone();
        $now = new DateTime('now', new DateTimeZone($timezone));

        // Define date ranges
        $todayStart = $now->format('Y-m-d 00:00:00');
        $todayEnd = $now->format('Y-m-d 23:59:59');

        $thisWeekStart = clone $now;
        $thisWeekStart->modify('monday this week');
        $thisWeekStart = $thisWeekStart->format('Y-m-d 00:00:00');

        $thisMonthStart = $now->format('Y-m-01 00:00:00');

        $lastMonthStart = clone $now;
        $lastMonthStart->modify('first day of last month');
        $lastMonthStart = $lastMonthStart->format('Y-m-01 00:00:00');

        $lastMonthEnd = clone $now;
        $lastMonthEnd->modify('last day of last month');
        $lastMonthEnd = $lastMonthEnd->format('Y-m-d 23:59:59');

        // Convert to UTC for database queries
        $utcTodayStart = convertToUTC($todayStart, $timezone);
        $utcTodayEnd = convertToUTC($todayEnd, $timezone);
        $utcThisWeekStart = convertToUTC($thisWeekStart, $timezone);
        $utcThisMonthStart = convertToUTC($thisMonthStart, $timezone);
        $utcLastMonthStart = convertToUTC($lastMonthStart, $timezone);
        $utcLastMonthEnd = convertToUTC($lastMonthEnd, $timezone);

        // Total Statistics
        $totalStats = getPaymentStatsByDateRange($pdo, null, null);

        // Today's Statistics
        $todayStats = getPaymentStatsByDateRange($pdo, $utcTodayStart, $utcTodayEnd);

        // This Week Statistics
        $thisWeekStats = getPaymentStatsByDateRange($pdo, $utcThisWeekStart, null);

        // This Month Statistics
        $thisMonthStats = getPaymentStatsByDateRange($pdo, $utcThisMonthStart, null);

        // Last Month Statistics
        $lastMonthStats = getPaymentStatsByDateRange($pdo, $utcLastMonthStart, $utcLastMonthEnd);

        return [
            'total' => $totalStats,
            'today' => $todayStats,
            'this_week' => $thisWeekStats,
            'this_month' => $thisMonthStats,
            'last_month' => $lastMonthStats
        ];
    } catch (Exception $e) {
        error_log("Error in getPlatformEarningsStats: " . $e->getMessage());
        return getEmptyPaymentStats();
    }
}

/**
 * Get payment statistics for a specific date range
 */
function getPaymentStatsByDateRange($pdo, $startDate = null, $endDate = null)
{
    try {
        $params = [];
        $dateCondition = "";

        if ($startDate && $endDate) {
            $dateCondition = "AND created_at BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        } elseif ($startDate) {
            $dateCondition = "AND created_at >= :start_date";
            $params[':start_date'] = $startDate;
        } elseif ($endDate) {
            $dateCondition = "AND created_at <= :end_date";
            $params[':end_date'] = $endDate;
        }

        // Total amount (sum of amount column) - EXCLUDING REFUNDS
        $totalSql = "SELECT COALESCE(SUM(amount), 0) as total FROM customer_payment WHERE status != 'refund' AND 1=1 $dateCondition";
        $totalStmt = $pdo->prepare($totalSql);
        foreach ($params as $key => $value) {
            $totalStmt->bindValue($key, $value);
        }
        $totalStmt->execute();
        $total = (float)$totalStmt->fetchColumn();

        // Paid amount (status = 'paid')
        $paidSql = "SELECT COALESCE(SUM(amount), 0) as total FROM customer_payment WHERE status = 'paid' $dateCondition";
        $paidStmt = $pdo->prepare($paidSql);
        foreach ($params as $key => $value) {
            $paidStmt->bindValue($key, $value);
        }
        $paidStmt->execute();
        $paid = (float)$paidStmt->fetchColumn();

        // Unpaid amount (status = 'pending')
        $unpaidSql = "SELECT COALESCE(SUM(amount), 0) as total FROM customer_payment WHERE status = 'pending' $dateCondition";
        $unpaidStmt = $pdo->prepare($unpaidSql);
        foreach ($params as $key => $value) {
            $unpaidStmt->bindValue($key, $value);
        }
        $unpaidStmt->execute();
        $unpaid = (float)$unpaidStmt->fetchColumn();

        // Refund amount (status = 'refund')
        $refundSql = "SELECT COALESCE(SUM(amount), 0) as total FROM customer_payment WHERE status = 'refund' $dateCondition";
        $refundStmt = $pdo->prepare($refundSql);
        foreach ($params as $key => $value) {
            $refundStmt->bindValue($key, $value);
        }
        $refundStmt->execute();
        $refund = (float)$refundStmt->fetchColumn();

        // Count transactions
        $countSql = "SELECT 
                        COUNT(*) as total_count,
                        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                        SUM(CASE WHEN status = 'refund' THEN 1 ELSE 0 END) as refund_count
                    FROM customer_payment WHERE 1=1 $dateCondition";
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);

        // Payment method breakdown
        $methodSql = "SELECT 
                        payment_method,
                        COALESCE(SUM(CASE WHEN status != 'refund' THEN amount ELSE 0 END), 0) as total,
                        COUNT(*) as count,
                        COALESCE(SUM(CASE WHEN status = 'refund' THEN amount ELSE 0 END), 0) as refund_total,
                        SUM(CASE WHEN status = 'refund' THEN 1 ELSE 0 END) as refund_count
                    FROM customer_payment 
                    WHERE 1=1 $dateCondition
                    GROUP BY payment_method";
        $methodStmt = $pdo->prepare($methodSql);
        foreach ($params as $key => $value) {
            $methodStmt->bindValue($key, $value);
        }
        $methodStmt->execute();
        $methodBreakdown = $methodStmt->fetchAll(PDO::FETCH_ASSOC);

        // Net revenue (total - refunds)
        $netRevenue = $total - $refund;

        return [
            'total' => $total,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'refund' => $refund,
            'net_revenue' => $netRevenue,
            'total_count' => (int)($counts['total_count'] ?? 0),
            'paid_count' => (int)($counts['paid_count'] ?? 0),
            'pending_count' => (int)($counts['pending_count'] ?? 0),
            'refund_count' => (int)($counts['refund_count'] ?? 0),
            'method_breakdown' => $methodBreakdown,
            'collection_rate' => $total > 0 ? round(($paid / $total) * 100, 1) : 0,
            'refund_rate' => $total > 0 ? round(($refund / $total) * 100, 1) : 0
        ];
    } catch (Exception $e) {
        error_log("Error in getPaymentStatsByDateRange: " . $e->getMessage());
        return getEmptyPaymentStats();
    }
}

/**
 * Get empty payment stats structure
 */
function getEmptyPaymentStats()
{
    return [
        'total' => 0,
        'paid' => 0,
        'unpaid' => 0,
        'refund' => 0,
        'net_revenue' => 0,
        'total_count' => 0,
        'paid_count' => 0,
        'pending_count' => 0,
        'refund_count' => 0,
        'method_breakdown' => [],
        'collection_rate' => 0,
        'refund_rate' => 0
    ];
}

/**
 * Get payment method display name and icon
 */
function getPaymentMethodInfo($method)
{
    $methods = [
        'razorpay' => ['name' => 'Razorpay', 'icon' => 'ki-credit-cart', 'color' => 'primary'],
        'upi' => ['name' => 'UPI', 'icon' => 'ki-phone', 'color' => 'success'],
        'cash' => ['name' => 'Cash', 'icon' => 'ki-money', 'color' => 'warning'],
        'card' => ['name' => 'Card', 'icon' => 'ki-card', 'color' => 'info'],
        'bank' => ['name' => 'Bank Transfer', 'icon' => 'ki-bank', 'color' => 'danger'],
        'phone pay' => ['name' => 'Phone Pay', 'icon' => 'ki-phone', 'color' => 'success'],
        'payu' => ['name' => 'PayU', 'icon' => 'ki-credit-cart', 'color' => 'primary'],
    ];

    $method = strtolower($method);
    return $methods[$method] ?? ['name' => ucfirst($method), 'icon' => 'ki-credit-cart', 'color' => 'secondary'];
}

/**
 * Get recent transactions
 */
function getRecentTransactions($pdo, $limit = 10)
{
    try {
        $sql = "SELECT 
                    cp.*,
                    u.name as user_name,
                    u.user_id,
                    c.name as customer_name
                FROM customer_payment cp
                LEFT JOIN users u ON cp.user_id = u.id
                LEFT JOIN customers c ON cp.customer_id = c.id
                ORDER BY cp.created_at DESC
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getRecentTransactions: " . $e->getMessage());
        return [];
    }
}
