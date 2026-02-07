<?php
require_once "../../../config/config.php";
require_once "../../../src/database.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$user_id = $_GET["user_id"] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User ID missing"]);
    exit();
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT * FROM website_settings WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(["success" => false, "message" => "No settings found"]);
        exit();
    }

    // Convert banners JSON string → Array
    $data["banners"] = json_decode($data["banners"], true);

    echo json_encode(["success" => true, "data" => $data]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}
