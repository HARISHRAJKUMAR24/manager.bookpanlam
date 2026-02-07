<?php
// seller/coupons/get.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

// Get parameters
$user_id = $_GET['user_id'] ?? null;
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$q = $_GET['q'] ?? '';
$offset = ($page - 1) * $limit;

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

// Build query
$where = "WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

if (!empty($q)) {
    $where .= " AND (name LIKE :q OR code LIKE :q)";
    $params[':q'] = "%$q%";
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM coupons $where";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();

// Get paginated data
$sql = "SELECT * FROM coupons $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format dates for frontend
foreach ($records as &$record) {
    $record['start_date'] = date('Y-m-d\TH:i:s', strtotime($record['start_date']));
    $record['end_date'] = date('Y-m-d\TH:i:s', strtotime($record['end_date']));
}

echo json_encode([
    'success' => true,
    'totalPages' => ceil($totalRecords / $limit),
    'totalRecords' => (int)$totalRecords,
    'records' => $records
]);