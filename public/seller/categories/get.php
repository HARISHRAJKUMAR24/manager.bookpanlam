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

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* --------------------------------------
   INPUT PARAMS
--------------------------------------*/
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

$limit  = (int)$limit;
$page   = (int)$page;
$offset = ($page - 1) * $limit;

/* --------------------------------------
   SEARCH CONDITION
--------------------------------------*/
$searchSql = "";
$params = [':user_id' => $user_id];

if (!empty($q)) {
    $searchSql = " AND (name LIKE :search OR slug LIKE :search) ";
    $params[':search'] = "%$q%";
}

/* --------------------------------------
   COUNT TOTAL RECORDS
--------------------------------------*/
$countSql = "SELECT COUNT(*) 
             FROM categories
             WHERE user_id = :user_id
             $searchSql";

$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
if (!empty($q)) {
    $countStmt->bindValue(':search', "%$q%", PDO::PARAM_STR);
}
$countStmt->execute();

$totalRecords = (int)$countStmt->fetchColumn();

/* --------------------------------------
   BASE IMAGE URL
--------------------------------------*/
$baseImageUrl = "http://localhost/manager.bookpanlam/public/uploads/";

/* --------------------------------------
   FETCH CATEGORY + DOCTOR FIELDS
--------------------------------------*/
$sql = "SELECT
    id,
    category_id,
    user_id,
    name,
    slug,
    meta_title,
    meta_description,

    -- doctor fields inside category
    doctor_name,
    specialization,
    qualification,
    experience,
    reg_number,
    doctor_image,

    created_at
FROM categories
WHERE user_id = :user_id
$searchSql
ORDER BY id DESC
LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
if (!empty($q)) {
    $stmt->bindValue(':search', "%$q%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------
   IMAGE URL FIX
--------------------------------------*/
foreach ($records as &$row) {
    if (!empty($row['doctor_image'])) {
        $row['doctor_image'] = $baseImageUrl . $row['doctor_image'];
    }
}
unset($row);

/* --------------------------------------
   RESPONSE
--------------------------------------*/
echo json_encode([
    "success" => true,
    "records" => $records,
    "totalRecords" => $totalRecords,
    "totalPages" => ceil($totalRecords / $limit)
]);
exit();
