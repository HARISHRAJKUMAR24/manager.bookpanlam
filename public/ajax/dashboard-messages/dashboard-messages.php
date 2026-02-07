<?php
// ======================================================
// Dashboard Messages Admin Controller (FINAL FIXED)
// ======================================================

// DEV MODE (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../src/database.php';
require_once '../../../src/functions.php';

// ------------------------------------------------------
// AUTH CHECK
// ------------------------------------------------------
if (!isLoggedIn()) {
    exit(json_encode(["type" => "error", "msg" => "Please login first"]));
}

if (!isAdmin()) {
    exit(json_encode(["type" => "error", "msg" => "Access denied"]));
}

// DB connection
$pdo = getDbConnection();

// Request type
$request = $_POST['request'] ?? '';


// ======================================================
// CREATE MESSAGE
// ======================================================
if ($request === 'create') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $expiry_value = (int)($_POST['expiry_value'] ?? 0);
    $expiry_type = $_POST['expiry_type'] ?? 'hours';
    $seller_type = $_POST['seller_type'] ?? [];
    $just_created_seller = isset($_POST['just_created_seller']) ? 1 : 0;

    if ($title === '' || $description === '' || $expiry_value <= 0) {
        exit(json_encode([
            "type" => "error",
            "msg" => "All required fields must be filled"
        ]));
    }

    $expiry_date = calculateExpiryDate($expiry_value, $expiry_type);

    $seller_type_json = null;
    if (!empty($seller_type) && !in_array('all', $seller_type)) {
        $seller_type_json = json_encode($seller_type);
    }

    $stmt = $pdo->prepare("
        INSERT INTO dashboard_messages
        (title, description, expiry_type, expiry_value, expiry_date, seller_type, just_created_seller)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $title,
        $description,
        $expiry_type,
        $expiry_value,
        $expiry_date,
        $seller_type_json,
        $just_created_seller
    ]);

    exit(json_encode([
        "type" => "success",
        "msg" => "Message created successfully!"
    ]));
}


// ======================================================
// UPDATE MESSAGE
// ======================================================
if ($request === 'update') {

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        exit(json_encode(["type" => "error", "msg" => "Invalid ID"]));
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $expiry_value = (int)($_POST['expiry_value'] ?? 0);
    $expiry_type = $_POST['expiry_type'] ?? 'hours';
    $seller_type = $_POST['seller_type'] ?? [];
    $just_created_seller = isset($_POST['just_created_seller']) ? 1 : 0;

    if ($title === '' || $description === '' || $expiry_value <= 0) {
        exit(json_encode(["type" => "error", "msg" => "All required fields must be filled"]));
    }

    $expiry_date = calculateExpiryDate($expiry_value, $expiry_type);

    $seller_type_json = null;
    if (!empty($seller_type) && !in_array('all', $seller_type)) {
        $seller_type_json = json_encode($seller_type);
    }

    $stmt = $pdo->prepare("
        UPDATE dashboard_messages SET
            title = ?,
            description = ?,
            expiry_type = ?,
            expiry_value = ?,
            expiry_date = ?,
            seller_type = ?,
            just_created_seller = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $title,
        $description,
        $expiry_type,
        $expiry_value,
        $expiry_date,
        $seller_type_json,
        $just_created_seller,
        $id
    ]);

    exit(json_encode([
        "type" => "success",
        "msg" => "Message updated successfully!"
    ]));
}


// ======================================================
// DISPLAY MESSAGES
// ======================================================
if ($request === 'display') {

    $stmt = $pdo->query("
        SELECT *
        FROM dashboard_messages
        ORDER BY created_at DESC
    ");

    $messages = $stmt->fetchAll(PDO::FETCH_OBJ);
    $data = [];

    foreach ($messages as $message) {

        $is_expired = isMessageExpired($message->expiry_date);
        $status = $is_expired ? 'Expired' : 'Active';

        $seller_types = "All Sellers";
        if ($message->seller_type) {
            $ids = json_decode($message->seller_type, true);
            if ($ids) {
                $in = implode(',', array_fill(0, count($ids), '?'));
                $stmt2 = $pdo->prepare("SELECT name FROM subscription_plans WHERE id IN ($in)");
                $stmt2->execute($ids);
                $seller_types = implode(', ', $stmt2->fetchAll(PDO::FETCH_COLUMN));
            }
        }

        $date = convertToAppTimezone($message->expiry_date, 'd/m/Y');
        $time = convertToAppTimezone($message->expiry_date, 'g:i A');

        $expiry_html = $is_expired
            ? "<span class='text-danger'>Expired: $date<br>$time</span>"
            : "<span class='text-success'>Expires: $date<br>$time</span>";

        $data[] = [
            "id" => (int)$message->id,
            "title" => htmlspecialchars($message->title),
            "description" => htmlspecialchars($message->description),
            "seller_type" => $seller_types,
            "expiry" => $expiry_html,
            "status" => $status,
            "just_created_seller" => (int)$message->just_created_seller,
            "target_badge" => $message->just_created_seller
                ? "<span class='badge badge-light-primary ms-2 fs-8'>New Sellers Only</span>"
                : ""
        ];
    }

    exit(json_encode([
        "success" => true,
        "data" => $data
    ]));
}


// ======================================================
// GET SINGLE MESSAGE (EDIT)
// ======================================================
if ($request === 'get_single') {

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        exit(json_encode(["success" => false, "msg" => "Invalid ID"]));
    }

    $stmt = $pdo->prepare("
        SELECT id, title, description, expiry_value, expiry_type, seller_type, just_created_seller
        FROM dashboard_messages
        WHERE id = ?
    ");

    $stmt->execute([$id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        exit(json_encode(["success" => false, "msg" => "Message not found"]));
    }

    $message['seller_type'] = $message['seller_type']
        ? json_decode($message['seller_type'], true)
        : null;

    exit(json_encode([
        "success" => true,
        "data" => $message
    ]));
}


// ======================================================
// DELETE MESSAGE
// ======================================================
if ($request === 'delete') {

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        exit(json_encode(["type" => "error", "msg" => "Invalid ID"]));
    }

    $stmt = $pdo->prepare("DELETE FROM dashboard_messages WHERE id = ?");
    $stmt->execute([$id]);

    exit(json_encode([
        "type" => "success",
        "msg" => "Message deleted successfully!"
    ]));
}


// ======================================================
// INVALID REQUEST (MUST BE LAST)
// ======================================================
exit(json_encode([
    "type" => "error",
    "msg" => "Invalid request"
]));
