<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
    http_response_code(200);
    exit();
}

$user_id = $_GET["user_id"] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit();
}

if (!isset($_FILES["files"])) {
    echo json_encode(["success" => false, "message" => "No files uploaded"]);
    exit();
}

$today = date("Y/m/d");

// NEW PATH (correct)
$basePath = __DIR__ . "/../../../public/uploads/sellers/$user_id/services/additional/$today";

if (!is_dir($basePath)) {
    mkdir($basePath, 0777, true);
}

$resultFiles = [];

foreach ($_FILES["files"]["name"] as $i => $file) {

    $extension = pathinfo($file, PATHINFO_EXTENSION);
    $filename = "add_" . uniqid() . "." . $extension;

    $tmp = $_FILES["files"]["tmp_name"][$i];
    $fullPath = "$basePath/$filename";

    move_uploaded_file($tmp, $fullPath);

    // DB relative path
$resultFiles[] = "/uploads/sellers/$user_id/services/additional/$today/$filename";
}

echo json_encode([
    "success" => true,
    "files" => $resultFiles
]);
?>
