<?php
/* =========================================================
   HEADERS
========================================================= */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow DELETE method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

/* =========================================================
   CONFIG + DB
========================================================= */
require_once "../../../../config/config.php";
require_once "../../../../src/database.php";

$pdo = getDbConnection();

/* =========================================================
   1️⃣ READ TOKEN
========================================================= */
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';

if (strpos($auth, 'Bearer ') !== 0) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$token = trim(substr($auth, 7));

/* =========================================================
   2️⃣ FETCH USER
========================================================= */
$stmt = $pdo->prepare("
    SELECT user_id
    FROM users
    WHERE api_token = ?
    LIMIT 1
");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$user_id = (int)$user['user_id'];

/* =========================================================
   3️⃣ READ REQUEST BODY
========================================================= */
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['path'])) {
    echo json_encode([
        "success" => false,
        "message" => "No banner path provided"
    ]);
    exit;
}

$bannerPath = $input['path'];

/* =========================================================
   4️⃣ VALIDATE PATH (Security check)
========================================================= */
// Expected format from frontend: "seller/{user_id}/website-settings/homepage/banners/Y/m/d/filename"
// But actual files are stored in: "sellers/{user_id}/website-settings/homepage/banners/Y/m/d/filename"

// First, convert the path from frontend format to actual storage format
// Replace "seller/" with "sellers/"
if (strpos($bannerPath, 'seller/') === 0) {
    $bannerPath = 'sellers/' . substr($bannerPath, 7); // Remove 'seller/' and add 'sellers/'
}

// Now extract user_id from the converted path
$pathParts = explode('/', $bannerPath);

// Check if path starts with "sellers"
if ($pathParts[0] !== 'sellers') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid banner path format. Expected to start with 'sellers/'"
    ]);
    exit;
}

// Get user_id from path (second part)
$pathUserId = isset($pathParts[1]) ? (int)$pathParts[1] : 0;

// Verify the banner belongs to the authenticated user
if ($pathUserId !== $user_id) {
    echo json_encode([
        "success" => false,
        "message" => "You don't have permission to delete this banner"
    ]);
    exit;
}

/* =========================================================
   5️⃣ DELETE THE FILE
========================================================= */
$uploadsBase = realpath(__DIR__ . "/../../../../public/uploads");
$fullPath = $uploadsBase . '/' . $bannerPath;

// Security: Make sure the path is within uploads directory
$realFullPath = realpath($fullPath);
if ($realFullPath === false) {
    // If realpath fails, try without realpath validation for files that don't exist yet
    if (file_exists($fullPath)) {
        $realFullPath = $fullPath;
    } else {
        echo json_encode([
            "success" => false,
            "message" => "File not found: " . $bannerPath
        ]);
        exit;
    }
}

// Double-check the path is within uploads directory
if (strpos($realFullPath, $uploadsBase) !== 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid file path - security violation"
    ]);
    exit;
}

// Check if file exists
if (!file_exists($realFullPath)) {
    echo json_encode([
        "success" => false,
        "message" => "Banner file not found"
    ]);
    exit;
}

// Delete the file
if (!unlink($realFullPath)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to delete banner file"
    ]);
    exit;
}

/* =========================================================
   6️⃣ OPTIONAL: Clean up empty directories
========================================================= */
function removeEmptyDirs($path) {
    if (!is_dir($path)) return false;
    
    $files = scandir($path);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        return false; // Directory not empty, stop
    }
    
    // Directory is empty, remove it
    rmdir($path);
    return true;
}

// Get the directory of the deleted file and try to clean up if empty
$fileDir = dirname($realFullPath);
removeEmptyDirs($fileDir);

// Also try to clean up parent directories (month, year)
$parentDir = dirname($fileDir);
removeEmptyDirs($parentDir);
$grandParentDir = dirname($parentDir);
removeEmptyDirs($grandParentDir);

/* =========================================================
   7️⃣ RESPONSE
========================================================= */
echo json_encode([
    "success" => true,
    "message" => "Banner deleted successfully"
]);
exit;