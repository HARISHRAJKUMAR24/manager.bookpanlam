<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$item_id = $_GET['item_id'] ?? null;

if (!$item_id) {
  echo json_encode([
    "success" => false,
    "variations" => [],
    "message" => "Missing item_id"
  ]);
  exit;
}

$sql = "
  SELECT
    id,
    item_id,
    name,
    mrp_price,
    selling_price,
    discount_percent,
    dine_in_price,
    takeaway_price,
    delivery_price,
    is_active
  FROM menu_item_variations
  WHERE item_id = ?
  ORDER BY id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$item_id]);

$variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  "success" => true,
  "variations" => $variations
]);
