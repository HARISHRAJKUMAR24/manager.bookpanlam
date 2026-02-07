<?php
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$input = json_decode(file_get_contents("php://input"), true);

$user_id        = $input["user_id"] ?? null;   // 👈 missing
$category_id    = $input["category_id"] ?? null;
$doctor_name    = $input["doctor_name"] ?? "";
$specialization = $input["specialization"] ?? "";
$qualification  = $input["qualification"] ?? "";
$experience     = $input["experience"] ?? null;
$reg_number     = $input["reg_number"] ?? "";
$doctor_image   = $input["doctor_image"] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "user_id required"
    ]);
    exit();
}

try {

    // validate category exists AND belongs to this user
    $check = $pdo->prepare("SELECT id FROM categories WHERE id = :id AND user_id = :uid LIMIT 1");
    $check->execute([
        ':id'  => $category_id,
        ':uid' => $user_id
    ]);

    if (!$check->fetch()) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid category_id or unauthorized access"
        ]);
        exit();
    }

    // insert doctor
    $sql = "INSERT INTO doctors
            (user_id, category_id, doctor_name, specialization, qualification, experience, reg_number, doctor_image)
            VALUES
            (:user_id, :category_id, :doctor_name, :specialization, :qualification, :experience, :reg_number, :doctor_image)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id'        => $user_id,
        ':category_id'    => $category_id,
        ':doctor_name'    => $doctor_name,
        ':specialization' => $specialization,
        ':qualification'  => $qualification,
        ':experience'     => $experience,
        ':reg_number'     => $reg_number,
        ':doctor_image'   => $doctor_image,
    ]);

    $doctorId = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "doctor_id" => $doctorId
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
    exit();
}
