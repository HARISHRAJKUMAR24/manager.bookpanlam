<!--begin:Header-->
<?php
require_once '../../src/database.php';
require_once '../../src/functions.php';

if (!isset($_GET['id']) || !isUserExists($_GET['id'])) {
    redirect(BASE_URL . 'users');
}

$user = fetchUserById($_GET['id'], "user_id");

// Simple plan name fetch
$planName = 'No Plan';
$planData = null;

// Get user_id from user object
$user_id = $user->user_id;

// Single query to get plan name using JOIN
$pdo = getDbConnection();
$sql = "SELECT sp.* FROM users u LEFT JOIN subscription_plans sp ON u.plan_id = sp.id WHERE u.user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result && isset($result['id'])) {
    $planData = $result;
    $planName = $result['name'] ?? 'No Plan';
}

// Get suspension history
$suspensionHistory = [];
if ($user->user_id) {
    $historySql = "SELECT * FROM suspend_users WHERE user_id = ? ORDER BY created_at DESC";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([$user->user_id]); // Use user_id not id
    $suspensionHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
}


renderTemplate('header');
?>
<!--end:Header-->

<!--begin::Vendor Stylesheets(used for this page only)-->
<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
<!--end::Vendor Stylesheets-->

<!--begin::Content wrapper-->
<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar pt-5">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex align-items-stretch">
            <div class="app-toolbar-wrapper d-flex flex-stack flex-wrap gap-4 w-100">
                <!--begin::Page title-->
                <div class="page-title d-flex flex-column gap-1 me-3 mb-2">
                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold mb-6">
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                            <a href="<?= BASE_URL ?>" class="text-gray-500 text-hover-primary">
                                <i class="ki-duotone ki-home fs-3 text-gray-400 me-n1"></i>
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <i class="ki-duotone ki-right fs-4 text-gray-700 mx-n1"></i>
                        </li>
                        <li class="breadcrumb-item text-gray-800 fw-bold lh-1">
                            <a href="<?= BASE_URL ?>users" class="text-gray-800 text-hover-primary">Users</a>
                        </li>
                        <li class="breadcrumb-item">
                            <i class="ki-duotone ki-right fs-4 text-gray-700 mx-n1"></i>
                        </li>
                        <li class="breadcrumb-item text-gray-700">ID : <?= $user->user_id ?></li>
                    </ul>
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">User Profile</h1>
                </div>
                <!--end::Page title-->
            </div>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <!--begin::Navbar-->
            <div class="card mb-5 mb-xxl-8">
                <div class="card-body pt-9 pb-0">
                    <!--begin::Details-->
                    <div class="d-flex flex-wrap flex-sm-nowrap">
                        <!--begin: Pic-->
                        <div class="me-7 mb-4">
                            <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                                <img src="<?= UPLOADS_URL . $user->image ?>" alt="<?= $user->name ?>" onerror="this.src='assets/media/avatars/blank.png'" />
                            </div>
                        </div>
                        <!--end::Pic-->

                        <!--begin::Info-->
                        <div class="flex-grow-1">
                            <!--begin::Title-->
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                <!--begin::User-->
                                <div class="d-flex flex-column">
                                    <!--begin::Name-->
                                    <div class="d-flex align-items-center mb-2">
                                        <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bold me-1"><?= htmlspecialchars($user->name) ?></a>
                                        <?php if (!$user->is_suspended): ?>
                                            <a href="#">
                                                <i class="ki-duotone ki-verify fs-1 text-primary">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <!--end::Name-->

                                    <!--begin::Info-->
                                    <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                        <?php if ($user->phone): ?>
                                            <a href="tel:<?= $user->phone ?>" class="d-flex align-items-center text-gray-800 text-hover-primary me-5 mb-2">
                                                <i class="ki-duotone ki-phone fs-4 me-1 text-primary">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i><?= htmlspecialchars($user->phone) ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (isset($user->country) && $user->country): ?>
                                            <span class="d-flex align-items-center text-gray-800 me-5 mb-2">
                                                <i class="ki-duotone ki-geolocation fs-4 me-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i><?= htmlspecialchars($user->country) ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($user->email): ?>
                                            <a href="mailto:<?= $user->email ?>" class="d-flex align-items-center text-gray-800 text-hover-primary mb-2">
                                                <i class="ki-duotone ki-sms fs-4 me-1 text-info">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i><?= htmlspecialchars($user->email) ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($user->site_slug): ?>
                                            <a href="https://<?= htmlspecialchars($user->site_slug) ?>.bookpannu.com"
                                                target="_blank"
                                                class="d-flex align-items-center text-gray-800 text-hover-primary ms-5 mb-2">
                                                <i class="ki-duotone ki-globe fs-4 me-1 text-warning">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i><?= htmlspecialchars($user->site_slug) ?>.bookpannu.com
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <!--end::Info-->
                                </div>
                                <!--end::User-->

                                <!--begin::Actions-->
                                <div class="d-flex my-4">
                                    <button type="button" class="btn btn-sm btn-primary" id="loginBtn">Login</button>
                                </div>
                                
                                <!--end::Actions-->
                            </div>
                            <!--end::Title-->

                            <!--begin::Plan Info Card-->
                            <div class="card mb-5">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-5">
                                        <div class="symbol symbol-50px symbol-circle me-3">
                                            <span class="symbol-label bg-light-primary">
                                                <i class="ki-duotone ki-crown fs-2x text-primary">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-600 fw-semibold fs-6">Current Plan</span>
                                            <span class="text-gray-900 fw-bold fs-3"><?= htmlspecialchars($planName) ?></span>
                                        </div>
                                    </div>

                                    <!--begin::Stats - All in boxes-->
                                    <div class="d-flex flex-wrap flex-stack">
                                        <div class="d-flex flex-column flex-grow-1 pe-8">
                                            <!-- First Row -->
                                            <div class="d-flex flex-wrap mb-3">
                                                <!-- Earnings -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-120px py-3 px-3 me-4 mb-3 text-center flex-fill">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <div class="fs-2 fw-bold text-gray-800 mb-1" data-kt-countup="true" data-kt-countup-value="<?= getUserEarnings($user->id) ?>">
                                                            <?= number_format(getUserEarnings($user->id) ?: 0) ?>
                                                        </div>
                                                        <div class="fw-semibold fs-7 text-gray-600">Earnings</div>
                                                    </div>
                                                </div>

                                                <!-- Appointments -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-120px py-3 px-3 me-4 mb-3 text-center flex-fill">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <div class="fs-2 fw-bold text-gray-800 mb-1" data-kt-countup="true" data-kt-countup-value="<?= getUserAppointments($user->id) ?>">
                                                            <?= number_format(getUserAppointments($user->id) ?: 0) ?>
                                                        </div>
                                                        <div class="fw-semibold fs-7 text-gray-600">Appointments</div>
                                                    </div>
                                                </div>

                                                <!-- Customers Limit -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-120px py-3 px-3 me-4 mb-3 text-center flex-fill">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <?php
                                                        $customerData = getCustomerLimitWithCount($user_id); // $user_id should be available in your context
                                                        ?>
                                                        <div class="fs-2 fw-bold text-gray-800 mb-1">
                                                            <?= $customerData['actual_count'] ?> / <?= $customerData['limit'] === 'unlimited' ? 'Unlimited' : $customerData['limit'] ?>
                                                        </div>
                                                        <div class="fw-semibold fs-7 text-gray-600">Customers</div>
                                                        <?php if (!$customerData['can_add']): ?>
                                                            <div class="fs-8 text-danger mt-1">
                                                                Limit reached!
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Second Row -->
                                            <div class="d-flex flex-wrap">
                                                <!-- Services Limit - Only show for non-HOTEL users -->
                                                <?php
                                                $servicesLimit = getUserPlanLimitWithActual($user_id, 'services');
                                                $actualCounts = getUserActualResourcesCount($user_id);

                                                // Only show services block if user is not HOTEL type (service_type_id = 2)
                                                // or if they actually have services (categories or departments)
                                                if ($servicesLimit['label'] !== 'Menu Items' || $servicesLimit['actual_count'] > 0) {
                                                ?>
                                                    <div class="border border-gray-300 border-dashed rounded min-w-120px py-3 px-3 me-4 mb-3 text-center flex-fill">
                                                        <div class="d-flex flex-column align-items-center">
                                                            <div class="fs-2 fw-bold text-gray-800 mb-1">
                                                                <?= $servicesLimit['actual_count'] ?> / <?= $servicesLimit['limit'] === 'unlimited' ? 'Unlimited' : $servicesLimit['limit'] ?>
                                                            </div>
                                                            <div class="fw-semibold fs-7 text-gray-600"><?= $servicesLimit['label'] ?></div>
                                                        </div>
                                                    </div>
                                                <?php } ?>

                                                <!-- Menu Items Limit - Only show for HOTEL users OR users with menu items -->
                                                <?php
                                                if ($actualCounts['menu_items_count'] > 0) {
                                                    $menuItemsLimit = getUserPlanLimitWithActual($user_id, 'menu_items');
                                                ?>
                                                    <div class="border border-gray-300 border-dashed rounded min-w-120px py-3 px-3 me-4 mb-3 text-center flex-fill">
                                                        <div class="d-flex flex-column align-items-center">
                                                            <div class="fs-2 fw-bold text-gray-800 mb-1">
                                                                <?= $menuItemsLimit['actual_count'] ?> / <?= $menuItemsLimit['limit'] === 'unlimited' ? 'Unlimited' : $menuItemsLimit['limit'] ?>
                                                            </div>
                                                            <div class="fw-semibold fs-7 text-gray-600"><?= $menuItemsLimit['label'] ?></div>
                                                        </div>
                                                    </div>
                                                <?php } ?>

                                                <!-- Joined Date -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-120px py-3 px-3 me-4 mb-3 text-center flex-fill">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <div class="fs-3 fw-bold text-gray-800 mb-1">
                                                            <?= date('M d, Y', strtotime($user->created_at)) ?>
                                                        </div>
                                                        <div class="fw-semibold fs-7 text-gray-600">Joined Date</div>
                                                    </div>
                                                </div>

                                                <!-- Expires On -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-120px py-3 px-3 me-4 mb-3 text-center flex-fill">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <div class="fs-3 fw-bold text-gray-800 mb-1">
                                                            <?= $user->expires_on ? date('M d, Y', strtotime($user->expires_on)) : 'N/A' ?>
                                                        </div>
                                                        <div class="fw-semibold fs-7 text-gray-600">Expires On</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Stats-->
                                </div>
                            </div>
                            <!--end::Plan Info Card-->

                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Details-->
                </div>
            </div>
            <!--end::Navbar-->

            <!--begin::Actions Section-->
            <div class="row g-5">
                <!--begin::Suspend Card-->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold">Account Status</h3>
                            </div>
                        </div>
                        <div class="card-body py-4">
                            <!-- Current Status -->
                            <div class="mb-8">
                                <div class="fw-semibold text-gray-600 mb-3">Current Status</div>
                                <div class="d-flex align-items-center">
                                    <?php if ($user->is_suspended): ?>
                                        <span class="badge badge-danger fs-6 fw-bold px-4 py-2">SUSPENDED</span>
                                        <span class="text-muted fs-7 ms-3">User account is currently suspended</span>
                                    <?php else: ?>
                                        <span class="badge badge-success fs-6 fw-bold px-4 py-2">ACTIVE</span>
                                        <span class="text-muted fs-7 ms-3">User account is active and functional</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Suspension History -->
                            <?php if (!empty($suspensionHistory)): ?>
                                <div class="mb-8">
                                    <div class="fw-semibold text-gray-600 mb-3">Suspension History</div>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-row-bordered table-row-gray-300 gy-4">
                                            <thead>
                                                <tr class="fw-bold fs-7 text-gray-800">
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                    <th>Reason</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($suspensionHistory as $history): ?>
                                                    <tr>
                                                        <td class="fs-7"><?= date('M d, Y h:i A', strtotime($history['created_at'])) ?></td>
                                                        <td>
                                                            <?php if ($history['action_type'] == 'suspend'): ?>
                                                                <span class="badge badge-danger">Suspended</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-success">Unsuspended</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="fs-7">
                                                            <?= !empty($history['reason']) ? htmlspecialchars($history['reason']) : '<span class="text-muted">No reason provided</span>' ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!$user->is_suspended): ?>
                                <!-- Suspend Form -->
                                <form id="suspendForm">
                                    <input type="hidden" name="user_id" value="<?= $user->id ?>">
                                    <input type="hidden" name="action" value="suspend">
                                    <div class="mb-8">
                                        <div class="fw-semibold text-gray-600 mb-3">Suspend This Account</div>
                                        <div class="d-flex flex-column">
                                            <div class="mb-5">
                                                <label class="form-label">Reason for Suspension (Optional)</label>
                                                <textarea class="form-control form-control-solid" name="message" rows="3" placeholder="Enter reason for suspension..."></textarea>
                                                <div class="form-text">Type any message and submit to suspend this account</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-danger" id="suspendBtn">
                                            <span class="indicator-label">Suspend Account</span>
                                            <span class="indicator-progress">Please wait...
                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <!-- Unsuspend Form -->
                                <form id="unsuspendForm">
                                    <input type="hidden" name="user_id" value="<?= $user->id ?>">
                                    <input type="hidden" name="action" value="unsuspend">
                                    <div class="mb-8">
                                        <div class="fw-semibold text-gray-600 mb-3">Unsuspend This Account</div>
                                        <div class="d-flex flex-column">
                                            <div class="mb-5">
                                                <label class="form-label">Note (Optional)</label>
                                                <textarea class="form-control form-control-solid" name="message" rows="3" placeholder="Enter note for unsuspension..."></textarea>
                                                <div class="form-text">Type any message and submit to unsuspend this account</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-success" id="unsuspendBtn">
                                            <span class="indicator-label">Unsuspend Account</span>
                                            <span class="indicator-progress">Please wait...
                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!--end::Suspend Card-->

                <!--begin::Delete Card-->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold text-danger">Delete Account</h3>
                            </div>
                        </div>
                        <div class="card-body py-4">
                            <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                                <div class="d-flex flex-column">
                                    <h5 class="mb-1">Warning</h5>
                                    <span>This action is permanent and cannot be undone. All user data will be deleted including:</span>
                                    <ul class="mt-2">
                                        <li>User profile and settings</li>
                                        <li>All appointments and booking history</li>
                                        <li>All customers data</li>
                                        <li>All services and categories</li>
                                        <li>All uploaded files and images</li>
                                        <li>Site configuration and settings</li>
                                        <li>Payment history and records</li>
                                        <li>Suspension history</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="mb-8">
                                <div class="fw-semibold text-gray-600 mb-3">To confirm deletion, please type:</div>
                                <div class="mb-3">
                                    <input type="text" class="form-control form-control-solid mb-2" id="confirmText" placeholder="Type 'DELETE' to confirm" />
                                    <div class="form-text">Type <span class="text-danger fw-bold">DELETE</span> in the field above to enable delete button</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-danger" id="deleteBtn" disabled>
                                    <span class="indicator-label">Delete Account</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Delete Card-->
            </div>
            <!--end::Actions Section-->
        </div>
    </div>
    <!--end::Content-->
