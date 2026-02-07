<?php
require_once "../../src/database.php";

$userId = $_GET['user_id'] ?? null;
if (!$userId) die("Invalid request");

$token = bin2hex(random_bytes(32));
$pdo = getDbConnection();

$pdo->prepare("
  INSERT INTO seller_sso_tokens (user_id, token, expires_at)
  VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 SECOND))
")->execute([$userId, $token]);

header("Location: http://localhost:3000/sso?token=$token");
exit;
