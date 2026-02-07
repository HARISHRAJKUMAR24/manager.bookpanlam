<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ----------------------------------------------------
   1️⃣ READ TOKEN (COOKIE)
---------------------------------------------------- */
$token = $_COOKIE["token"] ?? "";

if (!$token) {
    echo json_encode([]);
    exit;
}

/* ----------------------------------------------------
   2️⃣ FETCH USER USING TOKEN
---------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT user_id FROM users WHERE api_token = ? LIMIT 1"
);
$stmt->execute([$token]);
$user = $stmt->fetchObject();

if (!$user) {
    echo json_encode([]);
    exit;
}

$user_id = $user->user_id;

/* ----------------------------------------------------
   3️⃣ FETCH MENUS FOR THIS USER
---------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT 
        m.id,
        m.name,
        COUNT(mi.id) AS items
    FROM menus m
    LEFT JOIN menu_items mi ON mi.menu_id = m.id
    WHERE m.user_id = ?
    GROUP BY m.id
    ORDER BY m.id DESC
");

$stmt->execute([$user_id]);
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ----------------------------------------------------
   4️⃣ RETURN DATA
---------------------------------------------------- */
echo json_encode($menus);
