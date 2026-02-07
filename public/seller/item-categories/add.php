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
   1️⃣ READ JSON BODY
---------------------------------------------------- */
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];

/* ----------------------------------------------------
   2️⃣ READ TOKEN (BODY OR COOKIE)
---------------------------------------------------- */
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
   3️⃣ FETCH USER USING TOKEN
---------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT id, user_id FROM users WHERE api_token = ? LIMIT 1"
);
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$user_id = $user->user_id;

/* ----------------------------------------------------
   4️⃣ VALIDATE INPUT
---------------------------------------------------- */
$name = trim($data["name"] ?? "");

if ($name === "") {
    echo json_encode([
        "success" => false,
        "message" => "Category name is required"
    ]);
    exit;
}

/* ----------------------------------------------------
   5️⃣ INSERT CATEGORY
---------------------------------------------------- */
$stmt = $pdo->prepare(
    "INSERT INTO item_categories (user_id, name) VALUES (?, ?)"
);

$ok = $stmt->execute([
    $user_id,
    $name
]);

if ($ok) {
    echo json_encode([
        "success" => true,
        "id"      => $pdo->lastInsertId(),
        "name"    => $name,
        "items"   => 0
    ]);
    exit;
}

/* ----------------------------------------------------
   6️⃣ ERROR FALLBACK
---------------------------------------------------- */
$error = $stmt->errorInfo();
echo json_encode([
    "success" => false,
    "message" => $error[2] ?? "Database error"
]);
exit;
