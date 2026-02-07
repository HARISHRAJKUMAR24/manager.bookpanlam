<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit();

if (!isset($_GET["user_id"])) {
    echo json_encode(["success"=>false,"message"=>"user_id missing"]);
    exit();
}

$userId = $_GET["user_id"];
$oldImage = $_POST["old_image"] ?? null;  // ⭐ get old image if sent

$year = date("Y");
$month = date("m");
$day = date("d");

$uploadDir = "../../../public/uploads/sellers/$userId/doctors/$year/$month/$day/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!isset($_FILES["file"])) {
    echo json_encode(["success"=>false,"message"=>"no file uploaded"]);
    exit();
}

$file = $_FILES["file"];
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$filename = time() . "_" . uniqid() . "." . $ext;

if (!move_uploaded_file($file["tmp_name"], $uploadDir.$filename)) {
    echo json_encode(["success"=>false,"message"=>"upload failed"]);
    exit();
}

/* ===========================
   DELETE OLD IMAGE (if exists)
=========================== */
if ($oldImage) {
    $oldPath = "../../../public/uploads/" . $oldImage;

    if (file_exists($oldPath)) {
        unlink($oldPath);
    }
}

/* value stored in DB */
$relativePath = "sellers/$userId/doctors/$year/$month/$day/$filename";

echo json_encode([
    "success"=>true,
    "filename"=>$relativePath
]);
exit();
