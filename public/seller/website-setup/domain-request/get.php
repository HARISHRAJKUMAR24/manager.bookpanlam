<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

$userId = $_GET["user_id"] ?? null;

if (!$userId) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit;
}

try {
    // Get domain request data
    $stmt = $pdo->prepare("
        SELECT custom_domain, domain_request 
        FROM website_settings 
        WHERE user_id = :uid 
        LIMIT 1
    ");
    $stmt->execute([":uid" => $userId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        // Return empty/default data if no record exists
        echo json_encode([
            "success" => true,
            "data" => [
                "customDomain" => "",
                "domainRequest" => [
                    "domain" => "",
                    "status" => null,
                    "requestedAt" => null
                ]
            ]
        ]);
        exit;
    }

    // Parse domain_request JSON
    $domainRequest = !empty($existing["domain_request"]) 
        ? json_decode($existing["domain_request"], true) 
        : ["domain" => "", "status" => null, "requestedAt" => null];

    echo json_encode([
        "success" => true,
        "data" => [
            "customDomain" => $existing["custom_domain"] ?? "",
            "domainRequest" => $domainRequest
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage()
    ]);
}