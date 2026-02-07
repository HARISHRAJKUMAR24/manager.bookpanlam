<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   1️⃣ GET SITE SLUG
================================ */
$slug = $_GET["site"] ?? "";

if (!$slug) {
    echo json_encode([
        "success" => false,
        "message" => "Site slug missing"
    ]);
    exit;
}

/* ===============================
   2️⃣ GET SELLER BY SLUG
================================ */
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE site_slug = ? LIMIT 1");
$stmt->execute([$slug]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid site"
    ]);
    exit;
}

$user_id = $user["user_id"];

/* ===============================
   3️⃣ GET SITE SETTINGS BY USER_ID
================================ */
$stmt = $pdo->prepare("
    SELECT 
        id,
        user_id,
        logo,
        favicon,
        phone,
        whatsapp,
        email,
        currency,
        country,
        state,
        address,
        facebook,
        twitter,
        instagram,
        linkedin,
        youtube,
        pinterest,
        meta_title,
        meta_description,
        sharing_image_preview,
        gst_number,
        gst_type,
        tax_percent,
        selected_template
    FROM site_settings
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

/* If no settings exist, return defaults */
if (!$settings) {
    echo json_encode([
        "success" => true,
        "data" => null
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => $settings
]);

exit;
