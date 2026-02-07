<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/* ✅ CORRECT PATHS */
require_once "../../src/database.php";
require_once "../../src/functions.php";

$pdo = getDbConnection();

/* ===============================
   INPUT
================================ */
$user_id = $_GET['user_id'] ?? null;
$resource_type = $_GET['resource_type'] ?? 'customers';

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required",
        "can_add" => false,
        "current" => 0,
        "limit" => 0,
        "remaining" => 0,
        "plan_expired" => false,
        "expiry_message" => ""
    ]);
    exit();
}

/* ===============================
   CHECK LIMIT USING EXISTING FUNCTION
================================ */
$result = getUserPlanLimit($user_id, $resource_type);

echo json_encode([
    "success" => true,
    "can_add" => $result['can_add'],
    "message" => $result['message'],
    "current" => $result['current'],
    "limit" => $result['limit'],
    "remaining" => $result['remaining'],
    "plan_expired" => $result['plan_expired'] ?? false,
    "expiry_message" => $result['expiry_message'] ?? ''
]);

exit();
?>