<?php
/****************************************************
 * FIXED CORS → allows browser fetch requests
 ****************************************************/
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/****************************************************
 * DEBUG (optional, helps ensure PHP is running)
 ****************************************************/
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/****************************************************
 * Read JSON request body
 ****************************************************/
$input = json_decode(file_get_contents("php://input"), true);

$user_id = $input["user_id"] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "user_id required"
    ]);
    exit();
}

try {

    $sql = "SELECT * FROM categories WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $categories
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
