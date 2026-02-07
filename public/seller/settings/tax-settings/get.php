<?php
// manager.bookpanlam/public/seller/settings/tax-settings/get.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include your config file
require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

// Get user ID from query parameters
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit();
}

try {
    // Use your existing database connection function
    $pdo = getDbConnection();

    $sql = "SELECT gst_number, gst_type, tax_percent, country, state 
            FROM site_settings 
            WHERE user_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        $settings = [
            'gst_number' => '',
            'gst_type' => '',
            'tax_percent' => null,
            'country' => '',
            'state' => ''
        ];
    } else {
        // Ensure proper types
        $settings['tax_percent'] = $settings['tax_percent'] !== null ? (float) $settings['tax_percent'] : null;
        
        // Convert other null values to empty strings
        foreach ($settings as $key => $value) {
            if ($value === null && $key !== 'tax_percent') {
                $settings[$key] = '';
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $settings
    ]);
} catch (PDOException $e) {
    error_log("Database error in tax get.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}