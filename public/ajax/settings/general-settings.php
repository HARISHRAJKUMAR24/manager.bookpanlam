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

$name = $_POST['name'] ?? '';
$app_name = $_POST['app_name'] ?? '';
$existing_image = $_POST['existing_image'] ?? '';
$old_name = $_POST['old_name'] ?? '';
$address = $_POST['address'] ?? '';
$disclaimer = $_POST['disclaimer'] ?? '';
$timezone = $_POST['timezone'] ?? 'Asia/Kolkata'; // Add this line

// Get current admin data to compare changes
$current_admin = $pdo->query("SELECT * FROM managers WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_OBJ);

// Get current settings
$current_settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_OBJ);

// Track changes
$changes = [];

// Handle admin image upload
$image = $existing_image;
$image_changed = false;
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $uploadResult = uploadImage($_FILES['image'], 'managers');
    if ($uploadResult['success']) {
        $image = $uploadResult['file_name'];
        $image_changed = true;
        // Delete old image if it exists and is not the default
        if (!empty($existing_image) && $existing_image !== 'static/user.png') {
            $oldImagePath = __DIR__ . '/../../../uploads/' . $existing_image;
            @unlink($oldImagePath);
        }
    } else {
        exit(json_encode(["type" => "error", "msg" => $uploadResult['error'] ?? "Image upload failed"]));
    }
}

// Handle logo upload
$logo = $current_settings->logo ?? '';
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
    $uploadResult = uploadImage($_FILES['logo'], 'settings');
    if ($uploadResult['success']) {
        $logo = $uploadResult['file_name'];
        // Delete old logo if it exists
        if (!empty($current_settings->logo)) {
            $oldLogoPath = __DIR__ . '/../../../uploads/' . $current_settings->logo;
            @unlink($oldLogoPath);
        }
    } else {
        exit(json_encode(["type" => "error", "msg" => $uploadResult['error'] ?? "Logo upload failed"]));
    }
}

// Handle favicon upload
$favicon = $current_settings->favicon ?? '';
if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
    $uploadResult = uploadImage($_FILES['favicon'], 'settings');
    if ($uploadResult['success']) {
        $favicon = $uploadResult['file_name'];
        // Delete old favicon if it exists
        if (!empty($current_settings->favicon)) {
            $oldFaviconPath = __DIR__ . '/../../../uploads/' . $current_settings->favicon;
            @unlink($oldFaviconPath);
        }
    } else {
        exit(json_encode(["type" => "error", "msg" => $uploadResult['error'] ?? "Favicon upload failed"]));
    }
}

// Check if timezone changed
$timezone_changed = ($timezone !== ($current_settings->timezone ?? 'Asia/Kolkata'));

// Check if name changed
$name_changed = ($name !== $old_name);

// Check if app settings changed
$app_name_changed = ($app_name !== $current_settings->app_name);
$address_changed = ($address !== ($current_settings->address ?? ''));
$disclaimer_changed = ($disclaimer !== ($current_settings->disclaimer ?? ''));

// Validate required fields
if (empty($name)) {
    exit(json_encode(["type" => "error", "msg" => "Name is required"]));
}
if (empty($app_name)) {
    exit(json_encode(["type" => "error", "msg" => "App name is required"]));
}

// Update manager data only if there are changes
if ($image_changed || $name_changed) {
    $stmt = $pdo->prepare("UPDATE managers SET name = ?, image = ? WHERE role = 'admin'");
    if (!$stmt->execute([$name, $image])) {
        exit(json_encode(["type" => "error", "msg" => "Failed to update admin profile"]));
    }
}

// Update settings data - ADD timezone to the query
$stmt = $pdo->prepare("UPDATE settings SET app_name = ?, logo = ?, favicon = ?, address = ?, disclaimer = ?, timezone = ? WHERE id = 1");
if ($stmt->execute([$app_name, $logo, $favicon, $address, $disclaimer, $timezone])) {
    exit(json_encode(["type" => "success", "msg" => "Settings updated successfully!"]));
} else {
    exit(json_encode(["type" => "error", "msg" => "Failed to update settings"]));
}
?>