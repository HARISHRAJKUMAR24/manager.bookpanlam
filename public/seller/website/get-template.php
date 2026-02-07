<?php
// seller/website/get-template.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$user_id = intval($_GET['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid user_id"
    ]);
    exit;
}

$db = getDbConnection();

$stmt = $db->prepare("
    SELECT selected_template 
    FROM site_settings 
    WHERE user_id = ? 
    LIMIT 1
");
$stmt->execute([$user_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "template" => intval($data['selected_template'] ?? 1)
]);
exit;
?>
