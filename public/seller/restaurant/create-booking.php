<?php
// manager.bookpanlam/public/seller/restaurant/create-booking.php

// CORS headers
header("Access-Control-Allow-Origin: http://localhost:3000");
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* ============================================
   1️⃣ READ TOKEN
============================================ */
$token = null;

if (isset($_COOKIE['token'])) {
    $token = $_COOKIE['token'];
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
        $token = $m[1];
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized: Missing token"]);
    exit;
}

/* ============================================
   2️⃣ VALIDATE SELLER TOKEN
============================================ */
try {
    $stmt = $pdo->prepare("
        SELECT id, user_id 
        FROM users 
        WHERE api_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid seller token"]);
        exit;
    }

    $seller_id = (int)$seller["id"];          // For restaurant_bookings.user_id
    $seller_user_id = (int)$seller["user_id"]; // For restaurant_tables.user_id

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Token validation error"]);
    exit;
}

/* ============================================
   3️⃣ ALLOW ONLY POST
============================================ */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

/* ============================================
   4️⃣ REQUIRED FIELDS
============================================ */
$required = [
    "customerId", "customerName", "customerEmail", "customerPhone",
    "bookingDate", "day", "startTime", "endTime",
    "duration", "seats", "tableId", "tableNumber"
];

foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing field: $field"]);
        exit;
    }
}

/* ============================================
   5️⃣ EXTRACT BOOKING DATA
============================================ */
$customer_id     = (int)$input['customerId'];
$customer_name   = trim($input['customerName']);
$customer_email  = trim($input['customerEmail']);
$customer_phone  = trim($input['customerPhone']);
$booking_date    = $input['bookingDate'];
$day             = $input['day'];
$start_time      = $input['startTime'];
$end_time        = $input['endTime'];
$duration        = (int)$input['duration'];
$seats           = (int)$input['seats'];
$table_id        = (int)$input['tableId'];
$table_number    = $input['tableNumber'];
$special         = !empty($input["specialRequests"]) ? trim($input["specialRequests"]) : "";

/* ============================================
   6️⃣ VALIDATE TABLE BELONGS TO SELLER
============================================ */

$stmt = $pdo->prepare("
    SELECT id 
    FROM restaurant_tables
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$table_id, $seller_user_id]);

$validTable = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$validTable) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid table"]);
    exit;
}

/* ============================================
   7️⃣ CHECK OVERLAPPING BOOKINGS
============================================ */
$stmt = $pdo->prepare("
    SELECT id FROM restaurant_bookings
    WHERE user_id = ?
    AND table_id = ?
    AND booking_date = ?
    AND status IN ('pending','confirmed')
    AND (
        (start_time < ? AND end_time > ?) OR
        (start_time >= ? AND start_time < ?) OR
        (end_time > ? AND end_time <= ?)
    )
    LIMIT 1
");

$stmt->execute([
    $seller_id,
    $table_id,
    $booking_date,
    $end_time, $start_time,
    $start_time, $end_time,
    $start_time, $end_time
]);

$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Time slot already booked"]);
    exit;
}

/* ============================================
   8️⃣ INSERT BOOKING
============================================ */
$ref = "BK-" . strtoupper(uniqid()) . "-" . date("Ymd");

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO restaurant_bookings (
            user_id, customer_id, customer_name, customer_email,
            customer_phone, table_id, table_number, booking_date,
            day, start_time, end_time, duration_minutes,
            number_of_seats, special_requests, booking_reference, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending'
        )
    ");

    $stmt->execute([
        $seller_id,
        $customer_id,
        $customer_name,
        $customer_email,
        $customer_phone,
        $table_id,
        $table_number,
        $booking_date,
        $day,
        $start_time,
        $end_time,
        $duration,
        $seats,
        $special,
        $ref
    ]);

    $bookingId = $pdo->lastInsertId();
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Booking created successfully",
        "data" => [
            "bookingId" => $bookingId,
            "reference" => $ref,
            "status" => "pending",
            "bookingDetails" => [
                "date" => $booking_date,
                "time" => "$start_time - $end_time",
                "tableNumber" => $table_number,
                "seats" => $seats,
                "duration" => $duration
            ]
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error", "error" => $e->getMessage()]);
}
