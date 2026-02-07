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

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";
require_once "../../../../src/auth.php";

$pdo = getDbConnection();

/* ================= AUTH ================= */

$user = getAuthenticatedUser($pdo);

// IMPORTANT: use public user_id
$user_id = $user['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ================= INPUT ================= */

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON input"
    ]);
    exit;
}

$meta_title             = $data["meta_title"] ?? "";
$meta_description       = $data["meta_description"] ?? "";
$sharing_image_preview  = $data["sharing_image_preview"] ?? null;

/* ================= UPSERT ================= */

try {
    // Check if settings row already exists
    $check = $pdo->prepare(
        "SELECT id FROM site_settings WHERE user_id = ? LIMIT 1"
    );
    $check->execute([$user_id]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE site_settings SET
                meta_title = ?,
                meta_description = ?,
                sharing_image_preview = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $meta_title,
            $meta_description,
            $sharing_image_preview,
            $user_id
        ]);
    } else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO site_settings
                (user_id, meta_title, meta_description, sharing_image_preview)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $meta_title,
            $meta_description,
            $sharing_image_preview
        ]);
    }

    echo json_encode([
        "success" => true,
        "message" => "SEO settings updated successfully"
    ]);
} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $e->getMessage()
    ]);
}
