<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$user_id = $_GET['user_id'] ?? '';
$limit   = $_GET['limit'] ?? 10;
$page    = $_GET['page'] ?? 1;
$q       = $_GET['q'] ?? '';

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "user_id is required"
    ]);
    exit();
}

$offset = ($page - 1) * $limit;

$searchSql = "";
$params = [':user_id' => $user_id];

if (!empty($q)) {
    $searchSql = " AND (d.name LIKE :search OR d.slug LIKE :search) ";
    $params[':search'] = "%$q%";
}

$countSql = "SELECT COUNT(*) 
             FROM departments d
             WHERE d.user_id = :user_id  
             $searchSql";

$countStmt = $pdo->prepare($countSql);
if (!empty($q)) $countStmt->bindValue(':search', "%$q%", PDO::PARAM_STR);
$countStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$countStmt->execute();

$totalRecords = $countStmt->fetchColumn();

$baseImageUrl = "http://localhost/manager.bookpanlam/public/uploads/";

// Build column list including all type fields (NO type column)
$columns = "
    d.id,
    d.department_id,
    d.user_id,
    d.name,
    d.slug,
    d.type_main_name,
    d.type_main_amount,
    d.type_1_name,
    d.type_1_amount,
    d.type_2_name,
    d.type_2_amount,
    d.type_3_name,
    d.type_3_amount,
    d.type_4_name,
    d.type_4_amount,
    d.type_5_name,
    d.type_5_amount,
    d.type_6_name,
    d.type_6_amount,
    d.type_7_name,
    d.type_7_amount,
    d.type_8_name,
    d.type_8_amount,
    d.type_9_name,
    d.type_9_amount,
    d.type_10_name,
    d.type_10_amount,
    d.type_11_name,
    d.type_11_amount,
    d.type_12_name,
    d.type_12_amount,
    d.type_13_name,
    d.type_13_amount,
    d.type_14_name,
    d.type_14_amount,
    d.type_15_name,
    d.type_15_amount,
    d.type_16_name,
    d.type_16_amount,
    d.type_17_name,
    d.type_17_amount,
    d.type_18_name,
    d.type_18_amount,
    d.type_19_name,
    d.type_19_amount,
    d.type_20_name,
    d.type_20_amount,
    d.type_21_name,
    d.type_21_amount,
    d.type_22_name,
    d.type_22_amount,
    d.type_23_name,
    d.type_23_amount,
    d.type_24_name,
    d.type_24_amount,
    d.type_25_name,
    d.type_25_amount,
    CASE 
        WHEN d.image IS NULL OR d.image = '' 
            THEN NULL
        ELSE CONCAT('$baseImageUrl', d.image) 
    END AS image,
    d.meta_title,
    d.meta_description,
    d.created_at,
    d.updated_at
";

$sql = "SELECT $columns
        FROM departments d
        WHERE d.user_id = :user_id 
        $searchSql
        ORDER BY d.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
if (!empty($q)) $stmt->bindValue(':search', "%$q%", PDO::PARAM_STR);
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "records" => $records,
    "totalRecords" => (int)$totalRecords,
    "totalPages" => ceil($totalRecords / $limit)
]);

exit();
?>