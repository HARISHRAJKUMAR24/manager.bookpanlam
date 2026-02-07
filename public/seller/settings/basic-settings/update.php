<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* 🔐 Resolve user via token */
$headers = getallheaders();
$auth = $headers["Authorization"] ?? "";

if (strpos($auth, "Bearer ") !== 0) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$token = substr($auth, 7);

$stmt = $pdo->prepare("
    SELECT user_id 
    FROM users 
    WHERE api_token = ?
    LIMIT 1
");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $user["user_id"]; // ✅ 52064

$data = json_decode(file_get_contents("php://input"), true) ?? [];

/* Check existing settings */
$check = $pdo->prepare("SELECT id FROM site_settings WHERE user_id = ?");
$check->execute([$user_id]);
$exists = $check->fetch();

if ($exists) {
    $sql = "
        UPDATE site_settings SET
            logo = :logo,
            favicon = :favicon,
            email = :email,
            phone = :phone,
            whatsapp = :whatsapp,
            currency = :currency,
            country = :country,
            state = :state,
            address = :address
        WHERE user_id = :user_id
    ";
} else {
    $sql = "
        INSERT INTO site_settings
            (user_id, logo, favicon, email, phone, whatsapp, currency, country, state, address)
        VALUES
            (:user_id, :logo, :favicon, :email, :phone, :whatsapp, :currency, :country, :state, :address)
    ";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":user_id"  => $user_id,
    ":logo"     => $data["logo"] ?? null,
    ":favicon"  => $data["favicon"] ?? null,
    ":email"    => $data["email"] ?? null,
    ":phone"    => $data["phone"] ?? null,
    ":whatsapp" => $data["whatsapp"] ?? null,
    ":currency" => $data["currency"] ?? "INR",
    ":country"  => $data["country"] ?? null,
    ":state"    => $data["state"] ?? null,
    ":address"  => $data["address"] ?? null,
]);

echo json_encode([
    "success" => true,
    "message" => "Basic settings updated successfully"
]);
