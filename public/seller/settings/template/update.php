<?php
require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$input = json_decode(file_get_contents("php://input"), true);

$user_id = intval($input["user_id"] ?? 0);
$template_id = intval($input["template_id"] ?? 0);

if (!$user_id || !$template_id) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$db = getDbConnection();

// Check if row exists
$check = $db->prepare("SELECT id FROM template_settings WHERE user_id = ? LIMIT 1");
$check->execute([$user_id]);

if ($check->rowCount() > 0) {
    // Update template
    $update = $db->prepare("UPDATE template_settings SET template_id = ? WHERE user_id = ?");
    $update->execute([$template_id, $user_id]);
} else {
    // Insert new setting
    $insert = $db->prepare("INSERT INTO template_settings (user_id, template_id) VALUES (?, ?)");
    $insert->execute([$user_id, $template_id]);
}

echo json_encode([
    "success" => true,
    "message" => "Template updated successfully"
]);
?>
