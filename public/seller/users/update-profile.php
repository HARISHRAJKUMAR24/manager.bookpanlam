<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   READ POST DATA (ONLY user_id)
================================ */
$user_id = $_POST["user_id"] ?? 0;
$name    = $_POST["name"] ?? "";
$email   = $_POST["email"] ?? "";
$phone   = $_POST["phone"] ?? "";
$country = $_POST["country"] ?? "";

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "user_id missing"
    ]);
    exit;
}

$imagePath = null;

/* ===============================
   IMAGE UPLOAD
   /uploads/sellers/52064/profile/2025/12/11/profile_xxx.webp
================================ */
if (!empty($_FILES["image"]["name"])) {

    $year  = date("Y");
    $month = date("m");
    $day   = date("d");

    $baseDir = "../../../public/uploads/sellers/$user_id/profile/$year/$month/$day";

    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $fileName = "profile_" . uniqid() . "." . $ext;
    $targetPath = "$baseDir/$fileName";

    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
        echo json_encode([
            "success" => false,
            "message" => "Image upload failed"
        ]);
        exit;
    }

    // 🔥 STORE THIS PATH IN DB
    $imagePath = "/uploads/sellers/$user_id/profile/$year/$month/$day/$fileName";
}

/* ===============================
   UPDATE USER (BY user_id)
================================ */
$sql = "UPDATE users SET
            name    = :name,
            email   = :email,
            phone   = :phone,
            country = :country,
            image   = COALESCE(:image, image)
        WHERE user_id = :user_id";

$stmt = $pdo->prepare($sql);

$success = $stmt->execute([
    ":name"    => $name,
    ":email"   => $email,
    ":phone"   => $phone,
    ":country" => $country,
    ":image"   => $imagePath,
    ":user_id" => $user_id
]);

echo json_encode([
    "success" => $success,
    "message" => $success ? "Profile updated successfully!" : "Update failed"
]);
