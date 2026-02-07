<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);

$userId = $data["user_id"] ?? null;

if (!$userId) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit;
}

// Load existing row
$stmt = $pdo->prepare("SELECT * FROM website_settings WHERE user_id = :uid LIMIT 1");
$stmt->execute([":uid" => $userId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

// If not found, create base
if (!$existing) {
    $existing = [
        "hero_title" => "",
        "hero_description" => "",
        "hero_image" => "",
        "nav_links" => "",
    ];
}

// Merge homepage fields → KEEP OLD VALUES
$hero_title = $existing["hero_title"];
$hero_description = $existing["hero_description"];
$hero_image = $existing["hero_image"];

// Merge ONLY nav links from frontend
$nav_links = isset($data["navLinks"])
    ? json_encode($data["navLinks"])
    : $existing["nav_links"];

// Insert or update (this preserves ALL fields)
$stmt = $pdo->prepare("
    INSERT INTO website_settings (user_id, hero_title, hero_description, hero_image, nav_links)
    VALUES (:uid, :title, :description, :image, :links)
    ON DUPLICATE KEY UPDATE
        hero_title = :title,
        hero_description = :description,
        hero_image = :image,
        nav_links = :links
");

$stmt->execute([
    ":uid" => $userId,
    ":title" => $hero_title,
    ":description" => $hero_description,
    ":image" => $hero_image,
    ":links" => $nav_links
]);

echo json_encode([
    "success" => true,
    "message" => "Header settings updated successfully"
]);
