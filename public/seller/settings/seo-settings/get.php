<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

// User ID coming directly from cookie — REAL PRIMARY KEY
$real_user_id = $_GET["user_id"] ?? null;

if (!$real_user_id) {
    echo json_encode(["success" => false, "message" => "user_id missing"]);
    exit();
}

try {
    $sql = "SELECT 
                logo, favicon, email, phone, whatsapp, currency, 
                country, state, address,
                meta_title, meta_description, sharing_image_preview
            FROM site_settings 
            WHERE user_id = :user_id 
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":user_id" => $real_user_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        $settings = [
            "logo" => null,
            "favicon" => null,
            "email" => null,
            "phone" => null,
            "whatsapp" => null,
            "currency" => "INR",
            "country" => null,
            "state" => null,
            "address" => null,
            "meta_title" => "",
            "meta_description" => "",
            "sharing_image_preview" => ""
        ];
    }

    // Add full URLs
    $baseUrl = "http://localhost/manager.bookpanlam/public/uploads/";

    $settings["logo_url"] = $settings["logo"] ? $baseUrl . $settings["logo"] : null;
    $settings["favicon_url"] = $settings["favicon"] ? $baseUrl . $settings["favicon"] : null;
    $settings["sharing_image_preview_url"] = $settings["sharing_image_preview"]
        ? $baseUrl . $settings["sharing_image_preview"]
        : null;

    $settings["user_id"] = $real_user_id;

    echo json_encode([
        "success" => true,
        "data" => $settings
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
