<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Correct include path
require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

$user_id = $_GET["user_id"] ?? 0;
$days = $_GET["days"] ?? 7;

if (!$user_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        DATE(created_at) AS date,
        SUM(amount) AS revenue,
        COUNT(id) AS appointments
    FROM customer_payment
    WHERE user_id = :user_id
      AND status = 'paid'  -- ✔ only successful payments
      AND DATE(created_at) >= DATE(NOW() - INTERVAL :days DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
$stmt->bindValue(":days", $days, PDO::PARAM_INT);
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
