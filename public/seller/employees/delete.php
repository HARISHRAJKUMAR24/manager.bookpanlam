<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ------------------- INPUT ------------------- */
$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "Employee id missing"]);
    exit;
}

/* ------------------- GET IMAGE PATH BEFORE DELETE ------------------- */
$stmt = $pdo->prepare("SELECT image, user_id FROM employees WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    echo json_encode(["success" => false, "message" => "Employee not found"]);
    exit;
}

$oldImage = $emp["image"];     // Example: /uploads/sellers/12/employees/2026/02/01/emp_8adf34.png
$userId   = $emp["user_id"];

/* ------------------- DELETE RECORD ------------------- */
$stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
$deleted = $stmt->execute([$id]);

if ($deleted) {

    // Delete image if exists
    if (!empty($oldImage)) {
        $filePath = dirname(__DIR__, 3) . "/public" . $oldImage;

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Employee deleted successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to delete employee"
    ]);
}
