<?php

require_once '../../../src/database.php';

$planId = $_POST['planId'];

togglePlanIsDisabled($planId);
