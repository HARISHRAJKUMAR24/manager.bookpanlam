<?php
require "../../config/db.php";
require "../../config/auth.php";

header('Content-Type: application/json');

try {
   $stmt = $pdo->prepare("
    SELECT 
        mi.id,
        mi.name,
        mi.description,
        mi.hsn_code,
        mi.image,
        mi.menu_id,
        mi.category_id,
        mi.food_type,
        mi.halal,
        mi.stock_type,
        mi.stock_qty,
        mi.stock_unit,
        mi.selling_price,
        mi.mrp_price,
        mi.is_best_seller,
        mi.is_available,
        mi.show_on_site,
        mi.customer_limit,
        mi.customer_limit_period,
        mi.created_at,
        mi.updated_at,
        -- ✅ ADD THESE 4 FIELDS:
        mi.prebooking_enabled,
        mi.prebooking_min_amount,
        mi.prebooking_max_amount,
        mi.prebooking_advance_days,
        c.name AS category_name,
        m.name AS menu_name,
        COUNT(DISTINCT oi.id) AS order_count
    FROM menu_items mi
    LEFT JOIN categories c ON mi.category_id = c.id
    LEFT JOIN menus m ON mi.menu_id = m.id
    LEFT JOIN order_items oi ON mi.id = oi.menu_item_id
    WHERE mi.user_id = ?
    GROUP BY mi.id
    ORDER BY mi.created_at DESC
");

    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = array_map(function ($item) {
    return [
        'id' => (int)$item['id'],
        'name' => $item['name'],
        'description' => $item['description'],
        'hsn_code' => $item['hsn_code'],
        'image' => $item['image'],
        'menu_id' => (int)$item['menu_id'],
        'category_id' => (int)$item['category_id'],
        'food_type' => $item['food_type'] === 'nonveg' ? 'non-veg' : 'veg',
        'halal' => (bool)$item['halal'],
        'stock_type' => $item['stock_type'],
        'stock_qty' => (int)$item['stock_qty'],
        'stock_unit' => $item['stock_unit'],
        'price' => (float)$item['selling_price'],
        'original_price' => (float)$item['mrp_price'],
        'order_count' => (int)$item['order_count'],
        'is_best_seller' => (bool)$item['is_best_seller'],
        'is_available' => (bool)$item['is_available'],
        'show_on_site' => (bool)$item['show_on_site'],
        'customer_limit' => $item['customer_limit'] ? (int)$item['customer_limit'] : null,
        'customer_limit_period' => $item['customer_limit_period'],
        'created_at' => $item['created_at'],
        'last_updated' => $item['updated_at'],
        // ✅ ADD THESE 4 FIELDS TO THE RESPONSE:
        'prebooking_enabled' => (bool)$item['prebooking_enabled'],
        'prebooking_min_amount' => (float)$item['prebooking_min_amount'],
        'prebooking_max_amount' => (float)$item['prebooking_max_amount'],
        'prebooking_advance_days' => (int)$item['prebooking_advance_days'],
    ];
}, $items);

    echo json_encode($data);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}