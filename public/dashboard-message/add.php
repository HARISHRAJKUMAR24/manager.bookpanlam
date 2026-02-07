<!--begin:Header-->
<?php
require_once '../../src/database.php';
require_once '../../src/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) exit(header("Location: " . BASE_URL . "auth/sign-in"));
if (!isAdmin()) exit(header("Location: " . BASE_URL));

// Get database connection
$pdo = getDbConnection();

// Get ONLY ACTIVE subscription plans for dropdown (filter out disabled)
$plans = $pdo->query("SELECT id, name FROM subscription_plans WHERE is_disabled = 1 ORDER BY name")->fetchAll(PDO::FETCH_OBJ);

renderTemplate('header');
?>
<!--end:Header-->

<!--begin::Content wrapper-->
<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar pt-5">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex align-items-stretch">
            <!--begin::Toolbar wrapper-->
            <div class="app-toolbar-wrapper d-flex flex-stack flex-wrap gap-4 w-100">
                <!--begin::Page title-->
                <div class="page-title d-flex flex-column gap-1 me-3 mb-2">
                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold mb-6">
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                            <a href="<?= BASE_URL ?>" class="text-gray-500 text-hover-primary">
                                <i class="ki-duotone ki-home fs-3 text-gray-400 me-n1"></i>
                            </a>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <i class="ki-duotone ki-right fs-4 text-gray-700 mx-n1"></i>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-gray-700">Dashboard Messages</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Dashboard Messages</h1>
                    <!--end::Title-->
                </div>
                <!--end::Page title-->
            </div>
            <!--end::Toolbar wrapper-->
        </div>
        <!--end::Toolbar container-->
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="row g-6">
                <!--begin::Table Section - Made smaller to give more space to form-->
                <div class="col-xxl-6 col-xl-6 col-lg-7 col-md-12">
                    <!--begin::Card-->
                    <div class="card h-100">
                        <!--begin::Card header-->
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-3 mb-1">Active Messages</span>
                                <span class="text-muted mt-1 fw-semibold fs-7">Manage dashboard messages for sellers</span>
                            </h3>
                            <div class="card-toolbar">
                                <div class="d-flex align-items-center position-relative me-4">
                                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="text" id="searchMessages" class="form-control form-control-solid w-200px ps-10" placeholder="Search messages..." />
                                </div>
                            </div>
                        </div>
                        <!--end::Card header-->

                        <!--begin::Card body-->
                        <div class="card-body pt-0 d-flex flex-column">
                            <!--begin::Table container-->
                            <div class="table-responsive flex-grow-1">
                                <!--begin::Table-->
                                <table id="messages_Table" class="table table-hover align-middle table-row-dashed fs-6 gy-3">
                                    <thead>
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <th class="w-50px ps-4">#</th>
                                            <th class="min-w-200px">Message Details</th>
                                            <th class="min-w-120px">Target Audience</th>
                                            <th class="min-w-120px">Expiry</th>
                                            <th class="min-w-80px">Status</th>
                                            <th class="min-w-80px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600">
                                        <!-- Data will be loaded via AJAX -->
                                    </tbody>
                                </table>
                                <!--end::Table-->
                            </div>
                            <!--end::Table container-->

                            <!--begin::Table info-->
                            <div class="text-muted fw-semibold fs-7 mt-4 pt-3 border-top">
                                <i class="ki-duotone ki-information fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                Messages are automatically removed after expiry
                            </div>
                            <!--end::Table info-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Table Section-->

                <!--begin::Form Section - Made bigger-->
                <div class="col-xxl-6 col-xl-6 col-lg-5 col-md-12">
                    <!--begin::Card-->
                    <div class="card h-100">
                        <!--begin::Card header-->
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-3 mb-1">Create New Message</span>
                                <span class="text-muted mt-1 fw-semibold fs-7">Send message to seller dashboards</span>
                            </h3>
                        </div>
                        <!--end::Card header-->

                        <!--begin::Card body-->
                        <div class="card-body pt-0">
                            <!--begin::Form-->
                            <form id="messageForm" class="form">
                                <!--begin::Input group-->
                                <div class="row mb-7">
                                    <!--begin::Label-->
                                    <label class="col-lg-12 col-form-label required fw-semibold fs-6 mb-2">Title</label>
                                    <!--end::Label-->
                                    <!--begin::Col-->
                                    <div class="col-lg-12 fv-row">
                                        <input type="text" name="title" class="form-control form-control-lg form-control-solid mb-1" placeholder="Enter message title" required />
                                        <div class="form-text text-gray-600">Short and descriptive title for the message</div>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="row mb-7">
                                    <!--begin::Label-->
                                    <label class="col-lg-12 col-form-label required fw-semibold fs-6 mb-2">Description</label>
                                    <!--end::Label-->
                                    <!--begin::Col-->
                                    <div class="col-lg-12 fv-row">
                                        <textarea name="description" class="form-control form-control-lg form-control-solid mb-1" placeholder="Enter detailed message description..." rows="4" required></textarea>
                                        <div class="form-text text-gray-600">Full message content that sellers will see on their dashboard</div>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="row mb-7">
                                    <!--begin::Label-->
                                    <label class="col-lg-12 col-form-label required fw-semibold fs-6 mb-2">Message Duration</label>
                                    <!--end::Label-->
                                    <!--begin::Col-->
                                    <div class="col-lg-12">
                                        <div class="row g-3 mb-2">
                                            <div class="col-6">
                                                <input type="number" name="expiry_value" class="form-control form-control-lg form-control-solid" placeholder="Value" min="1" max="365" required />
                                                <div class="form-text text-gray-600">Duration value</div>
                                            </div>
                                            <div class="col-6">
                                                <select name="expiry_type" class="form-select form-select-lg form-select-solid" required>
                                                    <option value="hours">Hours</option>
                                                    <option value="days" selected>Days</option>
                                                    <option value="weeks">Weeks</option>
                                                    <option value="months">Months</option>
                                                </select>
                                                <div class="form-text text-gray-600">Time unit</div>
                                            </div>
                                        </div>
                                        <div class="alert alert-info d-flex align-items-center p-4 mb-0">
                                            <i class="ki-duotone ki-clock fs-2x me-3 text-info">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold fs-6">Automatic Expiry</span>
                                                <span class="text-gray-600">The message will automatically expire once the duration is completed.</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="row mb-7">
                                    <!--begin::Label-->
                                    <label class="col-lg-12 col-form-label fw-semibold fs-6 mb-2">Seller Type</label>
                                    <!--end::Label-->
                                    <!--begin::Col-->
                                    <div class="col-lg-12 fv-row">
                                        <select name="seller_type[]" class="form-select form-select-lg form-select-solid" multiple="multiple" data-control="select2" data-placeholder="Select seller types">
                                            <option value="all">All Sellers</option>
                                            <?php foreach ($plans as $plan): ?>
                                                <option value="<?= $plan->id ?>">
                                                    <?= htmlspecialchars($plan->name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text text-gray-600 mt-1">Select specific seller types or "All Sellers" to target everyone</div>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="row mb-8">
                                    <!--begin::Label-->
                                    <label class="col-lg-12 col-form-label fw-semibold fs-6 mb-2">Target Audience</label>
                                    <!--end::Label-->
                                    <!--begin::Col-->
                                    <div class="col-lg-12 fv-row">
                                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                            <input class="form-check-input" type="checkbox" name="just_created_seller" id="just_created_seller" value="1" />
                                            <label class="form-check-label fw-semibold fs-6" for="just_created_seller">
                                                Show to newly created sellers only
                                            </label>
                                        </div>
                                        <div class="alert alert-warning d-flex align-items-center p-4 mb-0">
                                            <i class="ki-duotone ki-information fs-2x me-3 text-warning">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold fs-6">New Sellers Only</span>
                                                <span class="text-gray-600">If enabled, only sellers who joined after this message creation will see it</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Input group-->

                                <!--begin::Card footer-->
                                <div class="card-footer d-flex justify-content-end py-6 px-9 mt-2">
                                    <button type="reset" class="btn btn-light btn-active-light-primary me-3 px-6">Cancel</button>
                                    <button type="submit" class="btn btn-primary px-8">
                                        <span class="indicator-label">Send Message</span>
                                        <span class="indicator-progress">Please wait...
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </div>
                                <!--end::Card footer-->
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Form Section-->
            </div>
        </div>
        <!--end::Content container-->
    </div>
    <!--end::Content-->
</div>
<!--end::Content wrapper-->

<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->

<!--begin::Script-->
<script src="assets/js/custom/dashboard-messages.js"></script>
<!--end::Script-->