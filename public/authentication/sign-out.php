<?php

require_once '../../config/config.php';

session_start();
session_destroy();
session_unset();

header("Location: " . BASE_URL);
