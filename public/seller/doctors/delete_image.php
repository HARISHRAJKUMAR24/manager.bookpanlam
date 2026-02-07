<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit();

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data["image"])) {
    echo json_encode(["success" => false, "message" => "Image path missing"]);
    exit();
}

$image = $data["image"];

/*
    IMPORTANT!!
    delete_image.php path:
    /seller/doctors/delete_image.php
    Real uploads folder:
    /public/uploads/

    So real absolute path = FOUR levels back
*/
$path = "../../../public/uploads/" . $image;

if (file_exists($path)) {
    unlink($path);
    echo json_encode(["success" => true, "message" => "Image deleted"]);
} else {
    echo json_encode(["success" => false, "message" => "File not found", "path" => $path]);
}
