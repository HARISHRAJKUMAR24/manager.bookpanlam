<?php
header("Content-Type: application/json");

require_once "../config/config.php";   // FIXED PATH
require_once "../src/database.php";    // FIXED PATH

$site = $_GET["site"] ?? "";

if (!$site) {
    echo json_encode([
        "success" => false,
        "message" => "Missing site name"
    ]);
    exit;
}

$db = getDbConnection();

// Query using site_slug (correct)
$stmt = $db->prepare("
    SELECT id, user_id, name, phone, email, site_name, site_slug,service_type_id
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
