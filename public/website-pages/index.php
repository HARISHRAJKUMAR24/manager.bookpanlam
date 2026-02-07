<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

require_once "../../src/database.php";

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    $user_id = $data["user_id"] ?? null;
    $name    = $data["name"] ?? "";
    $slug    = $data["slug"] ?? "";
    $content = $data["content"] ?? "";
    $page_id = $data["page_id"] ?? uniqid(); // auto generate

    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "user_id is required"]);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO website_pages (user_id, name, slug, content, page_id) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issss", $user_id, $name, $slug, $content, $page_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Page created successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
