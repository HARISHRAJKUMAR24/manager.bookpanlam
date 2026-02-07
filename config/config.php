<?php

define('BASE_URL', 'http://localhost/manager.bookpanlam/');
define('UPLOADS_URL', 'uploads/');
define('USER_PORTAL_URL', 'http://localhost/app.bookpannu.com/');
// define('');

define('DB_HOST', 'localhost');
define('DB_NAME', 'admin_bookpannu');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDbConnection()
{
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
