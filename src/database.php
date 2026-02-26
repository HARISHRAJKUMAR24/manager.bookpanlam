<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

function isAdmin()
{
    $user = fetchCurrentUser();
    return $user->role === 'admin' ? true : false;
}

function fetchManagerByEmail($email)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM managers WHERE email = ?");
    $stmt->execute([$email]);

    return $stmt->fetchObject();
}

function fetchCurrentUser()
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM managers WHERE id = ?");
    $stmt->execute([$_SESSION['SESSION_ID']]);

    return $stmt->fetchObject();
}

function fetchUsers($limit, $offset, $searchValue, $conditions = [])
{
    $pdo = getDbConnection();

    // Prepare the base SQL query with JOIN to subscription_plans
    $sql = "SELECT 
                u.id, 
                u.user_id, 
                u.name, 
                u.email, 
                u.phone, 
                u.country, 
                u.image, 
                u.site_name, 
                u.site_slug, 
                u.created_at, 
                u.expires_on, 
                u.is_suspended,
                u.plan_id,
                COALESCE(sp.name, 'No Plan') as plan_name,
                s.logo, 
                s.favicon, 
                s.currency
            FROM users u
            LEFT JOIN site_settings s ON u.id = s.user_id
            LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
            WHERE 1=1";

    $params = [];
    
    // Handle is_suspended condition
    if (isset($conditions['is_suspended']) && $conditions['is_suspended'] !== '') {
        $sql .= " AND u.is_suspended = :is_suspended";
        $params[':is_suspended'] = $conditions['is_suspended'];
    }
    
    // Handle plan_id condition - including NULL for "No Plan"
    // FIX: Check if it's set and not empty string, but allow "NULL" as a valid value
    if (isset($conditions['plan_id']) && $conditions['plan_id'] !== '') {
        if ($conditions['plan_id'] === 'no_plan') {
            // Special case for "No Plan" - users with null plan_id
            $sql .= " AND u.plan_id IS NULL";
        } else {
            $sql .= " AND u.plan_id = :plan_id";
            $params[':plan_id'] = $conditions['plan_id'];
        }
    }

    // Add search value conditions
    if (!empty($searchValue)) {
        $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.user_id LIKE :search OR u.site_name LIKE :search OR u.site_slug LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    // Add limit and offset
    $sql .= " ORDER BY u.id DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind parameters dynamically
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind limit and offset
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalUserRecords()
{
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $totalRecords = $stmt->fetchColumn();
    return $totalRecords;
}

function isUserExists($userId)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        return true;
    } else {
        return false;
    }
}

function fetchUserById($userId, $column = "")
{
    $pdo = getDbConnection();

    $sql = "SELECT 
    u.id, 
    u.user_id, 
    u.name, 
    u.email, 
    u.phone, 
    u.country, 
    u.image, 
    u.site_name, 
    u.site_slug, 
    u.created_at, 
    u.expires_on, 
    u.is_suspended, 
    s.logo, 
    s.favicon, 
    s.currency,
    s.country
FROM users u
LEFT JOIN site_settings s ON u.id = s.user_id
WHERE 1=1 AND ";

    $column === "user_id" ? $sql .= "u.user_id = :id" : $sql .= "u.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $userId]);

    return $stmt->fetchObject();
}

function getUserEarnings($userId)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM appointments WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();

    return $result[0];
}

function getUserAppointments($userId)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();

    return $count;
}

function fetchSubscriptionPlans()
{
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM subscription_plans");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSettings()
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM settings");
    $stmt->execute();

    return $stmt->fetchObject();
}

function togglePlanIsDisabled($id)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE subscription_plans SET is_disabled = !is_disabled WHERE id = ?");
    $stmt->execute([$id]);
}

function isPlanExists($plan_id)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscription_plans WHERE plan_id = ?");
    $stmt->execute([$plan_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        return true;
    } else {
        return false;
    }
}

function fetchPlan($plan_id)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE plan_id = ?");
    $stmt->execute([$plan_id]);

    return $stmt->fetchObject();
}

