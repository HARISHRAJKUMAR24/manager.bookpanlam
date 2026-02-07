<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();
$user_id = $_GET["user_id"] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "user_id missing"]);
    exit();
}

$baseUrl = "http://localhost/manager.bookpanlam/public/uploads/";

try {
    // Get all services for the user
    $sql = "SELECT s.*, 
            CASE WHEN s.image IS NULL OR s.image = '' THEN NULL 
                 ELSE CONCAT('$baseUrl', s.image) END AS image
            FROM  appointment_settings s
            WHERE s.user_id = :uid 
            ORDER BY s.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":uid" => $user_id]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each service, fetch additional images
    foreach ($services as &$service) {
        // Get additional images for this service
        $imagesStmt = $pdo->prepare("
            SELECT 
            CASE WHEN image IS NULL OR image = '' THEN NULL 
                 ELSE CONCAT('$baseUrl', image) END AS image
            FROM service_images 
            WHERE service_id = :service_id 
            ORDER BY id
        ");
        $imagesStmt->execute([":service_id" => $service['id']]);
        $additionalImages = $imagesStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Add additional images to service data
        $service['additionalImages'] = $additionalImages ?: [];
    }
    
    unset($service);

    echo json_encode([
        "success" => true,
        "records" => $services
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>