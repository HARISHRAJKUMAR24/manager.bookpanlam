<?php
header('Content-Type: application/json');

require_once '../../src/database.php';
require_once '../../src/functions.php';

try {
    $pdo = getDbConnection();
    
    // Test connection
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'tables' => [
            'subscription_plans' => countRows($pdo, 'subscription_plans'),
            'users' => countRows($pdo, 'users'),
            'settings' => countRows($pdo, 'settings')
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
}

function countRows($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}