function addPlan(
    $name,
    $amount,
    $previous_amount,
    $duration,
    $description,
    $feature_lists,
    $appointments_limit,
    $customers_limit,
    $services_limit,
    $menu_limit,
    $coupons_limit,
    $manual_payment_methods_limit,
    $upi_payment_methods_limit,
    $free_credits,
    $razorpay,
    $phonepe,
    $payu,
    $gst_type
) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO subscription_plans
    (plan_id, name, amount, previous_amount, duration, description, feature_lists, 
     appointments_limit, customers_limit, services_limit, menu_limit, 
     coupons_limit, manual_payment_methods_limit, upi_payment_methods_limit, free_credits, razorpay, phonepe, payu, gst_type)
    VALUES
    (:plan_id, :name, :amount, :previous_amount, :duration, :description, :feature_lists, 
     :appointments_limit, :customers_limit, :services_limit, :menu_limit, 
     :coupons_limit, :manual_payment_methods_limit, :free_credits, :razorpay, :phonepe, :payu, :gst_type)");

    $stmt->execute([
        'plan_id' => uuid(),
        'name' => $name,
        'amount' => $amount,
        'previous_amount' => $previous_amount,
        'duration' => $duration,
        'description' => $description,
        'feature_lists' => $feature_lists,
        'appointments_limit' => $appointments_limit,
        'customers_limit' => $customers_limit,
        'services_limit' => $services_limit,
        'menu_limit' => $menu_limit,
        'coupons_limit' => $coupons_limit,
        'manual_payment_methods_limit' => $manual_payment_methods_limit,
        'upi_payment_methods_limit' => $upi_payment_methods_limit,
        'free_credits' => $free_credits,
        'razorpay' => $razorpay,
        'phonepe' => $phonepe,
        'payu' => $payu,
        'gst_type' => $gst_type
    ]);
}

function updatePlan(
    $plan_id,
    $name,
    $amount,
    $previous_amount,
    $duration,
    $description,
    $feature_lists,
    $appointments_limit,
    $customers_limit,
    $services_limit,
    $menu_limit,
    $coupons_limit,
    $manual_payment_methods_limit,
    $upi_payment_methods_limit,
    $free_credits,
    $razorpay,
    $phonepe,
    $payu,
    $gst_type
) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE subscription_plans SET 
        name = :name,
        amount = :amount,
        previous_amount = :previous_amount,
        duration = :duration,
        description = :description,
        feature_lists = :feature_lists,
        appointments_limit = :appointments_limit,
        customers_limit = :customers_limit,
        services_limit = :services_limit,
        menu_limit = :menu_limit,
        coupons_limit = :coupons_limit,
        manual_payment_methods_limit = :manual_payment_methods_limit,
        upi_payment_methods_limit = :upi_payment_methods_limit,
        free_credits = :free_credits,
        razorpay = :razorpay,
        phonepe = :phonepe,
        payu = :payu,
        gst_type = :gst_type
    WHERE plan_id = :plan_id");

    $stmt->execute([
        'plan_id' => $plan_id,
        'name' => $name,
        'amount' => $amount,
        'previous_amount' => $previous_amount,
        'duration' => $duration,
        'description' => $description,
        'feature_lists' => $feature_lists,
        'appointments_limit' => $appointments_limit,
        'customers_limit' => $customers_limit,
        'services_limit' => $services_limit,
        'menu_limit' => $menu_limit,
        'coupons_limit' => $coupons_limit,
        'manual_payment_methods_limit' => $manual_payment_methods_limit,
        'upi_payment_methods_limit' => $upi_payment_methods_limit,
        'free_credits' => $free_credits,
        'razorpay' => $razorpay,
        'phonepe' => $phonepe,
        'payu' => $payu,
        'gst_type' => $gst_type
    ]);
}

