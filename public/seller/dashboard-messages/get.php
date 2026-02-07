<?php
// seller/dashboard-messages/get.php

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required",
        "data" => null
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| GET USER DETAILS
|--------------------------------------------------------------------------
*/
$userStmt = $pdo->prepare("
    SELECT plan_id, created_at 
    FROM users 
    WHERE user_id = ?
");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User not found",
        "data" => null
    ]);
    exit();
}

$user_plan_id = (int)$user['plan_id'];
$user_created_at = $user['created_at']; // User's creation time (UTC)

/*
|--------------------------------------------------------------------------
| USE UTC FOR TIME COMPARISONS
|--------------------------------------------------------------------------
*/
$current_utc_time = gmdate('Y-m-d H:i:s'); // Current UTC time

/*
|--------------------------------------------------------------------------
| GET ALL ACTIVE MESSAGES
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        id,
        title,
        description,
        expiry_date,
        seller_type,
        just_created_seller,
        created_at as message_created_at
    FROM dashboard_messages
    WHERE expiry_date > :current_utc_time
    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['current_utc_time' => $current_utc_time]);
$all_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$messages_for_user = []; // Changed to array to store ALL matching messages

foreach ($all_messages as $msg) {
    $seller_type_json = $msg['seller_type'];
    $just_created_seller = (int)$msg['just_created_seller'];
    $message_created_at = $msg['message_created_at']; // Message creation time (UTC)
    
    // STEP 1: Check seller_type targeting
    $seller_type_match = false;
    
    if ($seller_type_json === null || 
        $seller_type_json === '[]' || 
        $seller_type_json === 'null' ||
        $seller_type_json === '') {
        // Show to ALL sellers
        $seller_type_match = true;
    } else {
        // Check if user's plan_id is in the array
        $seller_type_array = json_decode($seller_type_json, true);
        if (is_array($seller_type_array)) {
            $seller_type_array = array_map('intval', $seller_type_array);
            if (in_array($user_plan_id, $seller_type_array)) {
                $seller_type_match = true;
            }
        }
    }
    
    if (!$seller_type_match) {
        continue; // Skip - seller not in target plan
    }
    
    // STEP 2: Check just_created_seller targeting
    $new_seller_match = false;
    
    if ($just_created_seller === 0) {
        // Show to ALL sellers (old and new)
        $new_seller_match = true;
    } else if ($just_created_seller === 1) {
        // Show only if user was created AFTER message
        // Compare timestamps: user_created_at > message_created_at
        $user_timestamp = strtotime($user_created_at);
        $message_timestamp = strtotime($message_created_at);
        
        if ($user_timestamp > $message_timestamp) {
            // User was created AFTER message → Show this message
            $new_seller_match = true;
        }
        // If user was created BEFORE or AT same time as message → Don't show
    }
    
    // STEP 3: If both conditions match, ADD this message to array (NO BREAK!)
    if ($seller_type_match && $new_seller_match) {
        $messages_for_user[] = $msg; // Add to array instead of single variable
        // REMOVED: break; // This was preventing multiple messages
    }
}

/*
|--------------------------------------------------------------------------
| DEBUG INFO - Check what's happening
|--------------------------------------------------------------------------
*/
if (isset($_GET['debug'])) {
    $debug_messages = [];
    
    foreach ($all_messages as $msg) {
        $seller_type_array = json_decode($msg['seller_type'] ?? '[]', true);
        $just_created_seller = (int)$msg['just_created_seller'];
        $message_created = $msg['message_created_at'];
        
        // Check if this message would show for current user
        $seller_type_match = false;
        if ($seller_type_json === null || $seller_type_json === '[]' || $seller_type_json === 'null') {
            $seller_type_match = true;
        } else {
            if (is_array($seller_type_array) && in_array($user_plan_id, $seller_type_array)) {
                $seller_type_match = true;
            }
        }
        
        $new_seller_match = false;
        if ($just_created_seller === 0) {
            $new_seller_match = true;
        } else if ($just_created_seller === 1) {
            $user_time = strtotime($user_created_at);
            $message_time = strtotime($message_created);
            $new_seller_match = ($user_time > $message_time);
        }
        
        $debug_messages[] = [
            "id" => $msg['id'],
            "title" => $msg['title'],
            "seller_type" => $seller_type_array,
            "just_created_seller" => $just_created_seller,
            "message_created" => $message_created,
            "expiry_date" => $msg['expiry_date'],
            "is_expired" => $msg['expiry_date'] <= $current_utc_time ? 'YES' : 'NO',
            "seller_type_match" => $seller_type_match ? 'YES' : 'NO',
            "new_seller_match" => $new_seller_match ? 'YES' : 'NO',
            "would_show" => ($seller_type_match && $new_seller_match) ? 'YES' : 'NO'
        ];
    }
    
    echo json_encode([
        "success" => true,
        "debug" => [
            "user_id" => $user_id,
            "user_plan_id" => $user_plan_id,
            "user_created_at" => $user_created_at,
            "user_created_timestamp" => strtotime($user_created_at),
            "current_utc_time" => $current_utc_time,
            "total_active_messages" => count($all_messages),
            "matching_messages_count" => count($messages_for_user),
            "messages" => $debug_messages,
            "selected_messages" => $messages_for_user ? array_map(function($msg) {
                return ['id' => $msg['id'], 'title' => $msg['title']];
            }, $messages_for_user) : []
        ],
        "data" => $messages_for_user
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/
echo json_encode([
    "success" => true,
    "data" => $messages_for_user // Now returns array of all matching messages
]);
exit();