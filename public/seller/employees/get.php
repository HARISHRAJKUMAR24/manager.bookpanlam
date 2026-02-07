<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$q = isset($_GET['q']) ? trim($_GET['q']) : "";

// REQUIRED
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "totalPages" => 1,
        "totalRecords" => 0,
        "records" => []
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM employees
    WHERE user_id = ?
    AND (name LIKE ? OR phone LIKE ?)
    ORDER BY id DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute([$user_id, "%$q%", "%$q%"]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE user_id = ?");
$countStmt->execute([$user_id]);
$totalRecords = $countStmt->fetchColumn();

$totalPages = ceil($totalRecords / $limit);

echo json_encode([
    "totalPages" => $totalPages,
    "totalRecords" => $totalRecords,
    "records" => $records
]);
exit;
