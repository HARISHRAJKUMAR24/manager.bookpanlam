<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* READ JSON INPUT */
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "Menu item ID missing"
    ]);
    exit;
}

/* ============================
   1. GET IMAGE PATH BEFORE DELETE
=============================== */

$stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

$imagePath = $item['image'] ?? null;

/* ============================
   2. DELETE VARIATIONS FIRST
=============================== */
$pdo->prepare("DELETE FROM menu_item_variations WHERE item_id = ?")
    ->execute([$id]);

/* ============================
   3. DELETE MENU ITEM
=============================== */
$stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Menu item not found"
    ]);
    exit;
}

/* ============================
   4. DELETE IMAGE FILE FROM SERVER
=============================== */

if ($imagePath) {

    // /uploads/... → convert to disk path
    $filePath = __DIR__ . "/../../" . ltrim($imagePath, "/");

    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

/* ============================
   RESPONSE
=============================== */
echo json_encode([
    "success" => true,
    "message" => "Menu item deleted successfully"
]);
exit;
