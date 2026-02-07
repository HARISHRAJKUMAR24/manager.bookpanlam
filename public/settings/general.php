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

// Get current admin data
$admin = $pdo->query("SELECT * FROM managers WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_OBJ);

// Get image URL - use default if no image in database
$adminImage = !empty($admin->image) ? UPLOADS_URL . $admin->image : BASE_URL . 'assets/media/avatars/blank.png';

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
                        <li class="breadcrumb-item text-gray-700">General</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">General Settings</h1>
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
            <form method="POST" enctype="multipart/form-data" class="form" action="ajax/settings/general-settings.php">
                <!--begin::Card-->
                <div class="card">
                    <!--begin::Card header-->
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">General Settings</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Update application general settings</span>
                        </h3>
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0">
                        <!--begin::Admin Profile Section-->
                        <div class="mb-12">
                            <h4 class="fw-bold text-gray-800 mb-6">Admin Profile</h4>

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Profile Image</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <!--begin::Image input-->
                                    <div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url('<?= BASE_URL ?>assets/media/avatars/blank.png')">
                                        <!--begin::Preview existing avatar-->
                                        <div class="image-input-wrapper w-125px h-125px" style="background-image: url('<?= $adminImage ?>')"></div>
                                        <!--end::Preview existing avatar-->
                                        <!--begin::Label - Change button (Green)-->
                                        <label class="btn btn-icon btn-circle btn-active-color-success w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar" style="bottom: 0; right: 0;">
                                            <i class="ki-duotone ki-pencil fs-7 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <!--begin::Inputs-->
                                            <input type="file" name="image" accept=".png, .jpg, .jpeg" />
                                            <input type="hidden" name="existing_image" value="<?= $admin->image ?>" />
                                            <!--end::Inputs-->
                                        </label>
                                        <!--end::Label-->
                                        <!--begin::Cancel-->
                                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar" style="top: 0; right: 0;">
                                            <i class="ki-duotone ki-cross fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <!--end::Cancel-->
                                        <!--begin::Remove - Remove button (Red)-->
                                        <span class="btn btn-icon btn-circle btn-active-color-danger w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar" style="top: 0; left: 0;">
                                            <i class="ki-duotone ki-cross fs-2 text-danger">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <!--end::Remove-->
                                    </div>
                                    <!--end::Image input-->
                                    <!--begin::Hint-->
                                    <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                                    <!--end::Hint-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Full Name</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="name" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter full name" value="<?= htmlspecialchars($admin->name) ?>" required style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <input type="hidden" name="old_name" value="<?= htmlspecialchars($admin->name) ?>" />
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Admin Profile Section-->

                        <!--begin::Application Settings Section-->
                        <div class="mb-12">
                            <h4 class="fw-bold text-gray-800 mb-6">Application Settings</h4>

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">App Name</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="app_name" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter application name" value="<?= htmlspecialchars($settings->app_name) ?>" required style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Application Settings Section-->

                        <!--begin::Input group For Time Zone-->
                        <div class="row mb-6">
                            <!--begin::Label-->
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Timezone</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8 fv-row">
                                <select name="timezone" id="timezone" class="form-select form-select-lg border-gray-300 bg-body" style="background-color: #f8f9fa; border-color: #d1d3e0;" required>
                                    <option value="Asia/Kolkata" <?= ($settings->timezone ?? 'Asia/Kolkata') == "Asia/Kolkata" ? "selected" : "" ?>>India (Asia/Kolkata) GMT+5:30</option>
                                    <option value="America/New_York" <?= ($settings->timezone ?? 'Asia/Kolkata') == "America/New_York" ? "selected" : "" ?>>USA – New York GMT-5:00</option>
                                    <option value="Europe/London" <?= ($settings->timezone ?? 'Asia/Kolkata') == "Europe/London" ? "selected" : "" ?>>UK – London GMT+0:00</option>
                                    <option value="Asia/Dubai" <?= ($settings->timezone ?? 'Asia/Kolkata') == "Asia/Dubai" ? "selected" : "" ?>>UAE – Dubai GMT+4:00</option>
                                    <option value="Australia/Sydney" <?= ($settings->timezone ?? 'Asia/Kolkata') == "Australia/Sydney" ? "selected" : "" ?>>Australia – Sydney GMT+10:00</option>
                                    <!-- Add more popular timezones as needed -->
                                </select>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->

                        <!-- Add this live clock display section -->
                        <!--begin::Input group-->
                        <div class="row mb-6">
                            <!--begin::Label-->
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Current Time</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8 fv-row">
                                <div id="current-time" class="form-control form-control-lg border-gray-300 bg-body" style="background-color: #f8f9fa; border-color: #d1d3e0; height: auto; min-height: 48px; display: flex; align-items: center; padding: 0.5rem 1rem;">
                                    <span id="clock-display">Loading time...</span>
                                </div>
                                <div class="form-text text-gray-600 mt-2">Live display of current time in selected timezone</div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->
                        
                        <!--begin::Brand Assets Section-->
                        <div class="mb-0">
                            <h4 class="fw-bold text-gray-800 mb-6">Brand Assets</h4>

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Logo</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="file" name="logo" class="form-control form-control-lg border-gray-300 bg-body" accept=".png, .jpg, .jpeg" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <?php if (!empty($settings->logo)): ?>
                                        <div class="mt-3">
                                            <img src="<?= UPLOADS_URL . $settings->logo ?>" alt="Logo" class="img-thumbnail" style="max-width: 200px; height: auto;">
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-text text-gray-600 mt-2">Upload your company logo (Recommended: 200x60px)</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Favicon</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <input type="file" name="favicon" class="form-control form-control-lg border-gray-300 bg-body" accept=".png, .jpg, .jpeg, .ico" style="background-color: #f8f9fa; border-color: #d1d3e0;" />
                                    <?php if (!empty($settings->favicon)): ?>
                                        <div class="mt-3">
                                            <img src="<?= UPLOADS_URL . $settings->favicon ?>" alt="Favicon" class="img-thumbnail" style="max-width: 32px; height: auto;">
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-text text-gray-600 mt-2">Upload your website favicon (Recommended: 32x32px)</div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Address</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <textarea name="address" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter company address" rows="3" style="background-color: #f8f9fa; border-color: #d1d3e0;"><?= htmlspecialchars($settings->address ?? '') ?></textarea>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Disclaimer</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <textarea name="disclaimer" class="form-control form-control-lg border-gray-300 bg-body" placeholder="Enter disclaimer text" rows="3" style="background-color: #f8f9fa; border-color: #d1d3e0;"><?= htmlspecialchars($settings->disclaimer ?? '') ?></textarea>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Brand Assets Section-->
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
<script src="assets/js/custom/settings/general-settings.js"></script>
<!--end::Script-->