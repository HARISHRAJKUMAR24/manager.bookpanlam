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
$auth = $headers['Authorization'] ?? '';

if (strpos($auth, 'Bearer ') !== 0) {
    echo json_encode(["success" => false]);
    exit;
}
$token = trim(substr($auth, 7));

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE api_token=? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}
$user_id = $user['user_id'];

/* ---------- INPUT ---------- */
$name = trim($_POST['name'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');
$upi_id = trim($_POST['upi_id'] ?? '');

if (empty($name) || empty($instructions)) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

/* ---------- UPLOAD ICON ---------- */
$iconPath = null;
if (!empty($_FILES['icon']['name']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {

    $year = date('Y');
    $month = date('m');
    $day = date('d');

    $base = dirname(__DIR__, 5)."/public/uploads/sellers/$user_id/manual_payment/$year/$month/$day/";
    if (!is_dir($base)) mkdir($base, 0777, true);

    $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
    $file = time()."_".uniqid().".".$ext;

    if (move_uploaded_file($_FILES['icon']['tmp_name'], $base.$file)) {
        $iconPath = "uploads/sellers/$user_id/manual_payment/$year/$month/$day/$file";
    }
}

/* ---------- INSERT QUERY ---------- */
$stmt = $pdo->prepare("
    INSERT INTO manual_payment_methods (user_id, name, upi_id, instructions, icon, created_at)
    VALUES (?, ?, ?, ?, ?, NOW(3))
");
$stmt->execute([
    $user_id,
    $name,
    $upi_id ?: null,
    $instructions,
    $iconPath
]);

echo json_encode([
    "success"=>true,
    "message"=>"Added",
    "id"=>$pdo->lastInsertId()
]);
