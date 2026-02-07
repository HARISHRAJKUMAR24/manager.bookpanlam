<?php
// seller/settings/qr-preview/get.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit();
}

// Get user ID from query parameters
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required"
    ]);
    exit();
}

try {
    // Get user details and site settings - FIXED COLUMN NAMES
    $sql = "
        SELECT 
            u.name,
            u.site_slug,
            s.phone,
            s.whatsapp,
            s.email,
            s.address
        FROM users u
        LEFT JOIN site_settings s ON u.user_id = s.user_id
        WHERE u.user_id = :user_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        exit();
    }

    // Format the data
    $formattedData = [
        'name' => $data['name'] ?? '',
        'siteSlug' => $data['site_slug'] ?? '',
        'phone' => $data['phone'] ?? '',
        'whatsapp' => $data['whatsapp'] ?? '',
        'email' => $data['email'] ?? '',
        'address' => $data['address'] ?? ''
    ];

    // Return success with data
    echo json_encode([
        'success' => true,
        'data' => $formattedData
    ]);

} catch (PDOException $e) {
    error_log("Database error in get.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}