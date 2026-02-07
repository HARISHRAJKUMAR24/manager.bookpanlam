<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ----------------------------------------------------
   1️⃣ READ TOKEN (JSON BODY OR COOKIE)
---------------------------------------------------- */
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];

$token =
    ($data["token"] ?? null)
    ?: ($_COOKIE["token"] ?? "");

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized: Missing token"
    ]);
    exit;
}

/* ----------------------------------------------------
   2️⃣ FETCH USER USING TOKEN
---------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT id, user_id FROM users WHERE api_token = ? LIMIT 1"
);
$stmt->execute([$token]);
$user = $stmt->fetchObject();

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$user_id = $user->user_id; // ⭐ THIS IS YOUR SELLER/USER ID

/* ----------------------------------------------------
   3️⃣ VALIDATE INPUT
---------------------------------------------------- */
$name = trim($data["name"] ?? "");

if ($name === "") {
    echo json_encode([
        "success" => false,
        "message" => "Menu name is required"
    ]);
    exit;
}

/* ----------------------------------------------------
   4️⃣ INSERT MENU
---------------------------------------------------- */
$stmt = $pdo->prepare(
    "INSERT INTO menus (user_id, name) VALUES (?, ?)"
);

$ok = $stmt->execute([
    $user_id,
    $name
]);

if ($ok) {
    echo json_encode([
        "success" => true,
        "id"      => $pdo->lastInsertId(),
        "name"    => $name
    ]);
    exit;
}

/* ----------------------------------------------------
   5️⃣ ERROR FALLBACK
---------------------------------------------------- */
$error = $stmt->errorInfo();
echo json_encode([
    "success" => false,
    "message" => $error[2] ?? "Database error"
]);
exit;
