<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$service_uid = $_GET["service_id"] ?? null;

if (!$service_uid) {
    echo json_encode(["success" => false, "message" => "service_id missing"]);
    exit();
}

// 1️⃣ Fetch numeric ID + image paths before deleting
$stmt = $pdo->prepare("SELECT id, image FROM  appointment_settings WHERE service_id = ?");
$stmt->execute([$service_uid]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    echo json_encode(["success" => false, "message" => "Service not found"]);
    exit();
}

$service_numeric_id = $service["id"];
$main_image = $service["image"];

// Fetch additional images
$imgStmt = $pdo->prepare("SELECT image FROM service_images WHERE service_id = ?");
$imgStmt->execute([$service_numeric_id]);
$additional_images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

$basePath = __DIR__ . "/../../../public/uploads/";

// 2️⃣ Delete main image file
if ($main_image && file_exists($basePath . $main_image)) {
    unlink($basePath . $main_image);
}

// 3️⃣ Delete additional image files
foreach ($additional_images as $img) {
    if (!empty($img) && file_exists($basePath . $img)) {
        unlink($basePath . $img);
    }
}

// 4️⃣ Delete DB records
$pdo->prepare("DELETE FROM service_images WHERE service_id = ?")
    ->execute([$service_numeric_id]);

$pdo->prepare("DELETE FROM services WHERE service_id = ?")
    ->execute([$service_uid]);

echo json_encode([
    "success" => true,
    "message" => "Service deleted successfully"
]);
?>
