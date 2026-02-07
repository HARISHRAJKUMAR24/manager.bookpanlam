<?php
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$input = json_decode(file_get_contents("php://input"), true);

$category_id = $input["category_id"] ?? null;
$user_id     = $input["user_id"] ?? null;

if (!$category_id || !$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "category_id and user_id required"
    ]);
    exit;
}

try {
    // ⭐ FIX: Use 'categories' table (your doctor table)
    $sql = "SELECT *
            FROM categories
            WHERE category_id = :category_id
            AND user_id = :user_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category_id' => $category_id,
        ':user_id' => $user_id
    ]);

    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $doctor
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
