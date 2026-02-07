<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* --------------------------------------
   CATEGORY NUMERIC ID
--------------------------------------*/
$catId = $_GET["id"] ?? null;

if (!$catId) {
    echo json_encode([
        "success" => false,
        "message" => "Category numeric id required"
    ]);
    exit();
}

/* --------------------------------------
   READ JSON BODY
--------------------------------------*/
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit();
}

/* --------------------------------------
   REMOVE NOT ALLOWED FIELDS
--------------------------------------*/
unset($data["token"]);
unset($data["created_at"]);

/* --------------------------------------
   ALLOWED CATEGORY + DOCTOR + HSN FIELDS
--------------------------------------*/
$allowedFields = [
    "name",
    "slug",
    "meta_title",
    "meta_description",

    // doctor fields inside category
    "doctor_name",
    "specialization",
    "qualification",
    "experience",
    "reg_number",
    "doctor_image",
    
    // ✅ NEW HSN FIELD
    "hsn_code"
];

/* --------------------------------------
   BUILD UPDATE QUERY
--------------------------------------*/
$fields = [];
$params = [];

foreach ($data as $key => $value) {
    if (in_array($key, $allowedFields)) {
        $fields[] = "`$key` = ?";
        $params[] = $value;
    }
}

if (empty($fields)) {
    echo json_encode([
        "success" => false,
        "message" => "No valid fields to update"
    ]);
    exit();
}

$params[] = $catId;

/* --------------------------------------
   UPDATE CATEGORY TABLE
--------------------------------------*/
$sql = "UPDATE categories 
        SET " . implode(", ", $fields) . "
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

/* --------------------------------------
   SUCCESS RESPONSE
--------------------------------------*/
echo json_encode([
    "success" => true,
    "message" => "Category, Doctor & HSN details updated successfully"
]);
exit();
?>