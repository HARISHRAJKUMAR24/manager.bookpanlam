<?php
// seller/settings/social-settings/update.php
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

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required data
if (!isset($data['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit();
}

$user_id = $data['user_id'];

// Extract and sanitize data
$facebook = isset($data['facebook']) ? trim($data['facebook']) : null;
$twitter = isset($data['twitter']) ? trim($data['twitter']) : null;
$instagram = isset($data['instagram']) ? trim($data['instagram']) : null;
$linkedin = isset($data['linkedin']) ? trim($data['linkedin']) : null;
$youtube = isset($data['youtube']) ? trim($data['youtube']) : null;
$pinterest = isset($data['pinterest']) ? trim($data['pinterest']) : null;

// Function to format URLs (add https:// if missing)
function formatUrl($url)
{
    if ($url === null || trim($url) === '') {
        return null;
    }

    $url = trim($url);
    
    if (empty($url)) {
        return null;
    }

    // If it doesn't start with http:// or https://, add https://
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        return "https://" . $url;
    }

    return $url;
}

// Format URLs
$facebook = formatUrl($facebook);
$twitter = formatUrl($twitter);
$instagram = formatUrl($instagram);
$linkedin = formatUrl($linkedin);
$youtube = formatUrl($youtube);
$pinterest = formatUrl($pinterest);

try {
    // Use your existing database connection function
    $pdo = getDbConnection();

    // Check if record exists
    $checkSql = "SELECT COUNT(*) FROM site_settings WHERE user_id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$user_id]);
    $exists = $checkStmt->fetchColumn() > 0;

    if ($exists) {
        // Update existing record
        $sql = "UPDATE site_settings SET 
                facebook = ?, 
                twitter = ?,
                instagram = ?,
                linkedin = ?,
                youtube = ?,
                pinterest = ?
                WHERE user_id = ?";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $facebook,
            $twitter,
            $instagram,
            $linkedin,
            $youtube,
            $pinterest,
            $user_id
        ]);
    } else {
        // Insert new record
        $sql = "INSERT INTO site_settings 
                (user_id, facebook, twitter, instagram, linkedin, youtube, pinterest, currency) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'INR')";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $user_id,
            $facebook,
            $twitter,
            $instagram,
            $linkedin,
            $youtube,
            $pinterest
        ]);
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Social settings saved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save social settings'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in update.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}