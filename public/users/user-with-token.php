<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

require_once "../../config/config.php";
require_once "../../src/database.php";

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

$token = $input["token"] ?? "";

if (!$token) {
    echo json_encode(["success" => false, "message" => "Missing token"]);
    exit;
}

$pdo = getDbConnection();

// Fetch user by token
$stmt = $pdo->prepare("SELECT * FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetchObject();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

// Fetch site settings (logo, favicon, etc)
$st = $pdo->prepare("SELECT * FROM site_settings WHERE user_id = :id LIMIT 1");
$st->execute([":id" => $user->user_id]);
$siteSettings = $st->fetch(PDO::FETCH_ASSOC);

$baseUrl = "http://localhost/manager.bookpanlam/public/uploads/";

if ($siteSettings) {
    $siteSettings["logo_url"] = $siteSettings["logo"]
        ? $baseUrl . $siteSettings["logo"]
        : null;

    $siteSettings["favicon_url"] = $siteSettings["favicon"]
        ? $baseUrl . $siteSettings["favicon"]
        : null;

    $siteSettings["sharing_image_preview_url"] = $siteSettings["sharing_image_preview"]
        ? $baseUrl . $siteSettings["sharing_image_preview"]
        : null;
}

// 👉 FUNCTION TO GET SUSPENSION REASON
function getSuspendReason($pdo, $user_id) {
    $q = $pdo->prepare("
        SELECT reason 
        FROM suspend_users 
        WHERE user_id = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $q->execute([$user_id]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    return $row["reason"] ?? "";
}


echo json_encode([
    "success" => true,
    "data" => [
        "id"        => (int)$user->id,
        "user_id"   => (int)$user->user_id,

        "name"      => $user->name,
        "email"     => $user->email,
        "phone"     => $user->phone,
        "country"   => $user->country,
        "image"     => $user->image,

     "site_slug"  => $user->site_slug,
"site_name"  => $user->site_name,


        "service_type_id" => (int)$user->service_type_id,

        // ⭐ ADD THESE LINES ⭐
        "is_suspended" => (int)$user->is_suspended,
        "suspension_reason" => $user->is_suspended ? getSuspendReason($pdo, $user->user_id) : null,

"siteSettings" => $siteSettings ? $siteSettings : new stdClass()
    ]
]);
