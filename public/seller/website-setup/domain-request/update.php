<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);

$userId = $data["user_id"] ?? null;
$customDomain = $data["customDomain"] ?? null;
$domainRequest = $data["domainRequest"] ?? null;

if (!$userId) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit;
}

if (!$customDomain || !$domainRequest) {
    echo json_encode(["success" => false, "message" => "Missing domain data"]);
    exit;
}

try {
    // Check if record exists
    $stmt = $pdo->prepare("SELECT id FROM website_settings WHERE user_id = :uid LIMIT 1");
    $stmt->execute([":uid" => $userId]);
    $exists = $stmt->fetch();

    // Encode domain request as JSON
    $domainRequestJson = json_encode($domainRequest);

    if ($exists) {
        // Update existing record - only update domain fields, preserve others
        $stmt = $pdo->prepare("
            UPDATE website_settings 
            SET custom_domain = :domain,
                domain_request = :request
            WHERE user_id = :uid
        ");
        
        $stmt->execute([
            ":domain" => $customDomain,
            ":request" => $domainRequestJson,
            ":uid" => $userId
        ]);
    } else {
        // Insert new record with default values for other fields
        $stmt = $pdo->prepare("
            INSERT INTO website_settings (
                user_id, 
                custom_domain, 
                domain_request,
                hero_title,
                hero_description,
                hero_image,
                banners,
                nav_links
            ) VALUES (
                :uid, 
                :domain, 
                :request,
                '',
                '',
                '',
                '[]',
                NULL
            )
        ");
        
        $stmt->execute([
            ":uid" => $userId,
            ":domain" => $customDomain,
            ":request" => $domainRequestJson
        ]);
    }

    echo json_encode([
        "success" => true,
        "message" => "Domain request submitted successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}