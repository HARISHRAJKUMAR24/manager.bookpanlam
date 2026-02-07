<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$pdo = getDbConnection();

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $categoryId = $_GET['category_id'] ?? null;
    $batchId = $_GET['batch_id'] ?? null;
    $userId = $_GET['user_id'] ?? null; // Optional filter by user_id
    
    if (!$categoryId) {
        echo json_encode([
            "success" => false,
            "message" => "Category ID is required"
        ]);
        exit;
    }
    
    $sql = "SELECT * FROM doctor_token_history WHERE category_id = ?";
    $params = [$categoryId];
    
    if ($batchId) {
        $sql .= " AND slot_batch_id = ?";
        $params[] = $batchId;
    }
    
    if ($userId) {
        $sql .= " AND user_id = ?";
        $params[] = $userId;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse batch_id to extract slot_index for display
    foreach ($history as &$record) {
        if (isset($record['slot_batch_id'])) {
            $parts = explode(':', $record['slot_batch_id']);
            $record['parsed_day_index'] = isset($parts[0]) ? (int)$parts[0] : null;
            $record['parsed_slot_index'] = isset($parts[1]) ? (int)$parts[1] + 1 : null; // +1 to make it human-readable
        }
    }
    
    echo json_encode([
        "success" => true,
        "data" => $history
    ]);
    exit;
}

// API to manually update token
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    
    $required = ['category_id', 'batch_id', 'action', 'value'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    // FIXED: Include 'id' in SELECT query
    $stmt = $pdo->prepare("SELECT id, weekly_schedule, user_id FROM doctor_schedule WHERE category_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$input['category_id']]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode([
            "success" => false,
            "message" => "Schedule not found for this category"
        ]);
        exit;
    }
    
    // Now these will have correct values
    $doctorScheduleId = $schedule['id'] ?? 0;  // This will be 47, not 0
    $userId = $schedule['user_id'] ?? null;
    
    $weeklySchedule = json_decode($schedule['weekly_schedule'], true);
    $updated = false;
    $oldToken = 0;
    $newToken = 0;
    
    // Find and update the slot
    foreach ($weeklySchedule as $day => &$dayData) {
        if ($dayData['enabled']) {
            foreach ($dayData['slots'] as &$slot) {
                if ($slot['batch_id'] === $input['batch_id']) {
                    $oldToken = (int)($slot['token'] ?? 0);
                    
                    if ($input['action'] === 'set') {
                        $newToken = (int)$input['value'];
                    } elseif ($input['action'] === 'increase') {
                        $newToken = $oldToken + (int)$input['value'];
                    } elseif ($input['action'] === 'decrease') {
                        $newToken = max(0, $oldToken - (int)$input['value']);
                    }
                    
                    $slot['token'] = (string)$newToken;
                    $updated = true;
                    break 2;
                }
            }
        }
    }
    
    if ($updated) {
        // Update schedule
        $updateStmt = $pdo->prepare("UPDATE doctor_schedule SET weekly_schedule = ? WHERE category_id = ?");
        $updateStmt->execute([json_encode($weeklySchedule), $input['category_id']]);
        
        // Parse day and slot from batch_id for display
        $parsedDayIndex = null;
        $parsedSlotIndex = null;
        if (isset($input['batch_id'])) {
            $parts = explode(':', $input['batch_id']);
            $parsedDayIndex = isset($parts[0]) ? (int)$parts[0] : null;
            $parsedSlotIndex = isset($parts[1]) ? (int)$parts[1] + 1 : null; // +1 for human readable
        }
        
        // Get updated_by from session (current logged in user)
        $updatedBy = $_SESSION['user_id'] ?? null;
        
        // Log to history with user_id
        $historyStmt = $pdo->prepare("
            INSERT INTO doctor_token_history 
            (doctor_schedule_id_temp, category_id, user_id, slot_batch_id, old_token, new_token, total_token, updated_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $historyStmt->execute([
            $doctorScheduleId,  // This will now be 47, not 0
            $input['category_id'],
            $userId, // The user_id who owns the doctor schedule
            $input['batch_id'],
            $oldToken,
            $newToken,
            $newToken,
            $updatedBy // The user_id who made the update
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Token updated successfully",
            "old_token" => $oldToken,
            "new_token" => $newToken,
            "total_token" => $newToken,
            "batch_id" => $input['batch_id'],
            "doctor_schedule_id_temp" => $doctorScheduleId, // Send back for debugging
            "doctor_schedule_user_id" => $userId, // Send back the doctor schedule owner's user_id
            "updated_by_user_id" => $updatedBy, // Send back who made the update
            "parsed_day_index" => $parsedDayIndex,
            "parsed_slot_index" => $parsedSlotIndex
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Slot not found"
        ]);
    }
}