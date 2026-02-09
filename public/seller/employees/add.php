<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

/* ----------------------------------------------------
   1️⃣ READ TOKEN (JSON + COOKIE)
---------------------------------------------------- */
$raw = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];


file_put_contents("debug_employee.txt", print_r($data, true));

$token =
    ($data["token"] ?? null)    // token from JSON
    ?: ($_COOKIE["token"] ?? ""); // token from cookie

if (!$token) {
    echo json_encode(["success" => false, "message" => "Unauthorized: Missing token"]);
    exit;
}

/* ----------------------------------------------------
   2️⃣ FETCH USER USING TOKEN
---------------------------------------------------- */
$stmt = $pdo->prepare("SELECT id, user_id FROM users WHERE api_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetchObject();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid token"]);
    exit;
}

// ⭐ FIX → employees.user_id must match users.user_id
$seller_id = $user->user_id;

/* ----------------------------------------------------
   3️⃣ VALIDATE REQUIRED FIELDS
---------------------------------------------------- */
$required = [ "name", "phone"];
foreach ($required as $field) {
    if (empty(trim($data[$field] ?? ""))) {
        echo json_encode([
            "success" => false,
            "message" => "Missing required field: $field"
        ]);
        exit;
    }
}

/* ----------------------------------------------------
   4️⃣ EXTRACT FIELDS
---------------------------------------------------- */
// Auto-generate employee ID if not provided
if (empty($data["employee_id"])) {
    $employee_id = "EMP-" . strtoupper(substr(md5(uniqid()), 0, 8));
} else {
    $employee_id = $data["employee_id"];
}
$name         = $data["name"];
$position     = $data["position"] ?? null;
$email        = $data["email"] ?? null;
$phone        = $data["phone"];
$address      = $data["address"] ?? null;
$image        = $data["image"] ?? null;

$joining_date = !empty($data["joining_date"])
    ? date("Y-m-d", strtotime($data["joining_date"]))
    : null;

/* ----------------------------------------------------
   5️⃣ INSERT WITH CORRECT SELLER ID
---------------------------------------------------- */
$stmt = $pdo->prepare("
    INSERT INTO employees 
    (employee_id, user_id, name, position, email, phone, address, joining_date, image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$ok = $stmt->execute([
    $employee_id,
    $seller_id,    // ⭐ FOREIGN KEY FIX
    $name,
    $position,
    $email,
    $phone,
    $address,
    $joining_date,
    $image
]);

if ($ok) {
    echo json_encode([
        "success" => true,
        "message" => "Employee added successfully"
    ]);
    exit;
}

$error = $stmt->errorInfo();
echo json_encode([
    "success" => false,
    "message" => $error[2] ?? "Database error"
]);
exit;

?>
