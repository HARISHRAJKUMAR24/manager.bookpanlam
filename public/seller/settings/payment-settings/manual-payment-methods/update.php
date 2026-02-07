<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 5) . "/src/database.php";
$pdo = getDbConnection();

/* ---------- AUTH ---------- */
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$token = trim(substr($authHeader, 7));

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = (int)$user['user_id'];

/* ---------- INPUT ---------- */
$id = (int)($_GET['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');
$upi_id = trim($_POST['upi_id'] ?? '');

if (!$id || empty($name) || empty($instructions)) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

/* ---------- RECORD CHECK ---------- */
$check = $pdo->prepare("SELECT icon FROM manual_payment_methods WHERE id = ? AND user_id = ?");
$check->execute([$id, $user_id]);
$record = $check->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    echo json_encode(["success" => false, "message" => "Not found"]);
    exit;
}

/* ---------- PATHS ---------- */
$year = date('Y');
$month = date('m');
$day = date('d');

$uploadRoot = dirname(__DIR__, 5) . "/public/uploads/sellers/$user_id/manual_payment/$year/$month/$day/";

if (!is_dir($uploadRoot)) {
    mkdir($uploadRoot, 0777, true);
}

$oldIcon = $record['icon'] ?? null;
$iconPath = null;

/***********************************
 *   1️⃣ REMOVE ICON COMPLETELY
 ***********************************/
$remove = (!empty($_POST['remove_icon']) && $_POST['remove_icon'] == "1");

if ($remove && $oldIcon) {
    $oldFile = dirname(__DIR__, 5) . "/public/" . ltrim($oldIcon, "/");
    if (file_exists($oldFile)) unlink($oldFile);
    $iconPath = "__REMOVE__";
}

/***********************************
 *   2️⃣ UPLOAD NEW ICON (REPLACES OLD)
 ***********************************/
if (!empty($_FILES['icon']['name']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {

    // Delete old file
    if ($oldIcon) {
        $oldFile = dirname(__DIR__, 5) . "/public/" . ltrim($oldIcon, "/");
        if (file_exists($oldFile)) unlink($oldFile);
    }

    $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
    $newName = "payment_method_" . $id . "." . $ext;

    $target = $uploadRoot . $newName;

    if (move_uploaded_file($_FILES['icon']['tmp_name'], $target)) {
        $iconPath = "uploads/sellers/$user_id/manual_payment/$year/$month/$day/$newName";
    }
}

/***********************************
 *   3️⃣ BUILD UPDATE QUERY
 ***********************************/
$sql = "UPDATE manual_payment_methods SET 
            name = ?, 
            upi_id = ?, 
            instructions = ?";

$params = [$name, $upi_id ?: null, $instructions];

// Remove image
if ($iconPath === "__REMOVE__") {
    $sql .= ", icon = NULL";
}

// Set new image
elseif ($iconPath) {
    $sql .= ", icon = ?";
    $params[] = $iconPath;
}

$sql .= " WHERE id = ? AND user_id = ?";
$params[] = $id;
$params[] = $user_id;

/***********************************
 *   4️⃣ EXECUTE
 ***********************************/
$update = $pdo->prepare($sql);
$update->execute($params);

echo json_encode([
    "success" => true,
    "message" => "Manual payment method updated successfully"
]);
