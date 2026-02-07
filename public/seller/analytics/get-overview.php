<?php
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* Read seller_id */
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_id"
    ]);
    exit;
}

/* -----------------------------
   TOTAL CUSTOMERS
------------------------------*/
$stm = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE user_id = ?");
$stm->execute([$user_id]);
$totalCustomers = (int)$stm->fetchColumn();

/* -----------------------------
   TOTAL APPOINTMENTS
------------------------------*/
$stm = $pdo->prepare("SELECT COUNT(*) FROM customer_payment WHERE user_id = ?");
$stm->execute([$user_id]);
$totalAppointments = (int)$stm->fetchColumn();

/* -----------------------------
   TOTAL REVENUE (PAID ONLY)
------------------------------*/
$stm = $pdo->prepare("
    SELECT SUM(total_amount) 
    FROM customer_payment 
    WHERE user_id = ? AND status = 'paid'
");
$stm->execute([$user_id]);
$totalRevenue = (float)$stm->fetchColumn();

/* -----------------------------
   TOTAL SERVICES (categories)
------------------------------*/
$stm = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE user_id = ?");
$stm->execute([$user_id]);
$totalServices = (int)$stm->fetchColumn();

/* -----------------------------
   TOTAL EMPLOYEES
------------------------------*/
$stm = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE user_id = ?");
$stm->execute([$user_id]);
$totalEmployees = (int)$stm->fetchColumn();

/* -----------------------------
   RESPONSE
------------------------------*/
echo json_encode([
    "success" => true,
    "totalRevenue" => $totalRevenue ?: 0,
    "totalAppointments" => $totalAppointments,
    "totalCustomers" => $totalCustomers,
    "totalServices" => $totalServices,
    "totalEmployees" => $totalEmployees
]);

exit;
