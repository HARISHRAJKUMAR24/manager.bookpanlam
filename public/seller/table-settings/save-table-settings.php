<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* =====================================================
   1️⃣ READ TOKEN FROM COOKIE
===================================================== */
$token = $_COOKIE["token"] ?? null;

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized: Missing token"
    ]);
    exit;
}

/* =====================================================
   2️⃣ FETCH USER USING TOKEN
===================================================== */
$stmt = $pdo->prepare("
    SELECT user_id 
    FROM users 
    WHERE api_token = ? 
    LIMIT 1
");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
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
    
    try {
        // Fetch restaurant settings
        $stmt = $pdo->prepare("
            SELECT * FROM restaurant_settings 
            WHERE user_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Fetch restaurant tables
        $stmt = $pdo->prepare("
            SELECT table_number, seats, eating_time 
            FROM restaurant_tables 
            WHERE user_id = ? 
            ORDER BY table_number
        ");
        $stmt->execute([$user_id]);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the response
        $response = [
            "success" => true,
            "data" => [
                "settings" => $settings ? [
                    "id" => (int)$settings["id"],
                    "user_id" => (int)$settings["user_id"],
                    "start_time" => $settings["start_time"],
                    "start_meridiem" => $settings["start_meridiem"],
                    "end_time" => $settings["end_time"],
                    "end_meridiem" => $settings["end_meridiem"],
                    "break_start" => $settings["break_start"],
                    "break_end" => $settings["break_end"],
                    "operating_days" => $settings["operating_days"],
                    "created_at" => $settings["created_at"]
                ] : null,
                "tables" => array_map(function($table) {
                    return [
                        "table_number" => $table["table_number"],
                        "seats" => (int)$table["seats"],
                        "eating_time" => (int)$table["eating_time"]
                    ];
                }, $tables)
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch settings",
            "error" => $e->getMessage()
        ]);
    }
    
    exit;
}

/* =====================================================
   4️⃣ HANDLE POST REQUEST (SAVE SETTINGS)
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    
    if (!$data) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON payload"
        ]);
        exit;
    }

    $timing = $data["timing"] ?? null;
    $operatingDays = $data["operatingDays"] ?? [];
    $tables = $data["tables"] ?? [];
    $breakSchedule = $data["breakSchedule"] ?? null;

    if (!$timing || empty($tables)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid payload: timing or tables missing"
        ]);
        exit;
    }

    /* TRANSACTION */
    $pdo->beginTransaction();

    try {
        /* UPSERT restaurant_settings */
        $stmt = $pdo->prepare("
            INSERT INTO restaurant_settings
            (user_id, start_time, start_meridiem, end_time, end_meridiem, break_start, break_end, operating_days)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                start_time = VALUES(start_time),
                start_meridiem = VALUES(start_meridiem),
                end_time = VALUES(end_time),
                end_meridiem = VALUES(end_meridiem),
                break_start = VALUES(break_start),
                break_end = VALUES(break_end),
                operating_days = VALUES(operating_days)
        ");

        $stmt->execute([
            $user_id,
            $timing["startTime"],
            $timing["startMeridiem"],
            $timing["endTime"],
            $timing["endMeridiem"],
            $breakSchedule["breakStart"] ?? null,
            $breakSchedule["breakEnd"] ?? null,
            json_encode($operatingDays)
        ]);

        /* REPLACE restaurant_tables */
        $pdo->prepare("
            DELETE FROM restaurant_tables WHERE user_id = ?
        ")->execute([$user_id]);

        $insert = $pdo->prepare("
            INSERT INTO restaurant_tables (user_id, table_number, seats, eating_time)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($tables as $t) {
            $eatingTime = isset($t["eatingTime"]) ? (int) $t["eatingTime"] : 60;
            
            $insert->execute([
                $user_id,
                $t["tableNumber"],
                (int) $t["seats"],
                $eatingTime
            ]);
        }

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Table settings saved successfully",
            "tables_count" => count($tables)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            "success" => false,
            "message" => "Save failed",
            "error" => $e->getMessage()
        ]);
    }
    
    exit;
}

// If method is not GET or POST
echo json_encode([
    "success" => false,
    "message" => "Method not allowed"
]);