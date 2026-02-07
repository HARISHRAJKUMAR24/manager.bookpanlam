<?php
header("Content-Type: application/json");

require_once "../../config/config.php";
require_once "../../src/database.php";

$site = $_GET["site"] ?? "";

if (!$site) {
    echo json_encode([
        "success" => false,
        "message" => "Missing site name"
    ]);
    exit;
}

$db = getDbConnection();

$stmt = $db->prepare("
    SELECT id, business_name, site_slug 
    FROM users 
    WHERE site_slug = ? 
    LIMIT 1
");
$stmt->execute([$site]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid site"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "user" => $user
]);
exit;
?>
