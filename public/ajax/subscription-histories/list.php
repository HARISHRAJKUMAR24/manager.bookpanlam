<?php
header('Content-Type: application/json');
require_once '../../../src/database.php';

try {
    // Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Your custom filters
    $planId = isset($_POST['planFilter']) ? $_POST['planFilter'] : '';
    $gstStatus = isset($_POST['gstFilter']) ? $_POST['gstFilter'] : '';
    $paymentMethod = isset($_POST['paymentMethodFilter']) ? $_POST['paymentMethodFilter'] : '';
    $startDate = isset($_POST['startDateFilter']) ? $_POST['startDateFilter'] : '';
    $endDate = isset($_POST['endDateFilter']) ? $_POST['endDateFilter'] : '';
    $statusFilter = isset($_POST['statusFilter']) ? $_POST['statusFilter'] : '';
    
    // Prepare conditions
    $conditions = [];
    if (!empty($planId)) $conditions['plan_id'] = $planId;
    if (!empty($gstStatus)) $conditions['gst_status'] = $gstStatus;
    if (!empty($paymentMethod)) $conditions['payment_method'] = $paymentMethod;
    if (!empty($startDate)) $conditions['start_date'] = $startDate;
    if (!empty($endDate)) $conditions['end_date'] = $endDate;
    if (!empty($statusFilter)) $conditions['status'] = $statusFilter;
    
    // Fetch data
    $histories = fetchSubscriptionHistories($limit, $offset, $searchValue, $conditions);
    
    // Get totals
    $totalRecords = getTotalSubscriptionHistoryRecords();
    $filteredRecords = getFilteredSubscriptionHistoryRecords($searchValue, $conditions);
    
    // Prepare data array
    $data = [];
    
    foreach ($histories as $row) {
        // Format amount with currency
        $amountFormatted = $row['currency_symbol'] . ' ' . number_format($row['amount'], 2);
        
        // Customer info with name, phone, email - Clickable link to user
        $customerName = $row['name'] ?? 'Unknown';
        $customerPhone = $row['phone'] ?? '';
        $customerEmail = $row['email'] ?? '';
        $userId = $row['user_id'] ?? '';
        
        // Create clickable customer info
        $customerInfo = '<div class="d-flex flex-column">';
        if ($userId) {
            $customerInfo .= '<a href="users/' . $userId . '" class="text-dark fw-bold text-hover-primary">' . htmlspecialchars($customerName) . '</a>';
        } else {
            $customerInfo .= '<span class="text-dark fw-bold">' . htmlspecialchars($customerName) . '</span>';
        }
        
        if ($customerPhone) {
            $customerInfo .= '<span class="text-muted fs-7">' . htmlspecialchars($customerPhone) . '</span>';
        }
        if ($customerEmail) {
            $customerInfo .= '<span class="text-muted fs-7">' . htmlspecialchars($customerEmail) . '</span>';
        }
        
        $customerInfo .= '</div>';
        
        // Payment method badge with different colors
        $paymentMethodText = $row['payment_method'] ?? '';
        $paymentMethodBadge = '';
        
        switch(strtolower($paymentMethodText)) {
            case 'razorpay':
                $paymentMethodBadge = '<span class="badge badge-light-primary fw-bold">Razorpay</span>';
                break;
            case 'phone pay':
            case 'phonepay':
                $paymentMethodBadge = '<span class="badge badge-light-info fw-bold">Phone Pay</span>';
                break;
            case 'payu':
                $paymentMethodBadge = '<span class="badge badge-light-success fw-bold">PayU</span>';
                break;
            default:
                if (strpos($paymentMethodText, 'MP_') === 0) {
                    $paymentMethodBadge = '<span class="badge badge-light-warning fw-bold">Manual Payment</span>';
                } else {
                    $paymentMethodBadge = '<span class="badge badge-light-dark fw-bold">' . ucfirst($paymentMethodText) . '</span>';
                }
                break;
        }
        
        // Payment ID display (with tooltip)
        $paymentIdDisplay = $row['payment_id'] ?? '';
        $paymentIdHtml = '';
        if (!empty($paymentIdDisplay)) {
            if (strlen($paymentIdDisplay) > 20) {
                $shortPaymentId = substr($paymentIdDisplay, 0, 17) . '...';
                $paymentIdHtml = '<span class="text-muted" title="' . htmlspecialchars($paymentIdDisplay) . '" data-bs-toggle="tooltip">' . htmlspecialchars($shortPaymentId) . '</span>';
            } else {
                $paymentIdHtml = '<span class="text-muted">' . htmlspecialchars($paymentIdDisplay) . '</span>';
            }
        } else {
            $paymentIdHtml = '<span class="text-muted">N/A</span>';
        }
        
        // Status badge with dropdown actions
        $status = $row['status'] ?? 'active';
        $statusBadge = '';
        $statusClass = '';
        
        switch($status) {
            case 'active':
                $statusClass = 'badge-light-success';
                break;
            case 'refunded':
                $statusClass = 'badge-light-warning';
                break;
            case 'cancelled':
                $statusClass = 'badge-light-danger';
                break;
            default:
                $statusClass = 'badge-light-secondary';
        }
        
        // Create status dropdown
        $statusHtml = '<div class="dropdown">';
        $statusHtml .= '<button class="btn btn-sm ' . $statusClass . ' dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
        $statusHtml .= ucfirst($status);
        $statusHtml .= '</button>';
        $statusHtml .= '<ul class="dropdown-menu">';
        
        // Only show active option if current status is not active
        if ($status !== 'active') {
            $statusHtml .= '<li><a class="dropdown-item status-change" href="#" data-id="' . $row['id'] . '" data-status="active">Active</a></li>';
        }
        
        // Show refund option if status is not refunded
        if ($status !== 'refunded') {
            $statusHtml .= '<li><a class="dropdown-item status-change" href="#" data-id="' . $row['id'] . '" data-status="refunded">Refunded</a></li>';
        }
        
        // Show cancelled option if status is not cancelled
        if ($status !== 'cancelled') {
            $statusHtml .= '<li><a class="dropdown-item status-change" href="#" data-id="' . $row['id'] . '" data-status="cancelled">Cancelled</a></li>';
        }
        
        $statusHtml .= '</ul>';
        $statusHtml .= '</div>';
        
        $data[] = [
            "checkbox" => '<div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="' . $row['id'] . '" /></div>',
            "invoice_number" => '<span class="text-dark fw-bold">#' . $row['invoice_number'] . '</span>',
            "customer_info" => $customerInfo,
            "plan_name" => $row['plan_name'] ?? 'N/A',
            "amount" => '<span class="text-dark fw-bold">' . $amountFormatted . '</span>',
            "payment_method" => $paymentMethodBadge,
            "payment_id" => $paymentIdHtml,
            "status" => $statusHtml,
            "id" => $row['id'] // Add ID for actions
        ];
    }
    
    // Prepare response
    $response = [
        "draw" => $draw,
        "recordsTotal" => intval($totalRecords),
        "recordsFiltered" => intval($filteredRecords),
        "data" => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "Database error occurred: " . $e->getMessage()
    ]);
}
exit();
?>