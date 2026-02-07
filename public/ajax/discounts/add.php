<?php

require_once '../../../src/database.php';

$code = $_POST['code'];
$type = $_POST['type'];
$discount = $_POST['discount'];
$eligibility = $_POST['eligibility'] === "" ? NULL : $_POST['eligibility'];

if (!$code === "") exit(json_encode(["type" => "error", "msg" => "Code is required"]));
if (!$type === "") exit(json_encode(["type" => "error", "msg" => "Type is required"]));
if (!$discount === "") exit(json_encode(["type" => "error", "msg" => "Discount is required"]));
if (isDiscountCodeExists($code)) exit(json_encode(["type" => "error", "msg" => "Code is already exists"]));

addDiscount(
    $code,
    $type,
    $discount,
    $eligibility
);

exit(json_encode(["type" => "success", "msg" => "Action applied successfully!"]));
