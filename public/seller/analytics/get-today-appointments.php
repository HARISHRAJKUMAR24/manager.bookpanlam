<?php
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* Read seller_id */
$user_id = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_id",
        "todayPaid" => 0,
        "todayPending" => 0
    ]);
    exit;
}

try {

    // ⭐ TODAY PAID
    $stmtPaid = $pdo->prepare("
        SELECT COUNT(*) AS total 
        FROM customer_payment 
        WHERE user_id = ?
        AND appointment_date = CURDATE()
        AND status = 'paid'
    ");
    $stmtPaid->execute([$user_id]);
    $paid = (int)$stmtPaid->fetchColumn();


    // ⭐ TODAY PENDING
    $stmtPending = $pdo->prepare("
        SELECT COUNT(*) AS total 
        FROM customer_payment 
        WHERE user_id = ?
        AND appointment_date = CURDATE()
        AND status = 'pending'
    ");
    $stmtPending->execute([$user_id]);
    $pending = (int)$stmtPending->fetchColumn();


    echo json_encode([
        "success" => true,
        "todayPaid" => $paid,
        "todayPending" => $pending
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "todayPaid" => 0,
        "todayPending" => 0,
        "message" => "DB Error"
    ]);
}
