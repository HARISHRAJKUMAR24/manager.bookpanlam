<?php
// 🔥 FORCE LOGOUT PREVIOUS SELLER
setcookie("token", "", time() - 3600, "/");

$token = $_GET['token'] ?? null;
if (!$token) die("Invalid SSO token");

header("Location: http://localhost:3000/sso?token=" . urlencode($token));
exit;
