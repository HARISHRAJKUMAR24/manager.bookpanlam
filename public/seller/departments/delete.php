<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$department_id = $_GET['department_id'] ?? '';

error_log("DELETE REQUESTED department_id: " . $department_id);

if (!$department_id) {
    echo json_encode([
        "success" => false, 
        "message" => "Department ID missing"
    ]);
    exit();
}

/* -----------------------------------------
   STEP 1: DELETE ADDITIONAL IMAGES
----------------------------------------- */
try {
    $delImgs = $pdo->prepare("
        DELETE FROM department_additional_images 
        WHERE department_id = ?
    ");
    $delImgs->execute([$department_id]);

    error_log("Deleted additional images for: " . $department_id);

} catch (Exception $e) {
    error_log("IMAGE DELETE ERROR: " . $e->getMessage());
}

/* -----------------------------------------
   STEP 2: DELETE MAIN DEPARTMENT
----------------------------------------- */
$sql = "DELETE FROM departments WHERE department_id = :did";
$stmt = $pdo->prepare($sql);
$stmt->execute([':did' => $department_id]);

$deleted = $stmt->rowCount() > 0;

/* -----------------------------------------
   RESPONSE
----------------------------------------- */
echo json_encode([
    "success" => $deleted,
    "message" => $deleted ? "Department deleted" : "Department not found"
]);

exit();
?>
