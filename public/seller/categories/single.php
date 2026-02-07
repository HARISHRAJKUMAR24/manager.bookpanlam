<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* -----------------------------------------
   INPUT
----------------------------------------- */
$category_id = $_GET['category_id'] ?? '';

if (!$category_id) {
    echo json_encode([
        "success" => false,
        "message" => "category_id missing"
    ]);
    exit();
}

$baseImageUrl = "http://localhost/manager.bookpanlam/public/uploads/";

/* -----------------------------------------
   TOKEN AUTHENTICATION
----------------------------------------- */
$token = $_GET['token'] ?? ($_COOKIE['token'] ?? '');
$user_id = $_GET['user_id'] ?? '';

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "Authentication required"
    ]);
    exit();
}

// Verify token
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetchObject();

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit();
}

/* -----------------------------------------
   FETCH CATEGORY + DOCTOR + HSN FIELDS
----------------------------------------- */
$sql = "SELECT
            id,
            category_id,
            user_id,
            name,
            slug,
            meta_title,
            meta_description,

            -- doctor fields inside categories
            doctor_name,
            specialization,
            qualification,
            experience,
            reg_number,
            doctor_image,
            
            -- ✅ NEW HSN FIELD
            hsn_code,

            created_at
        FROM categories
        WHERE category_id = :cid
        AND user_id = :uid
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':cid' => $category_id,
    ':uid' => $user->user_id
]);

$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    echo json_encode([
        "success" => false,
        "message" => "Category not found or unauthorized"
    ]);
    exit();
}

/* -----------------------------------------
   IMAGE URL FIX
----------------------------------------- */
if (!empty($category['doctor_image'])) {
    $category['doctor_image'] = $baseImageUrl . $category['doctor_image'];
}

/* -----------------------------------------
   RESPONSE FORMAT
----------------------------------------- */
echo json_encode([
    "success" => true,
    "data" => [
        "id" => $category["id"],
        "category_id" => $category["category_id"],
        "user_id" => $category["user_id"],
        "name" => $category["name"],
        "slug" => $category["slug"],
        "meta_title" => $category["meta_title"],
        "meta_description" => $category["meta_description"],
        "created_at" => $category["created_at"],

        // Direct doctor fields (for compatibility)
        "doctor_name"    => $category["doctor_name"],
        "specialization" => $category["specialization"],
        "qualification"  => $category["qualification"],
        "experience"     => $category["experience"],
        "reg_number"     => $category["reg_number"],
        "doctor_image"   => $category["doctor_image"],
        "hsn_code"       => $category["hsn_code"], // ✅ NEW HSN FIELD

        // Nested doctor_details (for your frontend structure)
        "doctor_details" => [
            "doctor_name"    => $category["doctor_name"],
            "specialization" => $category["specialization"],
            "qualification"  => $category["qualification"],
            "experience"     => $category["experience"],
            "reg_number"     => $category["reg_number"],
            "doctor_image"   => $category["doctor_image"],
            "hsn_code"       => $category["hsn_code"] // ✅ NEW HSN FIELD
        ]
    ]
]);
exit();
?>