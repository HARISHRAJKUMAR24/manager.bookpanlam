<?php
session_start();

require_once '../../../src/database.php';

$input = $_POST;

$data = fetchManagerByEmail($input['email']);

if ($data) {
    if (password_verify($input['password'], $data->password)) {
        $_SESSION['SESSION_ID'] = $data->id;
        $_SESSION['SESSION_EMAIL'] = $data->email;

        echo true;
    } else {
        // Incorrect password
        echo false;
    }
} else {
    // Incorrect email
    echo false;
}
