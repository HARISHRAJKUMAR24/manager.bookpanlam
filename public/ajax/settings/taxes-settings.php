<?php
require_once '../../../src/database.php';
require_once '../../../src/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    exit(json_encode(["type" => "error", "msg" => "Please login first"]));
}
if (!isAdmin()) {
    exit(json_encode(["type" => "error", "msg" => "Access denied"]));
}

// Get database connection
$pdo = getDbConnection();

$gst_enabled = isset($_POST['gst_enabled']) ? 1 : 0;
$gst_tax_type = $_POST['gst_tax_type'] ?? '';
$gst_number = $_POST['gst_number'] ?? '';
$gst_percentage = $_POST['gst_percentage'] ?? '';

// If GST is disabled, clear the GST fields
if (!$gst_enabled) {
    $gst_tax_type = NULL;
    $gst_number = NULL;
    $gst_percentage = 0;
}

// Validate inputs when GST is enabled
if ($gst_enabled) {
    if (empty($gst_tax_type)) {
        exit(json_encode(["type" => "error", "msg" => "Tax type is required"]));
    }
    if (empty($gst_percentage)) {
        exit(json_encode(["type" => "error", "msg" => "GST percentage is required"]));
    }
    if (!is_numeric($gst_percentage) || $gst_percentage < 0 || $gst_percentage > 100) {
        exit(json_encode(["type" => "error", "msg" => "GST percentage must be a valid number between 0 and 100"]));
    }
}

// Update settings in the existing settings table
$stmt = $pdo->prepare("UPDATE settings SET gst_tax_type = ?, gst_number = ?, gst_percentage = ? WHERE id = 1");
if ($stmt->execute([$gst_tax_type, $gst_number, $gst_percentage])) {
    exit(json_encode(["type" => "success", "msg" => "Tax settings updated successfully!"]));
} else {
    exit(json_encode(["type" => "error", "msg" => "Failed to update tax settings"]));
}
?>