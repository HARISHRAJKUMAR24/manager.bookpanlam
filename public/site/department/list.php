<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../../config/config.php";
require_once "../../../src/database.php";

$pdo = getDbConnection();

$user_id = (int)($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Valid user_id is required"
    ]);
    exit;
}

try {
    // Fetch departments for this user - USING YOUR ACTUAL TABLE STRUCTURE
    $sql = "
        SELECT 
            id,
            department_id,
            user_id,
            name,
            slug,
            type_main_name,
            type_main_amount,
            type_main_hsn,
            type_1_name,
            type_1_amount,
            type_1_hsn,
            type_2_name,
            type_2_amount,
            type_2_hsn,
            type_3_name,
            type_3_amount,
            type_3_hsn,
            type_4_name,
            type_4_amount,
            type_4_hsn,
            type_5_name,
            type_5_amount,
            type_5_hsn,
            type_6_name,
            type_6_amount,
            type_6_hsn,
            type_7_name,
            type_7_amount,
            type_7_hsn,
            type_8_name,
            type_8_amount,
            type_8_hsn,
            type_9_name,
            type_9_amount,
            type_9_hsn,
            type_10_name,
            type_10_amount,
            type_10_hsn,
            type_11_name,
            type_11_amount,
            type_11_hsn,
            type_12_name,
            type_12_amount,
            type_12_hsn,
            type_13_name,
            type_13_amount,
            type_13_hsn,
            type_14_name,
            type_14_amount,
            type_14_hsn,
            type_15_name,
            type_15_amount,
            type_15_hsn,
            type_16_name,
            type_16_amount,
            type_16_hsn,
            type_17_name,
            type_17_amount,
            type_17_hsn,
            type_18_name,
            type_18_amount,
            type_18_hsn,
            type_19_name,
            type_19_amount,
            type_19_hsn,
            type_20_name,
            type_20_amount,
            type_20_hsn,
            type_21_name,
            type_21_amount,
            type_21_hsn,
            type_22_name,
            type_22_amount,
            type_22_hsn,
            type_23_name,
            type_23_amount,
            type_23_hsn,
            type_24_name,
            type_24_amount,
            type_24_hsn,
            type_25_name,
            type_25_amount,
            type_25_hsn,
            image,
            meta_title,
            meta_description,
            created_at,
            updated_at,
            appointment_settings,
            leave_dates,
            appointment_time_from,
            appointment_time_to
        FROM departments 
        WHERE user_id = :user_id 
        ORDER BY name ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse JSON fields
    foreach ($departments as &$dept) {
        // Parse appointment_settings
        if (isset($dept['appointment_settings']) && is_string($dept['appointment_settings'])) {
            $dept['appointment_settings'] = json_decode($dept['appointment_settings'], true);
        } else {
            $dept['appointment_settings'] = [];
        }
        
        // Parse leave_dates
        if (isset($dept['leave_dates']) && is_string($dept['leave_dates'])) {
            $dept['leave_dates'] = json_decode($dept['leave_dates'], true);
        } else {
            $dept['leave_dates'] = [];
        }
        
        // Set default consultation fee from type_main_amount
        $dept['consultation_fee'] = floatval($dept['type_main_amount'] ?? 0);
        
        // Set default token limit (adjust as needed)
        $dept['token_limit'] = 10; // Default from your data
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'records' => $departments,
        'count' => count($departments),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>