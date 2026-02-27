<?php
header('Content-Type: application/json');
require_once '../../../src/database.php';
require_once '../../../src/functions.php';

try {
    $pdo = getDbConnection();

    // Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    // Get filter parameters
    $statusFilter = isset($_POST['statusFilter']) ? $_POST['statusFilter'] : '';
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';

    // Base query to get users with domain requests
    $sql = "SELECT 
                ws.id as setting_id,
                ws.custom_domain,
                ws.domain_request,
                u.user_id,
                u.name as user_name,
                u.email,
                u.site_name,
                u.site_slug
            FROM website_settings ws
            INNER JOIN users u ON ws.user_id = u.user_id
            WHERE ws.domain_request IS NOT NULL 
            AND ws.domain_request != 'null'
            AND ws.domain_request != ''";

    $countSql = "SELECT COUNT(*) as total 
                FROM website_settings ws
                INNER JOIN users u ON ws.user_id = u.user_id
                WHERE ws.domain_request IS NOT NULL 
                AND ws.domain_request != 'null'
                AND ws.domain_request != ''";

    $params = [];
    $countParams = [];

    // Apply status filter
    if (!empty($statusFilter)) {
        $sql .= " AND JSON_EXTRACT(ws.domain_request, '$.status') = :status";
        $countSql .= " AND JSON_EXTRACT(ws.domain_request, '$.status') = :status";
        $params[':status'] = $statusFilter;
        $countParams[':status'] = $statusFilter;
    }

    // Apply date range filter
    if (!empty($startDate) && !empty($endDate)) {
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        $sql .= " AND STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ws.domain_request, '$.requestedAt')), '%Y-%m-%d %H:%i:%s') BETWEEN :start_date AND :end_date";
        $countSql .= " AND STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ws.domain_request, '$.requestedAt')), '%Y-%m-%d %H:%i:%s') BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDateTime;
        $params[':end_date'] = $endDateTime;
        $countParams[':start_date'] = $startDateTime;
        $countParams[':end_date'] = $endDateTime;
    } elseif (!empty($startDate)) {
        $startDateTime = $startDate . ' 00:00:00';
        $sql .= " AND STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ws.domain_request, '$.requestedAt')), '%Y-%m-%d %H:%i:%s') >= :start_date";
        $countSql .= " AND STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ws.domain_request, '$.requestedAt')), '%Y-%m-%d %H:%i:%s') >= :start_date";
        $params[':start_date'] = $startDateTime;
        $countParams[':start_date'] = $startDateTime;
    } elseif (!empty($endDate)) {
        $endDateTime = $endDate . ' 23:59:59';
        $sql .= " AND STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ws.domain_request, '$.requestedAt')), '%Y-%m-%d %H:%i:%s') <= :end_date";
        $countSql .= " AND STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ws.domain_request, '$.requestedAt')), '%Y-%m-%d %H:%i:%s') <= :end_date";
        $params[':end_date'] = $endDateTime;
        $countParams[':end_date'] = $endDateTime;
    }

    // Apply search
    if (!empty($searchValue)) {
        $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.user_id LIKE :search OR u.site_name LIKE :search OR JSON_EXTRACT(ws.domain_request, '$.domain') LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    // Get stats for cards (before filtering)
    $stats = getDomainStats($pdo);

    // Get total count for pagination
    $countStmt = $pdo->prepare($countSql);
    foreach ($countParams as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();

    // Add order by and limit
    $sql .= " ORDER BY ws.id DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($results as $row) {
        $domainRequest = json_decode($row['domain_request'] ?? '{}', true);
        $status = $domainRequest['status'] ?? 'pending';
        $requestedDomain = $domainRequest['domain'] ?? '-';
        $requestedAt = isset($domainRequest['requestedAt']) ? date('d M Y, h:i A', strtotime($domainRequest['requestedAt'])) : '-';

        // Status badge
        $statusBadge = '';
        switch ($status) {
            case 'pending':
                $statusBadge = '<span class="badge badge-light-warning fw-bold px-3 py-2">Pending</span>';
                break;
            case 'active':
                $statusBadge = '<span class="badge badge-light-success fw-bold px-3 py-2">Active</span>';
                break;
            case 'inactive':
                $statusBadge = '<span class="badge badge-light-secondary fw-bold px-3 py-2">Inactive</span>';
                break;
            case 'rejected':
                $statusBadge = '<span class="badge badge-light-danger fw-bold px-3 py-2">Rejected</span>';
                break;
            default:
                $statusBadge = '<span class="badge badge-light-info fw-bold px-3 py-2">' . ucfirst($status) . '</span>';
        }

        // User details
        $userDetails = '<div class="d-flex flex-column">' .
            '<span class="fw-bold text-dark">' . htmlspecialchars($row['user_name'] ?? 'N/A') . '</span>' .
            '<span class="text-muted fs-7">' . htmlspecialchars($row['email'] ?? '') . '</span>' .
            '</div>';

        // Site name
        $siteName = $row['site_name'] ?? 'N/A';

        // Requested Domain with Copy Icon - FIXED
        $domainForJs = htmlspecialchars($requestedDomain, ENT_QUOTES, 'UTF-8');
        $requestedDomainHtml = '<div class="d-flex align-items-center">' .
            '<span class="fw-bold text-primary domain-text" data-domain="' . $domainForJs . '" style="cursor: pointer;" data-bs-toggle="tooltip" title="Click to copy">' . 
            htmlspecialchars($requestedDomain) . 
            '</span>' .
            '<i class="ki-duotone ki-copy fs-2 text-primary ms-2 copy-domain-icon" style="cursor: pointer;" data-domain="' . $domainForJs . '" data-bs-toggle="tooltip" title="Copy to clipboard"></i>' .
            '</div>';

        // Actions dropdown with status changer
        $actions = '
        <div class="d-flex justify-content-end">
            <select class="form-select form-select-sm form-select-solid status-changer" 
                    style="width: 130px;"
                    data-id="' . $row['setting_id'] . '"
                    data-user-id="' . $row['user_id'] . '"
                    data-domain="' . $domainForJs . '"
                    data-current-status="' . $status . '">
                <option value="pending" ' . ($status == 'pending' ? 'selected' : '') . '>Pending</option>
                <option value="active" ' . ($status == 'active' ? 'selected' : '') . '>Active</option>
                <option value="inactive" ' . ($status == 'inactive' ? 'selected' : '') . '>Inactive</option>
                <option value="rejected" ' . ($status == 'rejected' ? 'selected' : '') . '>Rejected</option>
            </select>
        </div>';

        $data[] = [
            "user_id" => '<span class="fw-bold text-dark">#' . $row['user_id'] . '</span>',
            "user_details" => $userDetails,
            "site_name" => '<span class="fw-semibold">' . htmlspecialchars($siteName) . '</span>',
            "requested_domain" => $requestedDomainHtml,
            "requested_date" => $requestedAt,
            "status" => $statusBadge,
            "actions" => $actions
        ];
    }

    $response = [
        "draw" => $draw,
        "recordsTotal" => intval($totalRecords),
        "recordsFiltered" => intval($totalRecords),
        "data" => $data,
        "stats" => $stats
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "stats" => ['total' => 0, 'pending' => 0, 'active' => 0, 'inactive' => 0, 'rejected' => 0, 'other' => 0],
        "error" => $e->getMessage()
    ]);
}