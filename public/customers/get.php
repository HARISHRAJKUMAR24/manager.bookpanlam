<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/* ✅ CORRECT PATHS */
require_once "../../config/config.php";
require_once "../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   INPUT
================================ */
$user_id = $_GET['user_id'] ?? null;
$page    = (int)($_GET['page'] ?? 1);
$limit   = (int)($_GET['limit'] ?? 10);
$q       = trim($_GET['q'] ?? "");

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required",
        "totalPages" => 1,
        "totalRecords" => 0,
        "records" => []
    ]);
    exit();
}

$offset = ($page - 1) * $limit;

/* ===============================
   WHERE
================================ */
$where = "WHERE user_id = :user_id"; 
$params = [":user_id" => $user_id];


/* ===============================
   COUNT
================================ */
$countSql = "SELECT COUNT(*) FROM customers $where";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v);
}
$countStmt->execute();
$totalRecords = (int)$countStmt->fetchColumn();

/* ===============================
   RECORDS
================================ */
$sql = "
    SELECT
        customer_id,
        name,
        email,
        phone,
        photo,
        created_at
    FROM customers
    $where
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "totalPages" => ceil($totalRecords / $limit),
    "totalRecords" => $totalRecords,
    "records" => $records
]);
