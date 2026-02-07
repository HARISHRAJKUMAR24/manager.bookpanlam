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

// Set variables for form
$gst_enabled = !empty($settings->gst_number) ? 1 : 0;
$gst_tax_type = $settings->gst_tax_type ?? 'exclusive';
$gst_number = $settings->gst_number ?? '';
$gst_percentage = $settings->gst_percentage ?? 0;

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
                        <li class="breadcrumb-item text-gray-700">Tax Settings</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Tax Settings</h1>
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
            <form id="taxForm" method="POST" class="form">
                <!--begin::Card-->
                <div class="card">
                    <!--begin::Card header-->
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">GST Tax Settings</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Manage GST tax configuration</span>
                        </h3>
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0">
                        <!--begin::Input group-->
                        <div class="row mb-6">
                            <!--begin::Label-->
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Enable GST</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8 fv-row">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="gst_enabled" id="gst_enabled" <?= $gst_enabled ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="gst_enabled">
                                        Enable GST Tax System
                                    </label>
                                </div>
                                <div class="form-text">Toggle to enable or disable GST tax calculations</div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->

                        <!--begin::GST Details-->
                        <div id="gstDetails" <?= !$gst_enabled ? 'style="display: none"' : '' ?>>
                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Tax Type</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <div class="d-flex gap-5">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="gst_tax_type" id="inclusive" value="inclusive" <?= $gst_tax_type === 'inclusive' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="inclusive">
                                                Inclusive of Tax
                                            </label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="gst_tax_type" id="exclusive" value="exclusive" <?= $gst_tax_type === 'exclusive' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="exclusive">
                                                Exclusive of Tax
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-text">Select whether prices include tax or tax is added separately</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">GST Number</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="gst_number" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter GST number" value="<?= htmlspecialchars($gst_number) ?>" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <div class="form-text text-gray-600 mt-2">Enter your business GST registration number</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">GST Percentage</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <div class="input-group input-group-lg">
                                        <input type="number" name="gst_percentage" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter GST percentage" step="0.01" min="0" max="100" value="<?= htmlspecialchars($gst_percentage) ?>" required style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                        <span class="input-group-text border-gray-300 bg-body" style="background-color: #f8f9fa; border-color: #d1d3e0;">%</span>
                                    </div>
                                    <div class="form-text text-gray-600 mt-2">Enter the GST percentage rate to be applied</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::GST Details-->
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
<script src="assets/js/custom/settings/taxes-settings.js"></script>
<!--end::Script-->