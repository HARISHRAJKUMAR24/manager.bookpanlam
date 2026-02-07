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

$currency = $_POST['currency'] ?? '';

// Validate inputs
if (empty($currency)) {
    exit(json_encode(["type" => "error", "msg" => "Currency is required"]));
}

// Update settings - only update currency, not app_name
$stmt = $pdo->prepare("UPDATE settings SET currency = ? WHERE id = 1");
if ($stmt->execute([$currency])) {
    exit(json_encode(["type" => "success", "msg" => "Currency settings updated successfully!"]));
} else {
    exit(json_encode(["type" => "error", "msg" => "Failed to update settings"]));
}
?>