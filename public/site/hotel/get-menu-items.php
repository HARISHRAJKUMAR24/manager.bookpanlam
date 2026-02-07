<?php
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../src/database.php";

$pdo = getDbConnection();

/* READ SELLER ID */
$seller_id = (int)($_GET['seller_id'] ?? 0);

if (!$seller_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing seller_id"
    ]);
    exit;
}

/* FETCH ALL MENU ITEMS */
$stmt = $pdo->prepare("
    SELECT 
        id,
        name,
        description,
        price,
        type,
        food_type,
        halal,
        stock_type,
        stock_qty,
        stock_unit,
        image,
        created_at,
        updated_at,
        -- ✅ ADD THESE 4 PREBOOKING FIELDS:
        prebooking_enabled,
        prebooking_min_amount,
        prebooking_max_amount,
        prebooking_advance_days
    FROM menu_items
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$seller_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* FOR EACH ITEM → FETCH VARIATIONS */
foreach ($items as $index => $item) {

    $v = $pdo->prepare("
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
        WHERE item_id = ? AND user_id = ?
        ORDER BY id ASC
    ");

    $v->execute([$item['id'], $seller_id]);
    $variations = $v->fetchAll(PDO::FETCH_ASSOC);

    // Attach to item
    $items[$index]['variations'] = $variations;

    // Format food type
    $items[$index]['food_type'] =
        ($item['food_type'] === 'nonveg') ? 'non-veg' : 'veg';
    
    // ✅ Convert prebooking_enabled to boolean for better frontend handling
    $items[$index]['prebooking_enabled'] = (bool)($item['prebooking_enabled'] ?? false);
}

/* SEND RESPONSE */
echo json_encode([
    "success" => true,
    "data" => $items
]);

exit;