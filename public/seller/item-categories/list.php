<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ----------------------------------------------------
   1️⃣ READ TOKEN
---------------------------------------------------- */
$token = $_COOKIE["token"] ?? "";

if (!$token) {
    echo json_encode([]);
    exit;
}

/* ----------------------------------------------------
   2️⃣ FETCH USER
---------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT user_id FROM users WHERE api_token = ? LIMIT 1"
);
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo json_encode([]);
    exit;
}

$user_id = (int)$user->user_id;

/* ----------------------------------------------------
   3️⃣ OPTIONAL MENU FILTER
---------------------------------------------------- */
$menu_id = isset($_GET['menu_id']) && is_numeric($_GET['menu_id'])
    ? (int)$_GET['menu_id']
    : null;

/* ----------------------------------------------------
   4️⃣ FETCH CATEGORIES WITH ITEM COUNT ✅
---------------------------------------------------- */
if ($menu_id) {
    // Categories for a specific menu
    $sql = "
        SELECT 
            c.id,
            c.name,
            COUNT(mi.id) AS items
        FROM item_categories c
        LEFT JOIN menu_items mi 
            ON mi.category_id = c.id
            AND mi.menu_id = ?
        WHERE c.user_id = ?
        GROUP BY c.id, c.name
        ORDER BY c.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$menu_id, $user_id]);
} else {
    // All categories
    $sql = "
        SELECT 
            c.id,
            c.name,
            COUNT(mi.id) AS items
        FROM item_categories c
        LEFT JOIN menu_items mi 
            ON mi.category_id = c.id
        WHERE c.user_id = ?
        GROUP BY c.id, c.name
        ORDER BY c.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
}

/* ----------------------------------------------------
   5️⃣ RETURN RESPONSE
---------------------------------------------------- */
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($categories);
exit;
