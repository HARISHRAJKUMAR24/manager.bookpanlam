<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$sql = "
SELECT
  mi.id,
  mi.name,
  mi.description,
  mi.hsn_code,
  mi.menu_id,
  mi.category_id,
  mi.food_type,
  mi.image,
  mi.stock_type,
  mi.stock_qty,
  mi.stock_unit,
  mi.halal,
  mi.customer_limit,
  mi.customer_limit_period,
  mi.created_at AS last_updated,
  mi.price AS price,
  mi.price AS original_price,
  mi.prebooking_enabled, -- ✅ ADD THIS
  mi.prebooking_min_amount, -- ✅ ADD THIS
  mi.prebooking_max_amount, -- ✅ ADD THIS
  mi.prebooking_advance_days, -- ✅ ADD THIS
  0 AS order_count,
  0 AS rating,
  'Medium' AS spice_level,
  mi.active AS is_available,
  mi.active AS show_on_site,
  0 AS is_best_seller
FROM menu_items mi
ORDER BY mi.created_at DESC
";

$stmt = $pdo->query($sql);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Ensure frontend never crashes */
foreach ($items as &$item) {
  $item['tags'] = [];
  // Ensure boolean values
  $item['is_available'] = (bool)$item['is_available'];
  $item['show_on_site'] = (bool)$item['show_on_site'];
  $item['is_best_seller'] = (bool)$item['is_best_seller'];
  $item['halal'] = (bool)$item['halal'];
  
  // ✅ ADD PREBOOKING FIELDS WITH DEFAULT VALUES
  $item['prebooking_enabled'] = isset($item['prebooking_enabled']) ? (bool)$item['prebooking_enabled'] : false;
  $item['prebooking_min_amount'] = $item['prebooking_min_amount'] ?? null;
  $item['prebooking_max_amount'] = $item['prebooking_max_amount'] ?? null;
  $item['prebooking_advance_days'] = $item['prebooking_advance_days'] ?? null;
}

echo json_encode($items);