<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/json");

require_once "../../src/database.php";

$input = json_decode(file_get_contents("php://input"), true);
$token = $input['token'] ?? '';

if (!$token) {
    echo json_encode(["success" => false]);
    exit;
}

$pdo = getDbConnection();

/* 1️⃣ Validate SSO token */
$stmt = $pdo->prepare("
    SELECT * FROM seller_sso_tokens
    WHERE token = ?
      AND used = 0
      AND expires_at > NOW()
    LIMIT 1
");
$stmt->execute([$token]);
$sso = $stmt->fetch(PDO::FETCH_OBJ);

if (!$sso) {
    echo json_encode(["success" => false]);
    exit;
}

/* 2️⃣ Fetch seller */
$userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$userStmt->execute([$sso->user_id]);
$user = $userStmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo json_encode(["success" => false]);
    exit;
}

/* 3️⃣ Create real login token */
$apiToken = bin2hex(random_bytes(32));

$pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?")
    ->execute([$apiToken, $user->id]);

/* 4️⃣ Mark SSO token as used */
$pdo->prepare("UPDATE seller_sso_tokens SET used = 1 WHERE id = ?")
    ->execute([$sso->id]);

/* 5️⃣ Success */
echo json_encode([
    "success" => true,
    "api_token" => $apiToken
]);
exit;
