<?php
header('Content-Type: application/json');
require_once '../../../src/database.php';

// Get POST data - This is user_id (public ID)
$userUid = $_POST['user_id'] ?? 0;

if (!$userUid) {
    echo json_encode(["type" => "error", "msg" => "User ID is required"]);
    exit();
}

try {
    $pdo = getDbConnection();
    
    // Get user details first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$userUid]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(["type" => "error", "msg" => "User not found"]);
        exit();
    }
    
    $publicUserId = $user['user_id'];
    $primaryKeyId = $user['id']; // This is the auto-increment primary key
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // First delete suspension history - use public user_id
        $stmt = $pdo->prepare("DELETE FROM suspend_users WHERE user_id = ?");
        $stmt->execute([$publicUserId]);
        
        // Then delete other related data
        $tables = [
            'appointments',
            'appointment_settings', 
            'categories',
            'customers',
            'coupons',
            'departments',
            'employees',
            'events',
            'site_settings',
            'subscription_histories'
        ];
        
        foreach ($tables as $table) {
            // Delete by public user_id
            $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id = ?");
            $stmt->execute([$publicUserId]);
        }
        
        // Finally delete the user - using public user_id
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $result = $stmt->execute([$userUid]);
        
        $pdo->commit();
        
        if ($result) {
            // Delete uploaded files
            $uploadDir = __DIR__ . "/../../../uploads/sellers/" . $publicUserId . "/";
            if (file_exists($uploadDir)) {
                deleteDirectory($uploadDir);
            }
            
            echo json_encode(["type" => "success", "msg" => "User and all associated data have been permanently deleted!"]);
        } else {
            echo json_encode(["type" => "error", "msg" => "Failed to delete user"]);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(["type" => "error", "msg" => "Database error: " . $e->getMessage()]);
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    
    return rmdir($dir);
}
?>