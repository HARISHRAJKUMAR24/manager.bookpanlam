<?php
session_start();

if (!isset($_SESSION['manager_logged_in'])) {
    die("Unauthorized");
}
