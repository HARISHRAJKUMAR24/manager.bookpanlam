<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// load PDO connection + helper functions
require_once __DIR__ . "/../../src/functions.php"; // this loads getDbConnection()
require_once __DIR__ . "/../../src/database.php";  // keep this if needed

if (!isset($_GET['slug'])) {
    echo json_encode(["error" => "missing slug parameter"]);
    exit;
}

$slug = $_GET['slug'];

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT 
        u.*, 
        s.logo, 
        s.favicon, 
        s.currency
    FROM users u
    LEFT JOIN site_settings s ON u.id = s.user_id
    WHERE u.site_slug = ?
    LIMIT 1");

    $stmt->execute([$slug]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($user ?: null);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
