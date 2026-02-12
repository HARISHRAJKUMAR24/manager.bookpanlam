<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

$user_id = $_GET["user_id"] ?? 0;
$view = $_GET["view"] ?? "month";
$limit = $_GET["limit"] ?? 12;

if (!$user_id) {
    echo json_encode([]);
    exit;
}

/* ---------------------------
   📌 GET AVAILABLE YEARS - NEW ENDPOINT
   --------------------------- */
if ($view === "getYears") {
    $sql = "
        SELECT DISTINCT YEAR(appointment_date) AS year
        FROM customer_payment
        WHERE user_id = :user_id
          AND status IN ('paid', 'pending')
          AND appointment_date IS NOT NULL
        ORDER BY year DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($years);
    exit;
}

/* ---------------------------
   📌 YEAR VIEW - Fixed grouping
   --------------------------- */
if ($view === "year") {
    $sql = "
        SELECT 
            YEAR(appointment_date) AS year,
            COALESCE(SUM(CAST(total_amount AS DECIMAL(10,2))), 0) AS revenue,
            COUNT(*) AS appointments
        FROM customer_payment
        WHERE user_id = :user_id
          AND status IN ('paid', 'pending')
          AND appointment_date IS NOT NULL
        GROUP BY YEAR(appointment_date)
        ORDER BY year ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedData = [];
    foreach ($rows as $row) {
        $formattedData[] = [
            'month' => (string)$row['year'],
            'actual' => (float)$row['revenue'],
            'appointments' => (int)$row['appointments']
        ];
    }
    
    echo json_encode($formattedData);
    exit;
}

/* ---------------------------
   📌 MONTH VIEW - Fixed to show only selected year/month
   --------------------------- */
if ($view === "month") {
    $selectedYear = $_GET["year"] ?? null;
    $selectedMonth = $_GET["month"] ?? null;
    
    $sql = "
        SELECT 
            appointment_date AS date,
            COALESCE(SUM(CAST(total_amount AS DECIMAL(10,2))), 0) AS revenue,
            COUNT(*) AS appointments
        FROM customer_payment
        WHERE user_id = :user_id
          AND status IN ('paid', 'pending')
          AND appointment_date IS NOT NULL
    ";
    
    $params = [":user_id" => $user_id];
    
    if ($selectedYear && $selectedMonth) {
        $sql .= " AND YEAR(appointment_date) = :year AND MONTH(appointment_date) = :month";
        $params[":year"] = $selectedYear;
        $params[":month"] = $selectedMonth;
    }
    
    $sql .= " GROUP BY appointment_date ORDER BY appointment_date ASC";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedData = [];
    foreach ($rows as $row) {
        $formattedData[] = [
            'month' => $row['date'],
            'actual' => (float)$row['revenue'],
            'appointments' => (int)$row['appointments']
        ];
    }
    
    echo json_encode($formattedData);
    exit;
}

/* ---------------------------
   📌 GET MONTHS FOR YEAR - NEW ENDPOINT
   --------------------------- */
if ($view === "getMonths") {
    $year = $_GET["year"] ?? null;
    
    if (!$year) {
        echo json_encode([]);
        exit;
    }
    
    $sql = "
        SELECT DISTINCT 
            DATE_FORMAT(appointment_date, '%Y-%m') AS month
        FROM customer_payment
        WHERE user_id = :user_id
          AND status IN ('paid', 'pending')
          AND appointment_date IS NOT NULL
          AND YEAR(appointment_date) = :year
        ORDER BY month ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":year", $year, PDO::PARAM_INT);
    $stmt->execute();
    
    $months = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($months);
    exit;
}

echo json_encode([]);
exit;