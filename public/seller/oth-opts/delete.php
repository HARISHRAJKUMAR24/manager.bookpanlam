<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$category_id = $_GET['category_id'] ?? '';
$user_id     = $_GET['user_id'] ?? '';

// ⭐ ADD THIS LOG
error_log("DELETE REQUESTED category_id: " . $category_id);

if (!$category_id) {
    echo json_encode(["success" => false, "message" => "Category ID missing"]);
    exit();
}

$sql = "DELETE FROM categories WHERE category_id = :cid";
$stmt = $pdo->prepare($sql);
$stmt->execute([':cid' => $category_id]);

echo json_encode([
    "success" => $stmt->rowCount() > 0,
    "message" => $stmt->rowCount() > 0 ? "Category deleted" : "Category not found"
]);
