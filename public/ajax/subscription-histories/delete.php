<?php
header('Content-Type: application/json');
require_once '../../../src/database.php';

try {
    // Get POST data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (!$id) {
        throw new Exception('Invalid ID');
    }
    
    $pdo = getDbConnection();
    
    // Delete record
    $stmt = $pdo->prepare("DELETE FROM subscription_histories WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Subscription history deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete record');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>