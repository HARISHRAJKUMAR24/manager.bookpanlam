<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$headers = getallheaders();
$token = $headers["Authorization"] ?? "";
$token = str_replace("Bearer ", "", $token);

if (!$token) {
    echo json_encode(["success" => false, "message" => "Token missing"]);
    exit;
}   

$pdo = getDbConnection();

$stmt = $pdo->prepare("SELECT * FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$name = $data["name"] ?? $user["name"];
$email = $data["email"] ?? $user["email"];
$phone = $data["phone"] ?? $user["phone"];
$country = $data["country"] ?? $user["country"];

$update = $pdo->prepare("
    UPDATE users 
    SET name=?, email=?, phone=?, country=? 
    WHERE id=? 
");

$success = $update->execute([
    $name,
    $email,
    $phone,
    $country,
    $user["id"]
]);
