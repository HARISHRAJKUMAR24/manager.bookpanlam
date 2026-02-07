<?php
// seller/website/update-template.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$input = json_decode(file_get_contents("php://input"), true);

$user_id  = intval($input['user_id'] ?? 0);
$template = intval($input['template'] ?? 1);

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid user_id"
    ]);
    exit;
}

$db = getDbConnection();

// update template
$stmt = $db->prepare("
    UPDATE site_settings 
    SET selected_template = ? 
    WHERE user_id = ?
");
$updated = $stmt->execute([$template, $user_id]);

echo json_encode([
    "success" => $updated ? true : false,
    "message" => $updated ? "Template updated successfully" : "Failed to update template"
]);
exit;
?>
