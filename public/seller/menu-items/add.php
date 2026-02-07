<?php

/* ===============================
   HEADERS / CORS
================================ */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

/* ===============================
   BOOTSTRAP
================================ */
require_once "../../../config/config.php";
require_once "../../../src/database.php";
require_once "../../../src/functions.php";

$pdo = getDbConnection();

/* ===============================
   READ INPUT
================================ */
$raw   = file_get_contents("php://input");
$input = json_decode($raw, true) ?? [];

/* ===============================
   AUTH: TOKEN → USER
================================ */
$token =
    ($input["token"] ?? null)
    ?: ($_COOKIE["token"] ?? "");

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized: Missing token"
    ]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT user_id FROM users WHERE api_token = ? LIMIT 1"
);
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$user_id = (int)$user->user_id;

// Check menu limit before creating
$limitResult = getUserPlanLimit($user_id, 'menu');

if (!$limitResult['can_add']) {
    echo json_encode([
        "success" => false,
        "message" => $limitResult['message']
    ]);
    exit;
}

/* ===============================
   VALIDATION
================================ */
$required = ["menu_id", "name", "food_type", "stock_type", "variations"];

foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode([
            "success" => false,
            "message" => "$field is required"
        ]);
        exit;
    }
}

if (!is_array($input["variations"]) || count($input["variations"]) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "At least one variation is required"
    ]);
    exit;
}

/* ===============================
   SAFE VALUES
================================ */
$halal      = !empty($input["halal"]) ? 1 : 0;
$stockQty   = $input["stock_qty"] ?? null;
$stockUnit  = $input["stock_unit"] ?? null;
$categoryId = $input["category_id"] ?? null;
$hsnCode    = !empty($input["hsn_code"]) ? trim($input["hsn_code"]) : null;

// NEW: Prebooking values
$prebookingEnabled = isset($input["prebooking_enabled"]) && $input["prebooking_enabled"] ? 1 : 0;
$prebookingMinAmount = isset($input["prebooking_min_amount"]) && $input["prebooking_min_amount"] > 0 
    ? (float)$input["prebooking_min_amount"] : null;
$prebookingMaxAmount = isset($input["prebooking_max_amount"]) && $input["prebooking_max_amount"] > 0 
    ? (float)$input["prebooking_max_amount"] : null;
$prebookingAdvanceDays = isset($input["prebooking_advance_days"]) && $input["prebooking_advance_days"] > 0 
    ? (int)$input["prebooking_advance_days"] : null;

/* ===============================
   TRANSACTION
================================ */
$pdo->beginTransaction();

try {

    /* ===============================
       INSERT MENU ITEM (WITH PREBOOKING)
    ================================ */
    $stmt = $pdo->prepare("
        INSERT INTO menu_items (
            user_id,
            menu_id,
            category_id,
            name,
            description,
            hsn_code,
            food_type,
            halal,
            stock_type,
            stock_qty,
            stock_unit,
            customer_limit,
            customer_limit_period,
            prebooking_enabled,        -- NEW
            prebooking_min_amount,     -- NEW
            prebooking_max_amount,     -- NEW
            prebooking_advance_days,   -- NEW
            image,
            active,
            created_at
        ) VALUES (
            :user_id,
            :menu_id,
            :category_id,
            :name,
            :description,
            :hsn_code,
            :food_type,
            :halal,
            :stock_type,
            :stock_qty,
            :stock_unit,
            :customer_limit,
            :customer_limit_period,
            :prebooking_enabled,       -- NEW
            :prebooking_min_amount,    -- NEW
            :prebooking_max_amount,    -- NEW
            :prebooking_advance_days,  -- NEW
            :image,
            1,
            NOW()
        )
    ");

    $stmt->execute([
        ":user_id"               => $user_id,
        ":menu_id"               => (int)$input["menu_id"],
        ":category_id"           => $categoryId,
        ":name"                  => trim($input["name"]),
        ":description"           => $input["description"] ?? "",
        ":hsn_code"              => $hsnCode,
        ":food_type"             => $input["food_type"],
        ":halal"                 => $halal,
        ":stock_type"            => $input["stock_type"],
        ":stock_qty"             => $stockQty,
        ":stock_unit"            => $stockUnit,
        ":customer_limit"        => $input["customer_limit"] ?? null,
        ":customer_limit_period" => $input["customer_limit_period"] ?? null,
        ":prebooking_enabled"    => $prebookingEnabled,           // NEW
        ":prebooking_min_amount" => $prebookingMinAmount,         // NEW
        ":prebooking_max_amount" => $prebookingMaxAmount,         // NEW
        ":prebooking_advance_days" => $prebookingAdvanceDays,     // NEW
        ":image"                 => $input["image"] ?? null,
    ]);

    $itemId = $pdo->lastInsertId();

    /* ===============================
       INSERT VARIATIONS
    ================================ */
    $varStmt = $pdo->prepare("
        INSERT INTO menu_item_variations (
            user_id,
            item_id,
            name,
            mrp_price,
            selling_price,
            discount_percent,
            dine_in_price,
            takeaway_price,
            delivery_price,
            is_active
        ) VALUES (
            :user_id,
            :item_id,
            :name,
            :mrp_price,
            :selling_price,
            :discount_percent,
            :dine_in_price,
            :takeaway_price,
            :delivery_price,
            1
        )
    ");

    $minSelling = PHP_FLOAT_MAX;

    foreach ($input["variations"] as $v) {

        $mrp     = isset($v["mrp_price"]) ? (float)$v["mrp_price"] : 0;
        $selling = isset($v["selling_price"]) ? (float)$v["selling_price"] : 0;

        $dineIn    = ($v["dine_in_price"] !== "" && $v["dine_in_price"] !== null)
            ? (float)$v["dine_in_price"]
            : null;

        $takeaway = ($v["takeaway_price"] !== "" && $v["takeaway_price"] !== null)
            ? (float)$v["takeaway_price"]
            : null;

        $delivery = ($v["delivery_price"] !== "" && $v["delivery_price"] !== null)
            ? (float)$v["delivery_price"]
            : null;

        $varStmt->execute([
            ":user_id"          => $user_id,
            ":item_id"          => $itemId,
            ":name"             => trim($v["name"]),
            ":mrp_price"        => $mrp,
            ":selling_price"    => $selling,
            ":discount_percent" => (float)($v["discount_percent"] ?? 0),
            ":dine_in_price"    => $dineIn,
            ":takeaway_price"   => $takeaway,
            ":delivery_price"   => $delivery,
        ]);

        $minSelling = min($minSelling, $selling);
    }

    /* ===============================
       UPDATE BASE PRICE
    ================================ */
    $pdo->prepare("
        UPDATE menu_items
        SET price = ?
        WHERE id = ?
    ")->execute([$minSelling, $itemId]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Menu item created successfully",
        "id"      => $itemId
    ]);

} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}