<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

/* ===============================
   HEADERS / CORS
================================ */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   READ INPUT
================================ */
$raw   = file_get_contents("php://input");
$input = json_decode($raw, true) ?? [];

/* ===============================
   AUTH
================================ */
$token = $input["token"] ?? ($_COOKIE["token"] ?? "");

if (!$token) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = (int)$user->user_id;

/* ===============================
   VALIDATION
================================ */
if (empty($input['id'])) {
    echo json_encode(["success" => false, "message" => "Item ID missing"]);
    exit;
}

$itemId = (int)$input['id'];
unset($input['id']);

/* ===============================
   OWNERSHIP CHECK
================================ */
$check = $pdo->prepare("SELECT image FROM menu_items WHERE id = ? AND user_id = ?");
$check->execute([$itemId, $user_id]);
$existing = $check->fetch(PDO::FETCH_ASSOC);

if (!$existing) {
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

$oldImage = $existing['image'];

/* ===============================
   HANDLE IMAGE DELETE
================================ */
if (!empty($input['remove_image']) && !empty($input['old_image'])) {
    $path = dirname(__DIR__, 3) . "/public" . $input['old_image'];
    if (file_exists($path)) unlink($path);

    $input['image'] = null;
}

/* ===============================
   HANDLE NEW IMAGE UPLOAD
================================ */
if (!empty($input["new_image"]) && !empty($input["new_image"]["data"])) {

    /* delete old */
    if ($oldImage) {
        $oldPath = dirname(__DIR__, 3) . "/public" . $oldImage;
        if (file_exists($oldPath)) unlink($oldPath);
    }

    /* Create folder */
    $year = date("Y");
    $month = date("m");
    $day = date("d");

    $uploadDir = dirname(__DIR__, 3) . "/public/uploads/sellers/$user_id/menu-items/$year/$month/$day/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    /* Save new file */
    $imageData = $input["new_image"]["data"];
    $ext = $input["new_image"]["ext"] ?? "png";

    $fileName = "item_" . $itemId . "_" . time() . ".$ext";
    $fullPath = $uploadDir . $fileName;

    file_put_contents($fullPath, base64_decode($imageData));

    $input["image"] = "/uploads/sellers/$user_id/menu-items/$year/$month/$day/$fileName";
}

/* Remove helper fields */
unset($input["remove_image"]);
unset($input["old_image"]);
unset($input["new_image"]);

/* ===============================
   NORMALIZE FIELDS
================================ */
if (isset($input['food_type'])) {
    $input['food_type'] = $input['food_type'] === 'non-veg' ? 'nonveg' : 'veg';
}

if (isset($input['stock_type']) && $input['stock_type'] === 'out_of_stock') {
    $input['stock_type'] = 'out';
}

if (isset($input['prebooking_enabled'])) {
    $input['prebooking_enabled'] = $input['prebooking_enabled'] ? 1 : 0;
}

/* ===============================
   UPDATE FIELDS
================================ */
$allowed = [
    "name","description","hsn_code","menu_id","category_id",
    "food_type","stock_type","halal","stock_qty","stock_unit",
    "customer_limit","customer_limit_period",
    "prebooking_enabled","prebooking_min_amount","prebooking_max_amount",
    "prebooking_advance_days","image"
];

$fields = [];
$values = [];

foreach ($input as $key => $value) {
    if (!in_array($key, $allowed)) continue;

    if ($key === "halal") {
        $fields[] = "halal = ?";
        $values[] = $value ? 1 : 0;
        continue;
    }

    $fields[] = "$key = ?";
    $values[] = $value;
}

/* ===============================
   EXECUTE
================================ */
if ($fields) {
    $values[] = $itemId;
    $sql = "UPDATE menu_items SET " . implode(", ", $fields) . " WHERE id = ?";
    $pdo->prepare($sql)->execute($values);
}

echo json_encode([
    "success" => true,
    "message" => "Menu item updated (image handled correctly)"
]);
exit;
