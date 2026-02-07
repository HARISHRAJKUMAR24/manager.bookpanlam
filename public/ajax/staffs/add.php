<?php

require_once '../../../src/database.php';

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

if (!$name === "") exit(json_encode(["type" => "error", "msg" => "Name is required"]));
if (!$email === "") exit(json_encode(["type" => "error", "msg" => "Email is required"]));
if (!$password === "") exit(json_encode(["type" => "error", "msg" => "Password is required"]));
if (isManagerEmailExists($email)) exit(json_encode(["type" => "error", "msg" => "Email already exists"]));

addManager(
    $name,
    $email,
    $hashed_password
);

exit(json_encode(["type" => "success", "msg" => "Action applied successfully!"]));
