<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* READ JSON */
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$category_id = $data['category_id'] ?? $_GET['category_id'] ?? null;
$user_id     = $data['user_id'] ?? $_GET['user_id'] ?? null;

if (!$category_id || !$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "category_id or user_id missing"
    ]);
    exit();
}

/* GET DOCTOR IMAGE */
$stmt = $pdo->prepare("
    SELECT doctor_image 
    FROM categories
    WHERE category_id = ? AND user_id = ?
");
$stmt->execute([$category_id, $user_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$doctorImage = $row['doctor_image'] ?? null;

/* DELETE CATEGORY */
$del = $pdo->prepare("
    DELETE FROM categories
    WHERE category_id = ? AND user_id = ?
");
$del->execute([$category_id, $user_id]);

/* DELETE DOCTOR */
$pdo->prepare("
    DELETE FROM doctors
    WHERE category_id = ? AND user_id = ?
")->execute([$category_id, $user_id]);

/* DELETE IMAGE FILE */
if (!empty($doctorImage)) {

    // 3 levels up to reach /public/uploads/
    $fullPath = __DIR__ . "/../../../public/uploads/" . $doctorImage;

    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

/* SUCCESS */
echo json_encode([
    "success" => true,
    "message" => "Category, doctor details & doctor image deleted successfully"
]);
exit();
