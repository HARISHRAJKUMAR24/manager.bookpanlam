<?php
// manager.bookpanlam/public/seller/restaurant/get-restaurant-settings.php

// CORS headers
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow from any origin for development
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* =====================================================
   1️⃣ READ TOKEN FROM COOKIE OR HEADER
===================================================== */
$token = null;

// First try to get token from cookie
if (isset($_COOKIE['token'])) {
    $token = $_COOKIE['token'];
} 
// Then try Authorization header
else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
}
// Then try from query string (for testing)
else if (isset($_GET['token'])) {
    $token = $_GET['token'];
}

if (!$token) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized: Missing token",
        "debug" => [
            "cookies" => $_COOKIE,
            "headers" => getallheaders()
        ]
    ]);
    exit;
}

/* =====================================================
   2️⃣ FETCH USER USING TOKEN
===================================================== */
try {
    $stmt = $pdo->prepare("
        SELECT user_id 
        FROM users 
        WHERE api_token = ? 
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid token"
        ]);
        exit;
    }

    $user_id = (int) $user["user_id"];

    /* =====================================================
       3️⃣ HANDLE GET REQUEST (FETCH SETTINGS)
    ===================================================== */
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        
        // Fetch restaurant settings
        $stmt = $pdo->prepare("
            SELECT * FROM restaurant_settings 
            WHERE user_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Decode operating_days if it exists
        if ($settings && isset($settings['operating_days'])) {
            $settings['operating_days'] = json_decode($settings['operating_days'], true) ?? [];
        }
        
        // Fetch restaurant tables
        $stmt = $pdo->prepare("
        SELECT id, table_number, seats, eating_time 
FROM restaurant_tables 
WHERE user_id = ? 
ORDER BY table_number

        ");
        $stmt->execute([$user_id]);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate summary statistics
        $total_tables = count($tables);
        $total_seats = 0;
        $total_eating_time = 0;
        
        foreach ($tables as $table) {
            $total_seats += (int)$table['seats'];
            $total_eating_time += (int)$table['eating_time'];
        }
        
        $avg_eating_time = $total_tables > 0 ? round($total_eating_time / $total_tables) : 0;
        
        // Format the response
        $response = [
            "success" => true,
            "data" => [
                "settings" => $settings ? [
                    "id" => (int)$settings["id"],
                    "user_id" => (int)$settings["user_id"],
                    "start_time" => $settings["start_time"] ?? "",
                    "start_meridiem" => $settings["start_meridiem"] ?? "AM",
                    "end_time" => $settings["end_time"] ?? "",
                    "end_meridiem" => $settings["end_meridiem"] ?? "PM",
                    "break_start" => $settings["break_start"],
                    "break_end" => $settings["break_end"],
                    "operating_days" => $settings["operating_days"] ?? [],
                    "created_at" => $settings["created_at"] ?? ""
                ] : null,
                "tables" => $tables,
                "summary" => [
                    "total_tables" => $total_tables,
                    "total_seats" => $total_seats,
                    "avg_eating_time" => $avg_eating_time
                ]
            ]
        ];
        
        echo json_encode($response);
        exit;
        
    } else {
        // Method not allowed
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Method not allowed"
        ]);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error",
        "error" => $e->getMessage()
    ]);
    exit;
}