<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['employee_id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "Employee ID missing"]);
    exit;
}

$name         = $data["name"];
$position     = $data["position"];
$phone        = $data["phone"];
$email        = $data["email"];
$address      = $data["address"];
$image        = $data["image"] ?? null;        // ⭐ ADD IMAGE HERE
$joining_date = $data["joining_date"] ?? null;

// Convert date format
if (!empty($joining_date)) {
    $joining_date = date("Y-m-d", strtotime($joining_date));
}

$stmt = $pdo->prepare("
    UPDATE employees 
    SET name=?, position=?, phone=?, email=?, address=?, joining_date=?, image=?
    WHERE employee_id=?
");

$updated = $stmt->execute([
    $name,
    $position,
    $phone,
    $email,
    $address,
    $joining_date,
    $image,         // ⭐ UPDATE IMAGE IN DB
    $id
]);

if ($updated) {
    echo json_encode(["success" => true, "message" => "Employee updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed"]);
}
