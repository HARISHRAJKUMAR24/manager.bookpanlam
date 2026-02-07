<?php
// Generate random token
function token()
{
    return md5(rand() . time());
}
// Including autoload
// require_once __DIR__ . '/../config/autoload.php';

if (isset($_POST['id'])) {
    $token = token();

    $sql = "UPDATE users SET direct_login = :direct_login WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt = $stmt->execute([
        "id" => $_POST['id'],
        "direct_login" => $token
    ]);

    if ($stmt) {
        // echo json_encode(array("message" => SELLER_URL . "login?t=$token", "success" => true));
    }
}
