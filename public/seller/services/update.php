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

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$user_id = $_GET["user_id"] ?? null;
$service_id = $_GET["service_id"] ?? null;

if (!$user_id || !$service_id) {
    echo json_encode(["success" => false, "message" => "Missing user_id or service_id"]);
    exit();
}

$name = $_POST["name"] ?? "";
$slug = $_POST["slug"] ?? "";
$amount = $_POST["amount"] ?? "";
$previousAmount = $_POST["previousAmount"] ?? null;
$categoryId = $_POST["categoryId"] ?? null;
$timeSlotInterval = $_POST["timeSlotInterval"] ?? "";
$intervalType = $_POST["intervalType"] ?? "";
$description = $_POST["description"] ?? "";
$gstPercentage = isset($_POST["gstPercentage"]) && $_POST["gstPercentage"] !== ""
    ? (int)$_POST["gstPercentage"]
    : null;
$metaTitle = $_POST["metaTitle"] ?? "";
$metaDescription = $_POST["metaDescription"] ?? "";
$status = isset($_POST["status"]) && $_POST["status"] == "1" ? 1 : 0;

/**********************************************
 * FIX #1 — CLEAN EXISTING MAIN IMAGE
 **********************************************/
$baseUrl = "http://localhost/manager.bookpanlam/public/uploads/";

$mainImagePath = $_POST["existing_main_image"] ?? "";
$mainImagePath = str_replace($baseUrl, "", $mainImagePath); // remove full URL

// HANDLE NEW MAIN IMAGE
if (!empty($_FILES["main_image"]) && $_FILES["main_image"]["error"] === 0) {

    $year  = date("Y");
    $month = date("m");
    $day   = date("d");

    $folder = __DIR__ . "/../../../public/uploads/sellers/$user_id/services/$year/$month/$day/";
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $file = $_FILES["main_image"];
    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $filename = "srv_" . uniqid() . "." . $ext;

    if (move_uploaded_file($file["tmp_name"], $folder . $filename)) {
        $mainImagePath = "sellers/$user_id/services/$year/$month/$day/$filename";
    }
}

/**********************************************
 * UPDATE MAIN SERVICE RECORD
 **********************************************/
$updateSQL = "UPDATE  appointment_settings SET 
    name = :name,
    slug = :slug,
    amount = :amount,
    previous_amount = :previousAmount,
    image = :image,
    category_id = :categoryId,
    time_slot_interval = :slot,
    interval_type = :itype,
    description = :descr,
    gst_percentage = :gst,
    meta_title = :mtitle,
    meta_description = :mdesc,
    status = :status
    WHERE service_id = :service_id AND user_id = :user_id
";

$stmt = $pdo->prepare($updateSQL);
$updated = $stmt->execute([
    ":name" => $name,
    ":slug" => $slug,
    ":amount" => $amount,
    ":previousAmount" => $previousAmount,
    ":image" => $mainImagePath, // ALWAYS relative
    ":categoryId" => $categoryId,
    ":slot" => $timeSlotInterval,
    ":itype" => $intervalType,
    ":descr" => $description,
    ":gst" => $gstPercentage,
    ":mtitle" => $metaTitle,
    ":mdesc" => $metaDescription,
    ":status" => $status,
    ":service_id" => $service_id,
    ":user_id" => $user_id
]);

if (!$updated) {
    echo json_encode(["success" => false, "message" => "Service update failed"]);
    exit();
}

/**********************************************
 * FIX #2 — CLEAN EXISTING ADDITIONAL IMAGES
 **********************************************/
$existingImages = json_decode($_POST["existing_additional_images"] ?? "[]", true);
$cleanImages = [];

foreach ($existingImages as $img) {
    $cleanImages[] = str_replace($baseUrl, "", $img); // remove full URL
}

// DELETE old rows
$pdo->prepare("
    DELETE FROM service_images 
    WHERE service_id = (SELECT id FROM services WHERE service_id = ?)
")->execute([$service_id]);

// REINSERT clean old ones
$insertStmt = $pdo->prepare("
    INSERT INTO service_images (service_id, image) 
    VALUES ((SELECT id FROM services WHERE service_id = ?), ?)
");

foreach ($cleanImages as $img) {
    $insertStmt->execute([$service_id, $img]);
}

/**********************************************
 * HANDLE NEW ADDITIONAL IMAGES
 **********************************************/
if (!empty($_FILES["additional_images"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["additional_images"]["name"]); $i++) {

        $tmp = $_FILES["additional_images"]["tmp_name"][$i];
        $name = $_FILES["additional_images"]["name"][$i];

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $filename = "add_" . uniqid() . "." . $ext;

        $year = date("Y");
        $month = date("m");
        $day = date("d");

        $folder = __DIR__ . "/../../../public/uploads/sellers/$user_id/services/additional/$year/$month/$day/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        if (move_uploaded_file($tmp, $folder . $filename)) {
            $relativePath = "sellers/$user_id/services/additional/$year/$month/$day/$filename";
            $insertStmt->execute([$service_id, $relativePath]);
        }
    }
}

echo json_encode([
    "success" => true,
    "message" => "Service updated successfully"
]);
?>
