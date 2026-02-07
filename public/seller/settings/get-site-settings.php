<?php
// manager.bookpanlam/public/seller/settings/get-site-settings.php

$allowed_origins = [
    "http://localhost:3000",
    "http://localhost:3001",
    "http://127.0.0.1:3000",
    "http://127.0.0.1:3001"
];

$origin = $_SERVER["HTTP_ORIGIN"] ?? "";

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:3001");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$user_id = $_GET["user_id"] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID is required"
    ]);
    exit();
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT * FROM site_settings WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);

    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        echo json_encode([
            "success" => false,
            "message" => "Site settings not found"
        ]);
        exit();
    }

    // Convert database types
    $settings["cash_in_hand"] = intval($settings["cash_in_hand"] ?? 0);
    
    // ✅ ADD: Check if payment methods should be shown based on plan limits
    $settings["show_cash_in_hand"] = ($settings["cash_in_hand"] == 1) && canShowCOH($user_id);
    $settings["show_upi_payments"] = canShowUPI($user_id); // ✅ NEW: Check UPI limit
    
    foreach ($settings as $k => $v) {
        if ($v === null) $settings[$k] = "";
    }

    echo json_encode([
        "success" => true,
        "data" => $settings
    ]);

} catch (Exception $e) {
    error_log("Site settings error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}