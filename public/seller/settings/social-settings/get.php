<?php
// seller/settings/social-settings/get.php
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

    $sql = "SELECT facebook, twitter, instagram, linkedin, youtube, pinterest 
            FROM site_settings 
            WHERE user_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        // Don't auto-create record here, just return empty values
        // Let update.php handle record creation when user saves
        $settings = [
            'facebook' => '',
            'twitter' => '',
            'instagram' => '',
            'linkedin' => '',
            'youtube' => '',
            'pinterest' => ''
        ];
    } else {
        // Convert null values to empty strings
        foreach ($settings as $key => $value) {
            if ($value === null) {
                $settings[$key] = '';
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $settings
    ]);
} catch (PDOException $e) {
    error_log("Database error in get.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}