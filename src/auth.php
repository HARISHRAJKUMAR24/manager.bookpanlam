<?php
function getAuthenticatedUser(PDO $pdo) {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';

    if (!$auth || !str_starts_with($auth, 'Bearer ')) {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    $token = substr($auth, 7);

    $stmt = $pdo->prepare("
        SELECT id, user_id 
        FROM users 
        WHERE api_token = ? 
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "Invalid token"]);
        exit;
    }

    return $user; // ['id' => users.id, 'user_id' => public id]
}
