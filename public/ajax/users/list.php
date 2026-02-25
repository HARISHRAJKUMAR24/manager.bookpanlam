<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once '../../../src/functions.php';
    require_once '../../../src/database.php';

    $limit = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $offset = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $searchValue = isset($_POST['search']) ? $_POST['search'] : '';

    $isSuspended = isset($_POST['isSuspended']) ? $_POST['isSuspended'] : '';
    $plan_id = isset($_POST['planId']) ? $_POST['planId'] : '';

    // Fetch data
    $conditions = [
        "is_suspended" => $isSuspended,
        "plan_id" => $plan_id,
    ];
    
    $users = fetchUsers($limit, $offset, $searchValue, $conditions);
    $totalRecords = getTotalUserRecords();
    $filteredRecords = getFilteredUserRecords($searchValue, $conditions);

    $data = array();

    foreach ($users as $row) {
        // Format plan name with appropriate badge color
        $planName = $row['plan_name'] ?? 'No Plan';
        $badgeClass = $planName === 'No Plan' ? 'badge-warning' : 'badge-light-primary';
        $planBadge = '<span class="badge ' . $badgeClass . ' fw-bold px-3 py-2">' . htmlspecialchars($planName) . '</span>';
        
        // Format expires_on date
        $expiresOn = !empty($row['expires_on']) && $row['expires_on'] != '0000-00-00 00:00:00' 
            ? date('d M Y', strtotime($row['expires_on'])) 
            : '<span class="badge badge-light-secondary">Never</span>';
        
        $data[] = [
            "id" => '<div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="' . $row['id'] . '" /></div>',
            "user_id" => '<span class="fw-bold text-dark">#' . $row['user_id'] . '</span>',

            "user" => '<div class="d-flex align-items-center">
            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                <a href="#">
                    <div class="symbol-label">
                        <img src="' . UPLOADS_URL . $row['image'] . '" alt="' . $row['name'] . '" class="w-100" onerror="this.src=\'assets/media/avatars/blank.png\'" />
                    </div>
                </a>
            </div>
            <div class="d-flex flex-column">
                <a href="#" class="text-gray-800 text-hover-primary mb-1">' . htmlspecialchars($row['name']) . '</a>
                <span class="text-muted fs-7">' . htmlspecialchars($row['email']) . '</span>
            </div></div>',

            "site" => '<div class="d-flex align-items-center">
            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                <a href="#">
                    <div class="symbol-label">
                        <img src="' . UPLOADS_URL . $row['favicon'] . '" alt="' . $row['site_name'] . '" class="w-100" onerror="this.src=\'assets/media/avatars/blank.png\'" />
                    </div>
                </a>
            </div>
            <div class="d-flex flex-column">
                <a href="#" class="text-gray-800 text-hover-primary mb-1">' . htmlspecialchars($row['site_name']) . '</a>
                <span class="text-muted fs-7">/' . htmlspecialchars($row['site_slug']) . '</span>
            </div></div>',

            "plan" => $planBadge,
            "expires_on" => $expiresOn,
            "is_suspended" => '<div class="badge ' . ($row['is_suspended'] ? 'badge-danger' : 'badge-success') . ' fw-bold">' . ($row['is_suspended'] ? 'Suspended' : 'Active') . '</div>',

            'actions' => '
            <a href="users/' . $row['user_id'] . '" class="btn btn-sm btn-light btn-active-light-primary">
                <i class="ki-duotone ki-eye fs-2 text-primary">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
            </a>',
        ];
    }

    // Prepare response
    $response = [
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $filteredRecords,
        "data" => $data
    ];

    // Return JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    // Return error as JSON
    echo json_encode([
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $e->getMessage()
    ]);
}