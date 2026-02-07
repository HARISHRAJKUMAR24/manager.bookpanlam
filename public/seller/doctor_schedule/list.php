<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* =====================
   AUTH VIA COOKIE TOKEN
===================== */
$token = $_COOKIE['token'] ?? null;

if (!$token) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$userId = (int)$user['user_id'];

/* =====================
   FETCH DOCTOR SCHEDULES
===================== */
$sql = "
SELECT
    id AS serviceId,
    id AS id,
    user_id AS userId,
    name,
    slug,
    amount,
    doctor_image AS image,
    specialization,
    qualification,
    created_at AS createdAt
FROM doctor_schedule
WHERE user_id = ?
ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "records" => $records,
    "totalRecords" => count($records),
    "totalPages" => 1
]);
