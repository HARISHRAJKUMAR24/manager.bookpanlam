<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 5) . "/src/database.php";
$pdo = getDbConnection();

/* ---------- AUTH ---------- */
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$token = trim(substr($authHeader, 7));

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = (int)$user['user_id'];

/* ---------- INPUT ---------- */
$payload = json_decode(file_get_contents("php://input"), true);
$id = (int)($payload['id'] ?? 0);

if (!$id) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit;
}

/* ---------- FETCH EXISTING ICON BEFORE DELETE ---------- */
$stmt = $pdo->prepare("
    SELECT icon
    FROM manual_payment_methods
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$id, $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(["success" => false, "message" => "Payment method not found"]);
    exit;
}

/* ---------- DELETE ICON FILE ---------- */
if (!empty($row['icon'])) {
    $filePath = dirname(__DIR__, 5) . "/public/" . ltrim($row['icon'], "/");

    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

/* ---------- DELETE DB RECORD ---------- */
$stmt = $pdo->prepare("
    DELETE FROM manual_payment_methods
    WHERE id = ? AND user_id = ?
");
$success = $stmt->execute([$id, $user_id]);

if (!$success) {
    echo json_encode(["success" => false, "message" => "Failed to delete"]);
    exit;
}

/* ---------- SUCCESS ---------- */
echo json_encode([
    "success" => true,
    "message" => "Manual payment method deleted successfully"
]);
