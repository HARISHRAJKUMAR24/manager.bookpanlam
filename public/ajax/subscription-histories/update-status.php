<?php
header('Content-Type: application/json');
require_once '../../../src/database.php';

try {
    // Get POST data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    if (!$id || !in_array($status, ['active', 'refunded', 'cancelled'])) {
        throw new Exception('Invalid parameters');
    }
    
    $pdo = getDbConnection();
    
    // Update status
    $stmt = $pdo->prepare("UPDATE subscription_histories SET status = ? WHERE id = ?");
    $result = $stmt->execute([$status, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update status');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>