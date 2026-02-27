<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";
require_once "../../../../src/functions.php";

$pdo = getDbConnection();

$userId = $_GET["user_id"] ?? null;

if (!$userId) {
    echo json_encode([
        "success" => false, 
        "can_access" => false,
        "message" => "Missing user_id. Please make sure you are logged in."
    ]);
    exit;
}

// Use the function we created
$accessCheck = canUserAccessCustomDomain($userId);

echo json_encode([
    "success" => true,
    "can_access" => $accessCheck['can_access'],
    "message" => $accessCheck['message'],
    "custom_domain_enabled" => $accessCheck['custom_domain_enabled'],
    "plan_name" => $accessCheck['plan_name'],
    "plan_id" => $accessCheck['plan_id'] ?? null
]);