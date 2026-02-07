<?php
ob_clean(); // 🔥 FIX: Prevents JSON from breaking due to warnings/notices

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$department_uid = $_GET['department_id'] ?? '';

if (!$department_uid) {
    echo json_encode([ 
        "success" => false, 
        "message" => "Department ID required" 
    ], JSON_UNESCAPED_SLASHES);
    exit();
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

error_log("UPDATE RAW DATA: " . $rawData);

if (!is_array($data)) {
    echo json_encode([ 
        "success" => false, 
        "message" => "Invalid JSON data" 
    ], JSON_UNESCAPED_SLASHES);
    exit();
}

/* ----------------------------------------------------
   REMOVE PROTECTED FIELDS
-----------------------------------------------------*/
$protected = ['id', 'department_id', 'user_id', 'created_at', 'updated_at', 'token'];
foreach ($protected as $field) unset($data[$field]);

/* ----------------------------------------------------
   FETCH DEPARTMENT
-----------------------------------------------------*/
$stmt = $pdo->prepare("SELECT id FROM departments WHERE department_id = ? LIMIT 1");
$stmt->execute([$department_uid]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    echo json_encode([ 
        "success" => false, 
        "message" => "Department not found" 
    ], JSON_UNESCAPED_SLASHES);
    exit();
}

/* ----------------------------------------------------
   HANDLE ADDITIONAL IMAGES
-----------------------------------------------------*/
$baseImageUrl = "http://localhost/manager.bookpanlam/public/uploads/";
$additionalImages = $data["additionalImages"] ?? null;
unset($data["additionalImages"]); // Remove before update

if (is_array($additionalImages)) {
    // Delete old images
    $pdo->prepare("
        DELETE FROM department_additional_images 
        WHERE department_id = ?
    ")->execute([$department_uid]);

    $insert = $pdo->prepare("
        INSERT INTO department_additional_images (department_id, image)
        VALUES (:department_id, :image)
    ");

    foreach ($additionalImages as $img) {
        // Convert full url → relative
        if (strpos($img, $baseImageUrl) === 0) {
            $img = substr($img, strlen($baseImageUrl));
        }

        $insert->execute([
            ":department_id" => $department_uid,
            ":image" => trim($img)
        ]);
    }
}

/* ----------------------------------------------------
   UPDATE MAIN DEPARTMENT FIELDS
-----------------------------------------------------*/
$fields = [];
$bindValues = [];

foreach ($data as $key => $value) {
    if ($value === '') $value = null;

    // Convert amount fields to float
    if (strpos($key, '_amount') !== false && $value !== null) {
        $value = floatval($value);
    }

    $fields[] = "`$key` = :$key";
    $bindValues[":$key"] = $value;
}

$bindValues[':department_id'] = $department_uid;

$sql = "UPDATE departments 
        SET " . implode(", ", $fields) . ", updated_at = NOW(3)
        WHERE department_id = :department_id";

try {
    $stmt = $pdo->prepare($sql);

    foreach ($bindValues as $param => $value) {
        $stmt->bindValue($param, $value);
    }

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Department updated successfully"
    ], JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log("UPDATE ERROR: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ], JSON_UNESCAPED_SLASHES);
}
exit();
?>