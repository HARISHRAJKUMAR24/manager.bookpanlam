<?php
// manager.bookpanlam/public/seller/restaurant/check-availability.php

// CORS headers
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* =====================================================
   1️⃣ VALIDATE INPUT PARAMETERS
===================================================== */
$required_params = ['restaurantId', 'date', 'startTime', 'endTime', 'seats'];

foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing parameter: $param"
        ]);
        exit;
    }
}

$restaurant_id = (int) $_GET['restaurantId'];
$date = $_GET['date'];
$start_time = $_GET['startTime'];
$end_time = $_GET['endTime'];
$seats = (int) $_GET['seats'];

// Validate inputs
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid date format"
    ]);
    exit;
}

if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid time format"
    ]);
    exit;
}

/* =====================================================
   2️⃣ FIND AVAILABLE TABLES
===================================================== */
try {
    // First, get all tables that can accommodate the required seats
    $stmt = $pdo->prepare("
        SELECT rt.id, rt.table_number, rt.seats, rt.eating_time
        FROM restaurant_tables rt
        WHERE rt.user_id = ? 
        AND rt.seats >= ?
        ORDER BY rt.seats ASC, rt.table_number ASC
    ");
    
    $stmt->execute([$restaurant_id, $seats]);
    $all_tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all_tables)) {
        echo json_encode([
            "success" => true,
            "available" => false,
            "message" => "No tables available for $seats seats"
        ]);
        exit;
    }
    
    // Get existing bookings for the given date and time
    $stmt = $pdo->prepare("
        SELECT rb.table_id
        FROM restaurant_bookings rb
        WHERE rb.user_id = ? 
        AND rb.booking_date = ? 
        AND rb.status IN ('pending', 'confirmed')
        AND (
            (rb.start_time < ? AND rb.end_time > ?) OR
            (rb.start_time >= ? AND rb.start_time < ?) OR
            (rb.end_time > ? AND rb.end_time <= ?)
        )
    ");
    
    $stmt->execute([
        $restaurant_id,
        $date,
        $end_time, $start_time,
        $start_time, $end_time,
        $start_time, $end_time
    ]);
    
    $booked_table_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'table_id');
    
    // Filter out booked tables
    $available_tables = array_filter($all_tables, function($table) use ($booked_table_ids) {
        return !in_array($table['id'], $booked_table_ids);
    });
    
    $available_tables = array_values($available_tables); // Reset array keys
    
    // Prepare response
    $response = [
        "success" => true,
        "available" => !empty($available_tables),
        "availableTables" => $available_tables,
        "message" => !empty($available_tables) 
            ? count($available_tables) . " table(s) available" 
            : "No tables available for the selected time"
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "available" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
    exit;
}