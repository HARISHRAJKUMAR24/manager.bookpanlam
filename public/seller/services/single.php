<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$service_id_param = $_GET["service_id"] ?? null;

if (!$service_id_param) {
    echo json_encode(["success" => false, "message" => "service_id missing"]);
    exit();
}

try {

    // -----------------------------------------
    // Fetch service (return ONLY raw relative path!)
    // -----------------------------------------
    $stmt = $pdo->prepare("
        SELECT * FROM  appointment_settings
        WHERE service_id = :sid
    ");
    $stmt->execute([":sid" => $service_id_param]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        echo json_encode(["success" => false, "message" => "Service not found"]);
        exit();
    }

    // Force image to ALWAYS start with /
    $imagePath = $service["image"];
    if ($imagePath && $imagePath[0] !== "/") {
        $imagePath = "/" . $imagePath;
    }

    // ----------------------------------------------------
    // Format serviceData (NO full URLs!!!)
    // ----------------------------------------------------
    $serviceData = [
        "id"                => $service["id"],
        "service_id"        => $service["service_id"],
        "user_id"           => $service["user_id"],
        "name"              => $service["name"],
        "slug"              => $service["slug"],
        "amount"            => $service["amount"],
        "previousAmount"    => $service["previous_amount"],
        "categoryId"        => $service["category_id"],
        "timeSlotInterval"  => $service["time_slot_interval"],
        "intervalType"      => $service["interval_type"],
        "description"       => $service["description"],
        "gstPercentage"     => $service["gst_percentage"],
        "metaTitle"         => $service["meta_title"],
        "metaDescription"   => $service["meta_description"],
        "status"            => $service["status"],
        "image"             => $imagePath, // RAW DB PATH ONLY
    ];

    // ----------------------------------------------------
    // Get additional images (ALSO only raw paths)
    // ----------------------------------------------------
    $imgStmt = $pdo->prepare("
        SELECT image FROM service_images
        WHERE service_id = :sid
        ORDER BY id
    ");
    $imgStmt->execute([":sid" => $service["id"]]);

    $rawImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // Normalize: Always start with "/"
    $serviceData["additionalImages"] = array_map(function($img) {
        if ($img && $img[0] !== "/") {
            $img = "/" . $img;
        }
        return ["image" => $img];
    }, $rawImages);

    // ----------------------------------------------------
    // Final response
    // ----------------------------------------------------
    echo json_encode([
        "success" => true,
        "data" => $serviceData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
