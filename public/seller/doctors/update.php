<?php
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$input = json_decode(file_get_contents("php://input"), true);

// Incoming fields
$user_id      = $input["user_id"] ?? null;
$category_id  = $input["category_id"] ?? null;
$doctor_name  = $input["doctor_name"] ?? "";
$specialization = $input["specialization"] ?? "";
$qualification  = $input["qualification"] ?? "";
$experience   = $input["experience"] ?? null;
$reg_number   = $input["reg_number"] ?? "";
$doctor_image = $input["doctor_image"] ?? null;

if (!$user_id || !$category_id) {
    echo json_encode([
        "success" => false,
        "message" => "user_id and category_id required"
    ]);
    exit;
}

try {

    // ⭐ FIRST: Check if doctor already exists
    $checkSql = "SELECT id FROM doctors WHERE user_id = :user_id AND category_id = :category_id LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        ":user_id" => $user_id,
        ":category_id" => $category_id
    ]);

    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {

        // ⭐ UPDATE EXISTING DOCTOR
        $sql = "UPDATE doctors SET
                    doctor_name = :doctor_name,
                    specialization = :specialization,
                    qualification = :qualification,
                    experience = :experience,
                    reg_number = :reg_number,
                    doctor_image = :doctor_image
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':doctor_name' => $doctor_name,
            ':specialization' => $specialization,
            ':qualification' => $qualification,
            ':experience' => $experience,
            ':reg_number' => $reg_number,
            ':doctor_image' => $doctor_image,
            ':id' => $existing["id"]
        ]);

        echo json_encode(["success" => true, "message" => "Doctor updated"]);
        exit;

    } else {

        // ⭐ IF NOT EXISTS: INSERT NEW
        $sql = "INSERT INTO doctors
                (user_id, category_id, doctor_name, specialization, qualification, experience, reg_number, doctor_image)
                VALUES 
                (:user_id, :category_id, :doctor_name, :specialization, :qualification, :experience, :reg_number, :doctor_image)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':user_id' => $user_id,
            ':category_id' => $category_id,
            ':doctor_name' => $doctor_name,
            ':specialization' => $specialization,
            ':qualification' => $qualification,
            ':experience' => $experience,
            ':reg_number' => $reg_number,
            ':doctor_image' => $doctor_image
        ]);

        echo json_encode(["success" => true, "message" => "Doctor inserted"]);
        exit;
    }

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
