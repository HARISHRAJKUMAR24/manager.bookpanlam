<?php

/* ===============================
   HEADERS / CORS
================================ */
$allowedOrigins = ['http://localhost:3000'];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Content-Type: application/json");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

/* ===============================
   PREFLIGHT
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

/* ===============================
   BOOTSTRAP
================================ */
require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   READ INPUT
================================ */
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];

/* ===============================
   AUTH: TOKEN → USER
================================ */
$token =
    ($data["token"] ?? null)
    ?: ($_COOKIE["token"] ?? "");

if (!$token) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized: Missing token"
    ]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT user_id FROM users WHERE api_token = ? LIMIT 1"
);
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$user_id = (int) $user->user_id;

/* ===============================
   VALIDATION
================================ */
if (!isset($data['id']) || !isset($data['available'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "ID and available status are required"
    ]);
    exit;
}

$itemId    = (int) $data['id'];
$available = $data['available'] ? 1 : 0;

/* ===============================
   UPDATE AVAILABILITY
================================ */
try {
    $stmt = $pdo->prepare("
        UPDATE menu_items
        SET active = :active,
            updated_at = NOW()
        WHERE id = :id
          AND user_id = :user_id
    ");

    $stmt->execute([
        ":active"  => $available,
        ":id"      => $itemId,
        ":user_id" => $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Menu item not found or access denied");
    }

    echo json_encode([
        "success" => true,
        "message" => "Availability updated successfully",
        "active"  => $available
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
