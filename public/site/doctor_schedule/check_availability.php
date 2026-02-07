<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

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

try {
    // Get doctor's schedule
    $scheduleStmt = $pdo->prepare("
        SELECT token_limit, weekly_schedule 
        FROM doctor_schedule 
        WHERE user_id = ?
    ");
    $scheduleStmt->execute([$userId]);
    $doctor = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        echo json_encode([
            'success' => false,
            'message' => 'Doctor schedule not found'
        ]);
        exit;
    }
    
    $tokenLimit = (int)$doctor['token_limit'];
    
    // Parse weekly schedule to find batch token
    $weeklySchedule = !empty($doctor['weekly_schedule']) 
        ? json_decode($doctor['weekly_schedule'], true) 
        : [];
    
    $batchToken = 0;
    $foundSlot = null;
    
    // Find the slot with this batch_id
    foreach ($weeklySchedule as $day => $daySchedule) {
        if (!empty($daySchedule['slots'])) {
            foreach ($daySchedule['slots'] as $slot) {
                if (isset($slot['batch_id']) && $slot['batch_id'] == $batchId) {
                    $batchToken = isset($slot['token']) ? (int)$slot['token'] : $tokenLimit;
                    $foundSlot = $slot;
                    break 2;
                }
            }
        }
    }
    
    if ($batchToken === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Batch not found in schedule'
        ]);
        exit;
    }
    
    // Count booked tokens
    $bookingStmt = $pdo->prepare("
        SELECT SUM(token_count) as total_booked 
        FROM customer_payment 
        WHERE user_id = ? 
        AND batch_id = ? 
        AND appointment_date = ? 
        AND status IN ('paid', 'pending', 'confirmed')
    ");
    
    $bookingStmt->execute([$userId, $batchId, $date]);
    $result = $bookingStmt->fetch(PDO::FETCH_ASSOC);
    
    $bookedCount = (int)($result['total_booked'] ?? 0);
    $remainingTokens = max(0, $batchToken - $bookedCount);
    $isAvailable = $remainingTokens > 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'available' => $isAvailable,
            'message' => $isAvailable 
                ? "Available ($remainingTokens tokens left)" 
                : "Appointment full ($bookedCount/$batchToken)",
            'booked' => $bookedCount,
            'total' => $batchToken,
            'remaining' => $remainingTokens,
            'token_limit' => $tokenLimit,
            'batch_token' => $batchToken,
            'slot_info' => $foundSlot
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>