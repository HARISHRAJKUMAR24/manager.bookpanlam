<?php
header('Content-Type: application/json');

require_once '../../../src/database.php';

$limit = isset($_POST['length']) ? (int)$_POST['length'] : 10;
$offset = isset($_POST['start']) ? (int)$_POST['start'] : 0;
$searchValue = isset($_POST['search']) ? $_POST['search'] : '';

// Fetch data
$conditions = [
    "role" => "staff"
];
$users = fetchManagers($limit, $offset, $searchValue, $conditions);
$totalRecords = getTotalManagers();

$data = array();

foreach ($users as $row) {
    $data[] = [
        "id" => '<div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="' . $row['id'] . '" /></div>',
        "staff_id" => $row['manager_id'],

        "staff" => '<div class="d-flex align-items-center"><!--begin:: Avatar -->
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
        "created_at" => $row['created_at'],

        'actions' => '
        <span data-id="' . $row['manager_id'] . '" class="deleteStaff text-end d-block cursor-pointer">
        <i class="ki-duotone ki-delete-folder fs-2x text-danger">
<span class="path1"></span>
<span class="path2"></span>
<span class="path3"></span>
</i>
        </span>',
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
