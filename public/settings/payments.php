<!--begin:Header-->
<?php
require_once '../../src/database.php';
require_once '../../src/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) exit(header("Location: " . BASE_URL . "auth/sign-in"));
if (!isAdmin()) exit(header("Location: " . BASE_URL));

// Get database connection
$pdo = getDbConnection();

// Get current settings
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_OBJ);
if (!$settings) {
    // Insert default settings if not exists
    $pdo->query("INSERT INTO settings (app_name, currency) VALUES ('Book Pannu', 'INR')");
    $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_OBJ);
}

// Get payment settings
$razorpay_key_id = $settings->razorpay_key_id ?? '';
$razorpay_key_secret = $settings->razorpay_key_secret ?? '';
$phonepe_key_merchant_id = $settings->phonepe_key_merchant_id ?? '';
$phonepe_key_index = $settings->phonepe_key_index ?? '';
$phonepe_key = $settings->phonepe_key ?? '';
$payu_merchant_key = $settings->payu_merchant_key ?? '';
$payu_salt = $settings->payu_salt ?? '';
$payu_client_id = $settings->payu_client_id ?? '';
$payu_client_secret = $settings->payu_client_secret ?? '';

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
                        <li class="breadcrumb-item text-gray-700">
                            <a href="settings" class="text-gray-700 text-hover-primary">Settings</a>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <i class="ki-duotone ki-right fs-4 text-gray-700 mx-n1"></i>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-gray-700">Payment Settings</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Payment Settings</h1>
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
            <!--begin::Form-->
            <form id="paymentForm" method="POST" class="form">
                <!--begin::Card-->
                <div class="card">
                    <!--begin::Card header-->
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Payment Gateway Settings</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Configure payment gateway credentials</span>
                        </h3>
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0">
                        <!--begin::Razorpay Section-->
                        <div class="mb-12">
                            <h4 class="fw-bold text-gray-800 mb-6">Razorpay Settings</h4>
                            
                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Razorpay Key ID</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="razorpay_key_id" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter Razorpay Key ID" value="<?= htmlspecialchars($razorpay_key_id) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your Razorpay API Key ID from the dashboard</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Razorpay Key Secret</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="razorpay_key_secret" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter Razorpay Key Secret" value="<?= htmlspecialchars($razorpay_key_secret) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your Razorpay API Key Secret from the dashboard</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Razorpay Section-->

                        <!--begin::PayU Section-->
                        <div class="mb-12">
                            <h4 class="fw-bold text-gray-800 mb-6">PayU Settings</h4>
                            
                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">PayU Merchant Key</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="payu_merchant_key" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter PayU Merchant Key" value="<?= htmlspecialchars($payu_merchant_key) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your PayU Merchant Key from the dashboard</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">PayU Salt</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="payu_salt" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter PayU Salt" value="<?= htmlspecialchars($payu_salt) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your PayU Salt Key from the dashboard</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">PayU Client ID (Optional)</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="payu_client_id" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter PayU Client ID" value="<?= htmlspecialchars($payu_client_id) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your PayU Client ID (optional, for additional API features)</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">PayU Client Secret (Optional)</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="payu_client_secret" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter PayU Client Secret" value="<?= htmlspecialchars($payu_client_secret) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your PayU Client Secret (optional, for additional API features)</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::PayU Section-->

                        <!--begin::PhonePe Section-->
                        <div class="mb-0">
                            <h4 class="fw-bold text-gray-800 mb-6">PhonePe Settings</h4>
                            
                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">PhonePe Merchant ID</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="phonepe_key_merchant_id" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter PhonePe Merchant ID" value="<?= htmlspecialchars($phonepe_key_merchant_id) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your PhonePe Merchant ID from the dashboard</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">PhonePe Key Index</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="phonepe_key_index" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter PhonePe Key Index" value="<?= htmlspecialchars($phonepe_key_index) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your PhonePe Key Index from the dashboard</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">PhonePe Key</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="phonepe_key" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter PhonePe Key" value="<?= htmlspecialchars($phonepe_key) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Your PhonePe API Key from the dashboard</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::PhonePe Section-->
                    </div>
                    <!--end::Card body-->

                    <!--begin::Card footer-->
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Save Changes</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                    <!--end::Card footer-->
                </div>
                <!--end::Card-->
            </form>
            <!--end::Form-->
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
<script src="assets/js/custom/settings/payments-settings.js"></script>
<!--end::Script-->