</div>
<!--end::Content wrapper-->

<!--begin::Modal - Delete Confirmation-->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Confirm Deletion</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body py-10 px-lg-17">
                <div class="text-center mb-10">
                    <i class="ki-duotone ki-shield-cross fs-2hx text-danger">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                </div>
                <div class="text-center mb-15">
                    <h4 class="fw-bold text-gray-900 mb-5">Are you sure you want to delete?</h4>
                    <div class="text-muted fw-semibold fs-6">
                        This action cannot be undone. All data associated with this user will be permanently deleted from the system.
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-center">
                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="indicator-label">Yes, Delete Permanently</span>
                    <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Delete Confirmation-->

<!-- Hidden field for user_id -->
<input type="hidden" id="user_id_hidden" value="<?= $user->user_id ?>">



<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->


<!--begin::Vendors Javascript(used for this page only)-->
<script src="assets/plugins/custom/jquery/jquery-3.7.1.min.js"></script>

<script>
$(document).on('click', '#loginBtn', function () {
    const userId = $('#user_id_hidden').val();

    $.post(
        '/manager.bookpanlam/public/ajax/create-manager-login-token.php',
        { user_id: userId }
    )
    .done(function (res) {
        console.log("SSO response:", res);
        let data;
        try {
            data = typeof res === 'string' ? JSON.parse(res) : res;
        } catch (e) {
            alert('Invalid server response');
            console.log(res);
            return;
        }

        if (data.success && data.token) {
                    console.log("Opening SSO with token:", data.token); // 👈 ADD

            // ✅ OPEN SSO FLOW IN NEW TAB
            window.open(
  'http://localhost:3000/sso?token=' + data.token,
                '_blank'
            );
        } else {
            alert('Token generation failed');
            console.log(data);
        }
    })
    .fail(function (xhr) {
        alert('AJAX request failed');
        console.log(xhr.status, xhr.responseText);
    });
});
</script>



<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<!--end::Vendors Javascript-->
<!--begin::Custom Javascript(used for this page only)-->
<script src="assets/js/custom/users/profile.js"></script>