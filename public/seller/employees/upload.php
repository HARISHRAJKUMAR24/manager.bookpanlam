<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_GET["user_id"])) {
    echo json_encode(["success" => false, "message" => "user_id missing"]);
    exit;
}

$userId = $_GET["user_id"];

/*************************************
 * DELETE OLD IMAGE IF PROVIDED
 *************************************/
if (!empty($_POST["old_image"])) {
    $oldFile = dirname(__DIR__, 3) . "/public" . $_POST["old_image"];

    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

/*************************************
 * VALIDATE NEW FILE
 *************************************/
if (!isset($_FILES["file"])) {
    echo json_encode(["success" => false, "message" => "No file received"]);
    exit();
}

$file = $_FILES["file"];
$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

$year = date("Y");
$month = date("m");
$day = date("d");

$uploadDir = dirname(__DIR__, 3) . "/public/uploads/sellers/$userId/employees/$year/$month/$day/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = uniqid("emp_") . "." . $ext;
$path = $uploadDir . $filename;

/*************************************
 * SAVE FILE
 *************************************/
if (move_uploaded_file($file["tmp_name"], $path)) {

    $relativePath = "/uploads/sellers/$userId/employees/$year/$month/$day/$filename";

    echo json_encode([
        "success" => true,
        "filename" => $relativePath
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Upload failed"]);
}
?>
