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

$user_id = $_GET["user_id"] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "user_id missing"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data["name"]) || empty($data["slug"]) || empty($data["amount"])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

$service_id_string = "SRV_" . uniqid();

$pdo->beginTransaction();

try {
    $sql = "INSERT INTO  appointment_settings 
            (service_id, user_id, name, slug, amount, previous_amount, image, category_id,
            time_slot_interval, interval_type, description, gst_percentage, 
            meta_title, meta_description, status, created_at)
            VALUES 
            (:sid, :uid, :name, :slug, :amount, :pamount, :image, :cat, :slot, :itype, 
            :descr, :gst, :mtitle, :mdesc, :status, NOW(3))";

    $stmt = $pdo->prepare($sql);

    $result = $stmt->execute([
        ":sid" => $service_id_string,
        ":uid" => $user_id,
        ":name" => $data["name"],
        ":slug" => $data["slug"],
        ":amount" => $data["amount"],
        ":pamount" => $data["previousAmount"] ?? null,
        ":image" => $data["image"] ?? null,
        ":cat" => $data["categoryId"] ?? null,
        ":slot" => $data["timeSlotInterval"] ?? null,
        ":itype" => $data["intervalType"] ?? null,
        ":descr" => $data["description"] ?? null,
        ":gst" => $data["gstPercentage"] ?? null,
        ":mtitle" => $data["metaTitle"] ?? null,
        ":mdesc" => $data["metaDescription"] ?? null,
        ":status" => $data["status"] ?? 0
    ]);

    if (!$result) {
        throw new Exception("Failed to insert service");
    }
    
    $numeric_service_id = $pdo->lastInsertId();

    // Insert additional images
    if (isset($data["additionalImages"]) && is_array($data["additionalImages"]) && !empty($data["additionalImages"])) {
        $insertStmt = $pdo->prepare("INSERT INTO service_images (service_id, image, created_at) VALUES (:service_id, :img, NOW(3))");
        
        foreach ($data["additionalImages"] as $imgPath) {
            if (!empty($imgPath)) {
                $insertStmt->execute([
                    ":service_id" => $numeric_service_id,
                    ":img" => $imgPath
                ]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Service created successfully",
        "service_id" => $service_id_string
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>