// Add this function to your existing database functions
function fetchSubscriptionHistories($limit, $offset, $searchValue = '', $conditions = [])
{
    $pdo = getDbConnection();

    $sql = "SELECT 
                sh.*,
                sp.name as plan_name,
                u.name as customer_name,
                u.email as customer_email,
                u.phone as customer_phone,
                u.user_id
            FROM subscription_histories sh
            LEFT JOIN subscription_plans sp ON sh.plan_id = sp.id
            LEFT JOIN users u ON sh.user_id = u.id
            WHERE 1=1";

    $params = [];

    // Apply filters
    if (!empty($conditions['plan_id'])) {
        $sql .= " AND sh.plan_id = :plan_id";
        $params[':plan_id'] = $conditions['plan_id'];
    }

    if (isset($conditions['gst_status'])) {
        if ($conditions['gst_status'] === 'yes') {
            $sql .= " AND (sh.gst_number IS NOT NULL AND sh.gst_number != '')";
        } elseif ($conditions['gst_status'] === 'no') {
            $sql .= " AND (sh.gst_number IS NULL OR sh.gst_number = '')";
        }
    }

    if (!empty($conditions['payment_method'])) {
        if ($conditions['payment_method'] === 'manual') {
            // Show all manual payments (those starting with MP_)
            $sql .= " AND sh.payment_method LIKE 'MP_%'";
        } else {
            $sql .= " AND sh.payment_method = :payment_method";
            $params[':payment_method'] = $conditions['payment_method'];
        }
    }

    // ADD THIS - Status filter
    if (!empty($conditions['status'])) {
        $sql .= " AND sh.status = :status";
        $params[':status'] = $conditions['status'];
    }

    if (!empty($conditions['start_date'])) {
        $sql .= " AND DATE(sh.created_at) >= :start_date";
        $params[':start_date'] = $conditions['start_date'];
    }

    if (!empty($conditions['end_date'])) {
        $sql .= " AND DATE(sh.created_at) <= :end_date";
        $params[':end_date'] = $conditions['end_date'];
    }

    // Apply search
    if (!empty($searchValue)) {
        $sql .= " AND (
                    sh.invoice_number LIKE :search OR
                    sh.payment_id LIKE :search OR
                    sh.name LIKE :search OR
                    sh.email LIKE :search OR
                    sh.phone LIKE :search OR
                    sp.name LIKE :search OR
                    sh.payment_method LIKE :search
                )";
        $params[':search'] = "%$searchValue%";
    }

    $sql .= " ORDER BY sh.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalSubscriptionHistoryRecords()
{
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM subscription_histories");
    return $stmt->fetchColumn();
}

// KEEP THIS VERSION ONLY (the one that includes manual payment filtering)
function getFilteredSubscriptionHistoryRecords($searchValue = '', $conditions = [])
{
    $pdo = getDbConnection();

    $sql = "SELECT COUNT(*) as count 
            FROM subscription_histories sh
            LEFT JOIN subscription_plans sp ON sh.plan_id = sp.id
            LEFT JOIN users u ON sh.user_id = u.id
            WHERE 1=1";

    $params = [];

    // Apply same filters as fetch function
    if (!empty($conditions['plan_id'])) {
        $sql .= " AND sh.plan_id = :plan_id";
        $params[':plan_id'] = $conditions['plan_id'];
    }

    if (isset($conditions['gst_status'])) {
        if ($conditions['gst_status'] === 'yes') {
            $sql .= " AND (sh.gst_number IS NOT NULL AND sh.gst_number != '')";
        } elseif ($conditions['gst_status'] === 'no') {
            $sql .= " AND (sh.gst_number IS NULL OR sh.gst_number = '')";
        }
    }

    if (!empty($conditions['payment_method'])) {
        if ($conditions['payment_method'] === 'manual') {
            $sql .= " AND sh.payment_method LIKE 'MP_%'";
        } else {
            $sql .= " AND sh.payment_method = :payment_method";
            $params[':payment_method'] = $conditions['payment_method'];
        }
    }

    // Status filter - already present in your code
    if (!empty($conditions['status'])) {
        $sql .= " AND sh.status = :status";
        $params[':status'] = $conditions['status'];
    }

    if (!empty($conditions['start_date'])) {
        $sql .= " AND DATE(sh.created_at) >= :start_date";
        $params[':start_date'] = $conditions['start_date'];
    }

    if (!empty($conditions['end_date'])) {
        $sql .= " AND DATE(sh.created_at) <= :end_date";
        $params[':end_date'] = $conditions['end_date'];
    }

    if (!empty($searchValue)) {
        $sql .= " AND (
                    sh.invoice_number LIKE :search OR
                    sh.payment_id LIKE :search OR
                    sh.name LIKE :search OR
                    sh.email LIKE :search OR
                    sh.phone LIKE :search OR
                    sp.name LIKE :search OR
                    sh.payment_method LIKE :search
                )";
        $params[':search'] = "%$searchValue%";
    }

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    return $stmt->fetchColumn();
}
function fetchManagers($limit, $offset, $searchValue, $conditions = [])
{
    $pdo = getDbConnection();

    // Prepare the base SQL query with a JOIN
    $sql = "SELECT * FROM managers WHERE 1=1"; // Base condition

    // Add conditions from the associative array
    $params = [];
    foreach ($conditions as $key => $value) {
        if (!empty($value)) { // Check if value is not empty
            $sql .= " AND $key = :$key"; // Use $key directly
            $params[":$key"] = $value; // Prepare parameter binding
        }
    }

    // Add search value conditions
    if (!empty($searchValue)) {
        $sql .= " AND (name LIKE :search OR email LIKE :search OR manager_id LIKE :search)";
        $params[':search'] = "%$searchValue%"; // Prepare search parameter
    }

    // Add limit and offset
    $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind parameters dynamically
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind limit and offset (using bindValue)
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as an associative array

    return $data;
}

