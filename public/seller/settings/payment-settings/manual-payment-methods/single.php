<?php
/* include COMMON HEADER above */

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid ID"
    ]);
    exit;
}

/* ===============================
   FETCH MANUAL PAYMENT METHOD
================================ */
$stmt = $pdo->prepare("
    SELECT 
        id,
        name,
        upi_id,      -- ✅ UPI ID field
        instructions,
        icon
    FROM manual_payment_methods
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

$stmt->execute([$id, $user_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Not found"
    ]);
    exit;
}

/* ===============================
   CONVERT ICON TO FULL URL
================================ */
if (!empty($data['icon'])) {
    $baseUrl = "http://localhost/manager.bookpanlam/public/";
    $data['icon'] = $baseUrl . $data['icon'];
}

/* ===============================
   RESPONSE
================================ */
echo json_encode([
    "success" => true,
    "data" => $data
]);