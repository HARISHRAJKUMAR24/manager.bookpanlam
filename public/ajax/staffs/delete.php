<?php

require_once '../../../src/database.php';

$id = $_POST['id'];

deleteManagerById($id);
