<?php
header('Content-Type: application/json');

require_once '../../../src/database.php';

$limit = isset($_POST['length']) ? (int)$_POST['length'] : 10;
$offset = isset($_POST['start']) ? (int)$_POST['start'] : 0;
$searchValue = isset($_POST['search']) ? $_POST['search'] : '';

// Fetch data
$conditions = [];
$users = fetchDiscounts($limit, $offset, $searchValue, $conditions);
$totalRecords = getTotalDiscounts();

$data = array();

foreach ($users as $row) {
    $data[] = [
        "id" => '<div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="' . $row['id'] . '" /></div>',
        "code" => $row['code'],
        "type" => $row['type'],
        "discount" => $row['discount'],
        "created_at" => $row['created_at'],

        'actions' => '
<span data-id="' . $row['id'] . '" class="deleteCode cursor-pointer">
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
