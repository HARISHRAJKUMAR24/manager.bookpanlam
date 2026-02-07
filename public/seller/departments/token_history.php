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
    $departmentId = $_GET['department_id'] ?? null;
    $batchId = $_GET['batch_id'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    
    if (!$departmentId) {
        echo json_encode([
            "success" => false,
            "message" => "Department ID is required"
        ]);
        exit;
    }
    
    $sql = "SELECT * FROM department_token_history WHERE department_id = ?";
    $params = [$departmentId];
    
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
            $record['parsed_slot_index'] = isset($parts[1]) ? (int)$parts[1] + 1 : null;
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
    
    $required = ['department_id', 'batch_id', 'action', 'value'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    // Get department data
    $stmt = $pdo->prepare("SELECT id, appointment_settings, user_id FROM departments WHERE department_id = ? LIMIT 1");
    $stmt->execute([$input['department_id']]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$department) {
        echo json_encode([
            "success" => false,
            "message" => "Department not found"
        ]);
        exit;
    }
    
    $departmentId = $department['id'];
    $userId = $department['user_id'] ?? null;
    
    $appointmentSettings = json_decode($department['appointment_settings'], true);
    $updated = false;
    $oldToken = 0;
    $newToken = 0;
    
    // Find and update the slot
    foreach ($appointmentSettings as $day => &$dayData) {
        if ($dayData['enabled']) {
            foreach ($dayData['slots'] as &$slot) {
                if ($slot['batch_id'] === $input['batch_id']) {
                    // Ensure token is treated as number
                    $oldToken = is_numeric($slot['token']) ? (int)$slot['token'] : 0;
                    
                    if ($input['action'] === 'set') {
                        $newToken = (int)$input['value'];
                    } elseif ($input['action'] === 'increase') {
                        $newToken = $oldToken + (int)$input['value'];
                    } elseif ($input['action'] === 'decrease') {
                        $newToken = max(0, $oldToken - (int)$input['value']);
                    }
                    
                    $slot['token'] = $newToken; // Store as number
                    $updated = true;
                    break 2;
                }
            }
        }
    }
    
    if ($updated) {
        // Update department appointment settings
        $updateStmt = $pdo->prepare("UPDATE departments SET appointment_settings = ? WHERE department_id = ?");
        $updateStmt->execute([json_encode($appointmentSettings), $input['department_id']]);
        
        // Parse day and slot from batch_id
        $parsedDayIndex = null;
        $parsedSlotIndex = null;
        if (isset($input['batch_id'])) {
            $parts = explode(':', $input['batch_id']);
            $parsedDayIndex = isset($parts[0]) ? (int)$parts[0] : null;
            $parsedSlotIndex = isset($parts[1]) ? (int)$parts[1] + 1 : null;
        }
        
        // Get updated_by from session
        $updatedBy = $_SESSION['user_id'] ?? null;
        
        // Log to history
        $historyStmt = $pdo->prepare("
            INSERT INTO department_token_history 
            (department_id, user_id, slot_batch_id, old_token, new_token, total_token, updated_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $historyStmt->execute([
            $input['department_id'],
            $userId,
            $input['batch_id'],
            $oldToken,
            $newToken,
            $newToken,
            $updatedBy
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Token updated successfully",
            "old_token" => $oldToken,
            "new_token" => $newToken,
            "total_token" => $newToken,
            "batch_id" => $input['batch_id'],
            "department_id" => $input['department_id']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Slot not found. Check if batch_id exists in appointment settings."
        ]);
    }
}