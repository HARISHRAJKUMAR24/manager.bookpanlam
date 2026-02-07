<?php
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);

$currentPassword = $data["currentPassword"] ?? "";
$newPassword     = $data["password"] ?? "";
$token           = $data["token"] ?? "";

if (!$token || !$currentPassword || !$newPassword) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* FIND USER BY TOKEN */
$stmt = $pdo->prepare("SELECT user_id, password FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* VERIFY CURRENT PASSWORD */
if (!password_verify($currentPassword, $user["password"])) {
    echo json_encode([
        "success" => false,
        "message" => "Current password is incorrect"
    ]);
    exit;
}

/* UPDATE PASSWORD */
$newHash = password_hash($newPassword, PASSWORD_BCRYPT);

$update = $pdo->prepare(
    "UPDATE users SET password = ? WHERE user_id = ?"
);
$update->execute([$newHash, $user["user_id"]]);

echo json_encode([
    "success" => true,
    "message" => "Password updated successfully"
]);
