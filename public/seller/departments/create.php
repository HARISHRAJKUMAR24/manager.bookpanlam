<?php
// CORS
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$pdo = getDbConnection();

/* ----------------------------------------------------
   READ JSON BODY
-----------------------------------------------------*/
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) $data = [];

/* ----------------------------------------------------
   TOKEN CHECK
-----------------------------------------------------*/
$token = $data["token"] ?? ($_COOKIE["token"] ?? "");

if (!$token) {
    echo json_encode(["success" => false, "message" => "Missing token"]);
    exit();
}

/* ----------------------------------------------------
   VALIDATE USER
-----------------------------------------------------*/
$stmt = $pdo->prepare("SELECT * FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetchObject();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit();
}

$user_id = $user->user_id;

/* ----------------------------------------------------
   ✅ CHECK SERVICES LIMIT
-----------------------------------------------------*/
validateResourceLimit($user_id, 'services');

/* ----------------------------------------------------
   REQUIRED FIELD
-----------------------------------------------------*/
$name = trim($data["name"] ?? "");

if ($name === "") {
    echo json_encode(["success" => false, "message" => "Department name is required"]);
    exit();
}

/* ----------------------------------------------------
   CREATE UNIQUE DEPARTMENT ID
-----------------------------------------------------*/
$department_id = "DEPT_" . uniqid();

/* ----------------------------------------------------
   PREPARE INSERT FIELDS
-----------------------------------------------------*/
$fields = [
    "department_id"       => $department_id,
    "user_id"             => $user_id,
    "name"                => $name,
    "slug"                => trim($data["slug"] ?? ""),
    "image"               => trim($data["image"] ?? ""),
    "meta_title"          => $data["meta_title"] ?? null,
    "meta_description"    => $data["meta_description"] ?? null,
    
    // Main type with HSN
    "type_main_name"      => $data["type_main_name"] ?? null,
    "type_main_amount"    => ($data["type_main_amount"] !== "" ? floatval($data["type_main_amount"]) : null),
    "type_main_hsn"       => $data["type_main_hsn"] ?? null,
];

/* ----------------------------------------------------
   ADD 25 DYNAMIC TYPES WITH HSN
-----------------------------------------------------*/
for ($i = 1; $i <= 25; $i++) {
    $fields["type_{$i}_name"] = $data["type_{$i}_name"] ?? null;
    $fields["type_{$i}_amount"] = ($data["type_{$i}_amount"] !== "" && isset($data["type_{$i}_amount"]))
        ? floatval($data["type_{$i}_amount"])
        : null;
    $fields["type_{$i}_hsn"] = $data["type_{$i}_hsn"] ?? null;
}

/* ----------------------------------------------------
   INSERT INTO DATABASE
-----------------------------------------------------*/
$columns = array_keys($fields);
$placeholders = array_map(fn ($c) => ":$c", $columns);

$sql = "INSERT INTO departments (" . implode(", ", $columns) . ", created_at)
        VALUES (" . implode(", ", $placeholders) . ", NOW(3))";

try {
    $stmt = $pdo->prepare($sql);

    foreach ($fields as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();

} catch (Exception $e) {
    error_log("DEPT INSERT ERROR: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database insert failed",
        "error"   => $e->getMessage()
    ]);
    exit();
}

/* ----------------------------------------------------
   SAVE ADDITIONAL IMAGES
-----------------------------------------------------*/
$additionalImages = $data["additionalImages"] ?? [];

if (is_array($additionalImages) && count($additionalImages) > 0) {
    foreach ($additionalImages as $img) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO department_additional_images (department_id, user_id, image, created_at)
                VALUES (:department_id, :user_id, :image, NOW())
            ");

            $stmt->execute([
                ":department_id" => $department_id,
                ":user_id"       => $user_id,
                ":image"         => trim($img)
            ]);

        } catch (Exception $e) {
            error_log("ADDITIONAL IMG ERR: " . $e->getMessage());
        }
    }
}

/* ----------------------------------------------------
   SUCCESS RESPONSE
-----------------------------------------------------*/
echo json_encode([
    "success"        => true,
    "message"        => "Department created successfully",
    "department_id"  => $department_id
]);

exit();
?>