<?php
// manager.bookpanlam/public/customers/payment/cleanup-pending.php

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$db = getDbConnection();

// Delete pending payments older than 24 hours
$deleteStmt = $db->prepare("
    DELETE FROM pending_payments 
    WHERE status = 'initiated' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$deleteStmt->execute();

echo "Cleanup completed: " . $deleteStmt->rowCount() . " records deleted";
?>