<?php
// manager.bookpanlam/public/seller/settings/tax-settings/update.php
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

// Extract data with defaults
// Detect GST OFF (clear) action
$isClearAction =
    array_key_exists('gstNumber', $data) &&
    array_key_exists('gstType', $data) &&
    array_key_exists('taxPercent', $data) &&
    $data['gstNumber'] === null &&
    $data['gstType'] === null &&
    $data['taxPercent'] === null;

if ($isClearAction) {
    // GST OFF → store NULL in DB
    $gst_number  = null;
    $gst_type    = null;
    $tax_percent = null;
    $country     = null;
    $state       = null;
} else {
    // GST ON → validate & save
    $gst_number  = trim($data['gstNumber'] ?? '');
    $gst_type    = trim($data['gstType'] ?? '');
    $tax_percent = isset($data['taxPercent']) && $data['taxPercent'] !== '' ? $data['taxPercent'] : null;
    $country     = trim($data['country'] ?? '');
    $state       = trim($data['state'] ?? '');

    // Validate required fields
    if (empty($gst_number)) {
        echo json_encode(['success' => false, 'message' => 'GST Number is required']);
        exit();
    }

    if (empty($gst_type)) {
        echo json_encode(['success' => false, 'message' => 'GST Type is required']);
        exit();
    }

    if ($tax_percent === null || $tax_percent === '') {
        echo json_encode(['success' => false, 'message' => 'Tax Percentage is required']);
        exit();
    }

    $tax_percent = (float) $tax_percent;
}

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
                gst_number = ?, 
                gst_type = ?,
                tax_percent = ?,
                country = ?,
                state = ?
                WHERE user_id = ?";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $gst_number,
            $gst_type,
            $tax_percent,
            $country,
            $state,
            $user_id
        ]);
    } else {
        // Insert new record
        $sql = "INSERT INTO site_settings 
                (user_id, gst_number, gst_type, tax_percent, country, state, currency) 
                VALUES (?, ?, ?, ?, ?, ?, 'INR')";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $user_id,
            $gst_number,
            $gst_type,
            $tax_percent,
            $country,
            $state
        ]);
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $isClearAction ? 'Tax settings cleared successfully' : 'Tax settings saved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save tax settings'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in tax update.php: " . $e->getMessage());

    // Check for duplicate GST number
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode([
            'success' => false,
            'message' => 'This GST number is already registered by another user'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit();
}