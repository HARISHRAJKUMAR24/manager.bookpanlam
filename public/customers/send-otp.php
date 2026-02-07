<?php
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

echo json_encode([
  "success" => true,
  "message" => "OTP sent successfully",
  "otp" => "111111"
]);