function getTotalManagers()
{
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM managers");
    $totalRecords = $stmt->fetchColumn();
    return $totalRecords;
}

function addManager($name, $email, $password)
{

    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO managers (manager_id, name, email, password, image, role) VALUES (:manager_id, :name, :email, :password, :image, :role)");

    $stmt->execute([
        'manager_id' => rand(1111, 9999),
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'image' => 'static/user.png',
        'role' => 'staff'
    ]);
}

function isManagerEmailExists($email)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM managers WHERE email = ?");
    $stmt->execute([$email]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        return true;
    } else {
        return false;
    }
}

function deleteManagerById($id)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM managers WHERE manager_id = :manager_id");

    $stmt->execute([
        'manager_id' => $id
    ]);
}

function fetchDiscounts($limit, $offset, $searchValue, $conditions = [])
{
    $pdo = getDbConnection();

    // Prepare the base SQL query with a JOIN
    $sql = "SELECT * FROM discounts WHERE 1=1"; // Base condition

    // Add conditions from the associative array
    $params = [];
    foreach ($conditions as $key => $value) {
        if (!empty($value)) { // Check if value is not empty
            $sql .= " AND $key = :$key"; // Use $key directly
            $params[":$key"] = $value; // Prepare parameter binding
        }
    }

    // Add search value conditions
    if (!empty($searchValue)) {
        $sql .= " AND (code LIKE :search OR type LIKE :search OR discount LIKE :search)";
        $params[':search'] = "%$searchValue%"; // Prepare search parameter
    }

    // Add limit and offset
    $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind parameters dynamically
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind limit and offset (using bindValue)
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as an associative array

    return $data;
}

function getTotalDiscounts()
{
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM discounts");
    $totalRecords = $stmt->fetchColumn();
    return $totalRecords;
}

function deleteDiscountById($id)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM discounts WHERE id = :id");

    $stmt->execute([
        'id' => $id
    ]);
}

function isDiscountCodeExists($code)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM discounts WHERE code = ?");
    $stmt->execute([$code]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        return true;
    } else {
        return false;
    }
}

function addDiscount($code, $type, $discount, $eligibility)
{

    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO discounts (code, type, discount, eligibility) VALUES (:code, :type, :discount, :eligibility)");

    $stmt->execute([
        'code' => $code,
        'type' => $type,
        'discount' => $discount,
        'eligibility' => $eligibility,
    ]);
}
