<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

/**
 * Absolute paths (CORRECT)
 * get.php → public/site/homepage/
 * ../../../ → manager.bookpanlam/
 */
require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* =============================
   READ SELLER ID
============================= */
$seller_id = (int)($_GET['seller_id'] ?? 0);

if (!$seller_id) {
  echo json_encode([
    "success" => false,
    "message" => "Missing seller_id"
  ]);
  exit;
}

/* =============================
   FETCH WEBSITE SETTINGS
============================= */
$stmt = $pdo->prepare("
  SELECT
    hero_title,
    hero_description,
    hero_image,
    banners
  FROM website_settings
  WHERE user_id = ?
  LIMIT 1
");
$stmt->execute([$seller_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  echo json_encode([
    "success" => true,
    "data" => null
  ]);
  exit;
}

/* =============================
   DECODE BANNERS JSON
============================= */
$row["banners"] = $row["banners"]
  ? json_decode($row["banners"], true)
  : [];

echo json_encode([
  "success" => true,
  "data" => $row
]);
exit;
