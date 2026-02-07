<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$pdo = getDbConnection();

/* --------------------------------------
   READ JSON BODY
---------------------------------------*/
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) $data = [];

/* --------------------------------------
   AUTH TOKEN → USER
---------------------------------------*/
$token = $data["token"] ?? ($_COOKIE["token"] ?? "");

if (!$token) {
    echo json_encode(["success" => false, "message" => "Missing token"]);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetchObject();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit();
}

$user_id = $user->user_id;

/* --------------------------------------
   ✅ CHECK SERVICES LIMIT
---------------------------------------*/
validateResourceLimit($user_id, 'services');

/* --------------------------------------
   CATEGORY FIELDS
---------------------------------------*/
$name = trim($data["name"] ?? "");
$slug = trim($data["slug"] ?? "");

if ($name === "" || $slug === "") {
    echo json_encode(["success" => false, "message" => "Name & slug required"]);
    exit();
}

$meta_title = $data["meta_title"] ?? null;
$meta_desc  = $data["meta_description"] ?? null;

/* --------------------------------------
   DOCTOR FIELDS (INSIDE CATEGORY) WITH HSN
---------------------------------------*/
$doctor_name    = $data["doctor_name"] ?? null;
$specialization = $data["specialization"] ?? null;
$qualification  = $data["qualification"] ?? null;
$experience     = $data["experience"] ?? null;
$reg_number     = $data["reg_number"] ?? null;
$doctor_image   = $data["doctor_image"] ?? null;
$hsn_code       = $data["hsn_code"] ?? null; // ✅ NEW HSN FIELD

/* --------------------------------------
   AUTO CATEGORY CODE
---------------------------------------*/
$category_code = "CAT_" . uniqid();

/* --------------------------------------
   INSERT INTO CATEGORIES (WITH DOCTOR + HSN)
---------------------------------------*/
$sql = "INSERT INTO categories
(
    category_id,
    user_id,
    name,
    slug,
    meta_title,
    meta_description,
    doctor_name,
    specialization,
    qualification,
    experience,
    reg_number,
    doctor_image,
    hsn_code,
    created_at
)
VALUES
(
    :cid,
    :uid,
    :name,
    :slug,
    :mtitle,
    :mdesc,
    :dname,
    :spec,
    :qual,
    :exp,
    :reg,
    :img,
    :hsn,
    NOW(3)
)";

$stmt = $pdo->prepare($sql);

$ok = $stmt->execute([
    ":cid"   => $category_code,
    ":uid"   => $user_id,
    ":name"  => $name,
    ":slug"  => $slug,
    ":mtitle" => $meta_title,
    ":mdesc" => $meta_desc,
    ":dname" => $doctor_name,
    ":spec"  => $specialization,
    ":qual"  => $qualification,
    ":exp"   => $experience,
    ":reg"   => $reg_number,
    ":img"   => $doctor_image,
    ":hsn"   => $hsn_code // ✅ NEW HSN FIELD
]);

if (!$ok) {
    echo json_encode([
        "success" => false,
        "message" => "Category insert failed"
    ]);
    exit();
}

/* --------------------------------------
   SUCCESS RESPONSE
---------------------------------------*/
echo json_encode([
    "success" => true,
    "message" => "Category + Doctor + HSN saved successfully",
    "category_id" => $category_code,
    "id" => $pdo->lastInsertId()
]);
exit();
?>