<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ===============================
   READ INPUT (FIXED)
================================ */
$input = $_POST;

if (empty($input)) {
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON input"
        ]);
        exit;
    }
}

/* ===============================
   VALIDATE INPUT
================================ */
$phone = trim($input['phone'] ?? "");
$password = trim($input['password'] ?? "");

if ($phone === "" || $password === "") {
    echo json_encode([
        "success" => false,
        "message" => "Phone and password required"
    ]);
    exit;
}

/* ===============================
   FETCH USER
================================ */
$stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

/* ===============================
   VERIFY PASSWORD
================================ */
if (!password_verify($password, $user->password)) {
    echo json_encode([
        "success" => false,
        "message" => "Incorrect password"
    ]);
    exit;
}

/* ===============================
   GENERATE TOKEN
================================ */
$token = bin2hex(random_bytes(32));

$update = $pdo->prepare(
    "UPDATE users SET api_token = ? WHERE id = ?"
);
$update->execute([$token, $user->id]);

/* ===============================
   SUCCESS RESPONSE (STABLE)
================================ */
echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "token" => $token,
    "user" => [
        "db_id"    => (int) $user->id,       // internal DB id
        "user_id"  => (int) $user->user_id,  // public seller id (IMPORTANT)
        "name"     => $user->name,
        "email"    => $user->email,
        "phone"    => $user->phone,
        "site_slug"=> $user->site_slug,
        "country"  => $user->country,
    ]
]);

exit;
