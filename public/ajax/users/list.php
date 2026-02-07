<?php
header('Content-Type: application/json');

require_once '../../../src/database.php';

$limit = isset($_POST['length']) ? (int)$_POST['length'] : 10;
$offset = isset($_POST['start']) ? (int)$_POST['start'] : 0;
$searchValue = isset($_POST['search']) ? $_POST['search'] : '';

$isSuspended = isset($_POST['isSuspended']) ? $_POST['isSuspended'] : '';
$plan_id = isset($_POST['planId']) ? $_POST['planId'] : '';

// Fetch data
$conditions = [
    "is_suspended" => $isSuspended,
    "plan_id" => (int)$plan_id,
];
$users = fetchUsers($limit, $offset, $searchValue, $conditions);
$totalRecords = getTotalUserRecords();

$data = array();

foreach ($users as $row) {
    $data[] = [
        "id" => '<div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="' . $row['id'] . '" /></div>',
        "user_id" => $row['user_id'],

        "user" => '<div class="d-flex align-items-center"><!--begin:: Avatar -->
        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
            <a href="#">
                <div class="symbol-label">
                    <img src="' . UPLOADS_URL . $row['image'] . '" alt="' . $row['name'] . '" class="w-100" />
                </div>
            </a>
        </div>
        <!--end::Avatar-->
        <!--begin::User details-->
        <div class="d-flex flex-column">
            <a href="#" class="text-gray-800 text-hover-primary mb-1">' . $row['name'] . '</a>
            <span>' . $row['email'] . '</span>
        </div>
        <!--begin::User details--></div>',

        "site" => '<div class="d-flex align-items-center"><!--begin:: Logo -->
        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
            <a href="#">
                <div class="symbol-label">
                    <img src="' . UPLOADS_URL . $row['favicon'] . '" alt="' . $row['site_name'] . '" class="w-100" />
                </div>
            </a>
        </div>
        <!--end::Logo-->
        <!--begin::Site details-->
        <div class="d-flex flex-column">
            <a href="#" class="text-gray-800 text-hover-primary mb-1">' . $row['site_name'] . '</a>
            <span>/' . $row['site_slug'] . '</span>
        </div>
        <!--begin::Site details--></div>',

        "plan" => "",
        "expires_on" => '<div class="badge badge-light fw-bold">' . $row['expires_on'] . '</div>',
        "is_suspended" => '<div class="badge ' . ($row['is_suspended'] ? 'badge-danger' : 'badge-success') . ' fw-bold">' . ($row['is_suspended'] ? 'TRUE' : 'FALSE') . '</div>',

        'actions' => '
        <a href="users/' . $row['user_id'] . '" class="text-end d-block">
        <i class="ki-duotone ki-eye fs-2x text-info">
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
    "recordsFiltered" => $totalRecords, // Adjust this if you implement filtering
    "data" => $data
];

// Return JSON response
echo json_encode($response);
