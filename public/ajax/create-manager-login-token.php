<?php
// ✅ FORCE TIMEZONE (CRITICAL FIX)
date_default_timezone_set('Asia/Kolkata');

// Hide notices in production
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

require_once '../../src/database.php';

// ✅ Validate input
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user id'
    ]);
    exit;
}

$userId = (int) $_POST['user_id'];
$token  = bin2hex(random_bytes(32));

$pdo = getDbConnection();

/**
 * 🔥 IMPORTANT
 * - Use seller_sso_tokens
 * - Let MySQL generate expiry using NOW()
 * - Avoid PHP time mismatch bugs
 */
$stmt = $pdo->prepare("
    INSERT INTO seller_sso_tokens (user_id, token, expires_at, used)
    VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 2 MINUTE), 0)
");

$stmt->execute([
    ':user_id' => $userId,
    ':token'   => $token
]);

echo json_encode([
    'success' => true,
    'token'   => $token
]);
exit;
