<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$user_id = (int)($_GET['user_id'] ?? 0);
$customer_id = ($_GET['customer_id'] ?? null);
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 10);
$offset = ($page - 1) * $limit;

if ($user_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid seller"
    ]);
    exit;
}

/* COUNT WITH CUSTOMER FILTER */
$countSql = "
    SELECT COUNT(*) 
    FROM appointments 
    WHERE user_id = :user_id
";

if (!empty($customer_id)) {
    $countSql .= " AND customer_id = :customer_id";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

if (!empty($customer_id)) {
    $countStmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
}

$countStmt->execute();
$totalRecords = (int)$countStmt->fetchColumn();

/* FETCH WITH CUSTOMER FILTER */
$sql = "
    SELECT
        a.id,
        a.appointment_id,
        a.date,
        a.time,
        a.status,
        a.paymentStatus,
        a.total_amount,

        c.name  AS customer_name,
        c.phone AS customer_phone,

        d.name  AS doctor_name
    FROM appointments a
    JOIN customers c ON c.id = a.customer_id
    JOIN doctor_schedule d ON d.id = a.service_id
    WHERE a.user_id = :user_id
";

if (!empty($customer_id)) {
    $sql .= " AND a.customer_id = :customer_id";
}

$sql .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
$stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

if (!empty($customer_id)) {
    $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
}

$stmt->execute();

echo json_encode([
    "success" => true,
    "records" => $stmt->fetchAll(PDO::FETCH_ASSOC),
    "totalRecords" => $totalRecords,
    "totalPages" => ceil($totalRecords / $limit)
]);
