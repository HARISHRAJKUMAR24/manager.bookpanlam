<?php
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$user_id = $_GET["user_id"] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_id"
    ]);
    exit;
}

$db = getDbConnection();

$stmt = $db->prepare("SELECT template_id FROM template_settings WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$template = $row["template_id"] ?? 1; // default 1

echo json_encode([
    "success" => true,
    "template" => intval($template)
]);
exit;
