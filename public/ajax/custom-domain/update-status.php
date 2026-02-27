<?php
header('Content-Type: application/json');
require_once '../../../src/database.php';
require_once '../../../src/functions.php';

try {
    $pdo = getDbConnection();
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $domain = isset($_POST['domain']) ? $_POST['domain'] : '';
    
    if (!$id || !$user_id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    // Validate status
    $allowedStatuses = ['pending', 'active', 'inactive', 'rejected'];
    if (!in_array($status, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Get current domain request
    $stmt = $pdo->prepare("SELECT domain_request FROM website_settings WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $domainRequest = $stmt->fetchColumn();
    
    if (!$domainRequest) {
        echo json_encode(['success' => false, 'message' => 'Domain request not found']);
        exit;
    }
    
    $requestData = json_decode($domainRequest, true);
    $oldStatus = $requestData['status'] ?? 'pending';
    
    // Update the status
    $requestData['status'] = $status;
    $requestData['updatedAt'] = date('Y-m-d H:i:s');
    $requestData['updatedBy'] = 'admin';
    
    // If status is active, update the custom_domain field with the requested domain
    if ($status === 'active') {
        $updateDomainStmt = $pdo->prepare("UPDATE website_settings SET custom_domain = ? WHERE id = ?");
        $updateDomainStmt->execute([$domain, $id]);
    } elseif ($status === 'rejected' || $status === 'inactive' || $status === 'pending') {
        // Clear custom domain if not active
        $updateDomainStmt = $pdo->prepare("UPDATE website_settings SET custom_domain = NULL WHERE id = ?");
        $updateDomainStmt->execute([$id]);
    }
    
    // Update the domain_request JSON
    $updateStmt = $pdo->prepare("UPDATE website_settings SET domain_request = ? WHERE id = ?");
    $updateStmt->execute([json_encode($requestData), $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated to ' . ucfirst($status),
        'data' => [
            'old_status' => $oldStatus,
            'new_status' => $status
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}