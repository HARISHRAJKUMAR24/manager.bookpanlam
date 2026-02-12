<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

$user_id = $_GET["user_id"] ?? 0;
$view = $_GET["view"] ?? "month"; // year, month, OR day
$limit = $_GET["limit"] ?? 12; // Number of periods to show

if (!$user_id) {
    echo json_encode([]);
    exit;
}

/* ---------------------------
   📌 YEAR VIEW
   --------------------------- */
if ($view === "year") {
    // Get data for last X years
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '%Y') AS label,
            YEAR(created_at) AS year_key,
            COALESCE(SUM(amount), 0) AS revenue,
            COALESCE(COUNT(id), 0) AS appointments
        FROM customer_payment
        WHERE user_id = :user_id
          AND status = 'paid'
          AND YEAR(created_at) >= YEAR(NOW()) - :limit
        GROUP BY YEAR(created_at)
        ORDER BY year_key ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for chart
    $formattedData = [];
    foreach ($rows as $row) {
        $formattedData[] = [
            'month' => $row['label'],
            'actual' => (float)$row['revenue'],
            'appointments' => (int)$row['appointments']
        ];
    }
    
    echo json_encode($formattedData);
    exit;
}

/* ---------------------------
   📌 MONTH VIEW (default) - Show months horizontally
   --------------------------- */
if ($view === "month") {
    // Get data for last X months
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '%b-%y') AS label,     -- Feb-24, Mar-24 format
            DATE_FORMAT(created_at, '%Y-%m') AS month_key,
            COALESCE(SUM(amount), 0) AS revenue,
            COALESCE(COUNT(id), 0) AS appointments
        FROM customer_payment
        WHERE user_id = :user_id
          AND status = 'paid'
          AND created_at >= DATE_SUB(NOW(), INTERVAL :limit MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month_key ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for chart
    $formattedData = [];
    foreach ($rows as $row) {
        $formattedData[] = [
            'month' => $row['label'],
            'actual' => (float)$row['revenue'],
            'appointments' => (int)$row['appointments']
        ];
    }
    
    echo json_encode($formattedData);
    exit;
}

/* ---------------------------
   📌 DAY VIEW - Show days horizontally
   --------------------------- */
if ($view === "day") {
    // Get data for last X days
    $sql = "
        SELECT 
            DATE(created_at) AS date,
            DATE_FORMAT(created_at, '%b %d') AS label,     -- Feb 26
            COALESCE(SUM(amount), 0) AS revenue,
            COALESCE(COUNT(id), 0) AS appointments
        FROM customer_payment
        WHERE user_id = :user_id
          AND status = 'paid'
          AND DATE(created_at) >= DATE(NOW() - INTERVAL :limit DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for chart
    $formattedData = [];
    foreach ($rows as $row) {
        $formattedData[] = [
            'month' => $row['label'],
            'actual' => (float)$row['revenue'],
            'appointments' => (int)$row['appointments']
        ];
    }
    
    echo json_encode($formattedData);
    exit;
}

// Default return empty array
echo json_encode([]);
?>