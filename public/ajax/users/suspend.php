<?php
header('Content-Type: application/json');
require_once '../../../src/database.php';

// Get POST data
$userId = $_POST['user_id'] ?? 0; // This is actually the users.id (primary key)
$action = $_POST['action'] ?? '';
$message = $_POST['message'] ?? '';

if (!$userId) {
    echo json_encode(["type" => "error", "msg" => "User ID is required"]);
    exit();
}

try {
    $pdo = getDbConnection();
    
    // First, get the user's public user_id from users table
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        echo json_encode(["type" => "error", "msg" => "User not found"]);
        exit();
    }
    
    $publicUserId = $userData['user_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Determine new status
    if ($action === 'suspend') {
        $newStatus = 1; // Suspend (0 → 1)
        $statusText = 'suspended';
    } elseif ($action === 'unsuspend') {
        $newStatus = 0; // Unsuspend (1 → 0)
        $statusText = 'unsuspended';
    } else {
        echo json_encode(["type" => "error", "msg" => "Invalid action"]);
        exit();
    }
    
    // Record the suspension/unsuspension in suspend_users table - use public user_id
    $stmt = $pdo->prepare("INSERT INTO suspend_users (user_id, reason, action_type) VALUES (?, ?, ?)");
    $recordResult = $stmt->execute([$publicUserId, $message, $action]);
    
    if (!$recordResult) {
        $pdo->rollBack();
        echo json_encode(["type" => "error", "msg" => "Failed to record suspension history"]);
        exit();
    }
    
    // Update user suspension status - use primary key id
    $stmt = $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
    $updateResult = $stmt->execute([$newStatus, $userId]);
    
    if ($updateResult) {
        $pdo->commit();
        echo json_encode(["type" => "success", "msg" => "User account has been {$statusText} successfully!"]);
    } else {
        $pdo->rollBack();
        echo json_encode(["type" => "error", "msg" => "Failed to update user status"]);
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["type" => "error", "msg" => "Database error: " . $e->getMessage()]);
}
?>