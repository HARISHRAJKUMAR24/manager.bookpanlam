<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===============================
   CORS (REQUIRED)
================================ */
$allowedOrigin = "http://localhost:3001";

header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

/* Handle preflight */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


require_once "../../config/config.php";
require_once "../../src/database.php";

/* ===============================
   AUTH
================================ */
if (!isset($_COOKIE['customer_token'])) {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$payload = json_decode(base64_decode($_COOKIE['customer_token']), true);
$customerId = $payload['customer_id'] ?? null;

if (!$customerId) {
  echo json_encode(["success" => false, "message" => "Invalid token"]);
  exit;
}

$pdo = getDbConnection();

/* ===============================
   FORM DATA
================================ */
$doctorId = (int)($_POST['doctorId'] ?? 0);
$date     = $_POST['date'] ?? '';
$slot     = json_decode($_POST['slot'] ?? '{}', true);

$slotFrom = $slot['from'] ?? '';
$slotTo   = $slot['to'] ?? '';

$tokens   = (int)($_POST['token'] ?? 1);
$notes    = $_POST['notes'] ?? '';
$amount   = (float)($_POST['amount'] ?? 0);

if (!$doctorId || !$date || !$slotFrom || !$slotTo) {
  echo json_encode(["success" => false, "message" => "Missing booking data"]);
  exit;
}

/* ===============================
   INSERT DOCTOR APPOINTMENT
================================ */
$stmt = $pdo->prepare("
  INSERT INTO doctor_appointments
  (customer_id, doctor_id, appointment_date, slot_from, slot_to, tokens, notes, amount)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
  $customerId,
  $doctorId,
  date('Y-m-d', strtotime($date)),
  $slotFrom,
  $slotTo,
  $tokens,
  $notes,
  $amount
]);

$appointmentId = $pdo->lastInsertId();

/* ===============================
   FILE UPLOAD
================================ */
$uploadDir = "../../uploads/doctor-appointments/";
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}

if (isset($_FILES['files'])) {
  $files = $_FILES['files'];

  for ($i = 0; $i < count($files['name']); $i++) {
    if ($files['error'][$i] === UPLOAD_ERR_OK) {

      $originalName = $files['name'][$i];
      $tmpName      = $files['tmp_name'][$i];
      $fileType     = $files['type'][$i];
      $fileSize     = $files['size'][$i];

      $fileName = time() . "_" . basename($originalName);
      $filePath = $uploadDir . $fileName;

      if (move_uploaded_file($tmpName, $filePath)) {
        $stmt = $pdo->prepare("
          INSERT INTO doctor_appointment_files
          (doctor_appointment_id, file_name, file_path, file_type, file_size)
          VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
          $appointmentId,
          $originalName,
          $fileName,
          $fileType,
          $fileSize
        ]);
      }
    }
  }
}

echo json_encode([
  "success" => true,
  "message" => "Doctor appointment booked successfully",
  "appointment_id" => $appointmentId
]);
