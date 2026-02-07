<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$id = $_GET['id'] ?? null;

if (!$id) {
  echo json_encode([
    "success" => false,
    "message" => "Item ID is required"
  ]);
  exit;
}

/* ================= GET MENU ITEM ================= */

$stmt = $pdo->prepare("
  SELECT
    id,
    name,
    description,
    hsn_code,
    menu_id,
    category_id,
    food_type,
    image,
    stock_type,
    stock_qty,
    stock_unit,
    halal,
    customer_limit,
    customer_limit_period,
    prebooking_enabled,        
    prebooking_min_amount,    
    prebooking_max_amount,     
    prebooking_advance_days,   
    price,
    created_at,
    updated_at
  FROM menu_items
  WHERE id = ?
");

$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
  echo json_encode([
    "success" => false,
    "message" => "Menu item not found"
  ]);
  exit;
}

// Convert prebooking_enabled to boolean for frontend
if (isset($item['prebooking_enabled'])) {
    $item['prebooking_enabled'] = (bool)$item['prebooking_enabled'];
}

/* ================= GET VARIATIONS ================= */

$varStmt = $pdo->prepare("
  SELECT
    id,
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
");

$varStmt->execute([$id]);
$variations = $varStmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= ATTACH VARIATIONS ================= */

$item['variations'] = $variations;

/* ================= RESPONSE ================= */

echo json_encode([
  "success" => true,
  "item" => $item
]);