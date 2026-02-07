<?php
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

    // ✅ CORRECTED: Removed 'image' field, added full URL for icon
    $stmt = $pdo->prepare(
        "SELECT id, name, icon, instructions, upi_id, created_at
         FROM manual_payment_methods 
         WHERE user_id = ?
         ORDER BY id DESC"
    );

    $stmt->execute([$user_id]);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Base URL for converting relative paths to full URLs
    $baseUrl = "http://localhost/manager.bookpanlam/public/";
    
    foreach ($methods as &$method) {
        // Convert empty values to empty strings
        foreach ($method as $key => $value) {
            if ($value === null) {
                $method[$key] = "";
            }
        }
        
        // Convert icon path to full URL if exists
        if (!empty($method['icon']) && strpos($method['icon'], 'http') !== 0) {
            // Remove any duplicate "uploads/" prefix
            $cleanIcon = preg_replace('/^uploads\//', '', $method['icon']);
            $method['icon'] = $baseUrl . 'uploads/' . $cleanIcon;
        }
        
        // Debug log (optional)
        error_log("Method: " . $method['name'] . ", UPI: " . $method['upi_id'] . ", Icon: " . $method['icon']);
    }

    // Debug output
    error_log("Total methods found: " . count($methods));
    error_log("Methods data: " . print_r($methods, true));

    echo json_encode([
        "success" => true,
        "data" => $methods
    ]);

} catch (Exception $e) {
    error_log("Database error in get-manual-payments.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}