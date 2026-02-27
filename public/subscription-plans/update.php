<!--begin:Header-->
<?php
require_once '../../src/functions.php';
renderTemplate('header');


if (!isset($_GET['id']) || !isPlanExists($_GET['id'])) {
    redirect(BASE_URL . 'subscription-plans');
}

$settings = fetchSettings();
$plan = fetchPlan($_GET['id']);
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
                <div class="page-title d-flex flex-column gap-1 me-3 mb-2 w-100">
                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold mb-6">
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                            <a href="<?= BASE_URL ?>" class="text-gray-500">
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
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                            <a href="subscription-plans" class="text-gray-500">
                                Subscription Plans
                            </a>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <i class="ki-duotone ki-right fs-4 text-gray-700 mx-n1"></i>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1"><?= $plan->name ?></li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Update Plan</h1>
                    </div>
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
            <form action="<?= BASE_URL ?>ajax/subscription-plans/update.php" id="updateForm" class="row">

                <input type="text" name="plan_id" value="<?= $plan->plan_id ?>" readonly hidden>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Name</label>
                    <input type="text" name="name" class="form-control form-control-solid" placeholder="Welcome" value="<?= $plan->name ?>" />
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Amount</label>
                    <div class="input-group input-group-solid">
                        <span class="input-group-text"><?= getCurrencySymbol($settings->currency) ?></span>
                        <input type="number" name="amount" class="form-control form-control-solid" placeholder="199" value="<?= $plan->amount ?>" />
                    </div>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="form-label">Previous Amount</label>
                    <div class="input-group input-group-solid">
                        <span class="input-group-text"><?= getCurrencySymbol($settings->currency) ?></span>
                        <input type="number" name="previous_amount" class="form-control form-control-solid" placeholder="216" value="<?= $plan->previous_amount ?>" />
                    </div>
                </div>

                <!-- Change the duration field section in update.php -->
                <div class="col-sm-6 mb-5">
                    <label class="form-label">Duration</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" name="duration_value" class="form-control form-control-solid"
                                placeholder="1" min="1" max="100" value="<?= getDurationValue($plan->duration) ?>" required />
                            <div class="form-text text-gray-600">Duration value</div>
                        </div>
                        <div class="col-6">
                            <select name="duration_type" class="form-select form-select-solid" required>
                                <option value="month" <?= getDurationType($plan->duration) === 'month' ? 'selected' : '' ?>>Month</option>
                                <option value="year" <?= getDurationType($plan->duration) === 'year' ? 'selected' : '' ?>>Year</option>
                            </select>
                            <div class="form-text text-gray-600">Time unit</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-12 mb-5">
                    <label class="required form-label">Description</label>
                    <textarea name="description" class="form-control form-control-solid" placeholder="Start your dream online store for only 199 for 1 year"><?= $plan->description ?></textarea>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Appointments Limit</label>
                    <input type="text" name="appointments_limit" class="form-control form-control-solid" value="<?= $plan->appointments_limit ?>" />
                    <small class="d-block mt-2">Please use the term <mark>unlimited</mark> to indicate the granting of unrestricted permission.</small>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Customers Limit</label>
                    <input type="text" name="customers_limit" class="form-control form-control-solid" value="<?= $plan->customers_limit ?>" />
                    <small class="d-block mt-2">Please use the term <mark>unlimited</mark> to indicate the granting of unrestricted permission.</small>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Services Limit</label>
                    <input type="text" name="services_limit" class="form-control form-control-solid" value="<?= $plan->services_limit ?>" />
                    <small class="d-block mt-2">Please use the term <mark>unlimited</mark> to indicate the granting of unrestricted permission.</small>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Menu Limit</label>
                    <input type="text" name="menu_limit" class="form-control form-control-solid" value="<?= $plan->menu_limit ?>" />
                    <small class="d-block mt-2">Please use the term <mark>unlimited</mark> to indicate the granting of unrestricted permission.</small>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Coupons Limit</label>
                    <input type="text" name="coupons_limit" class="form-control form-control-solid" value="<?= $plan->coupons_limit ?>" />
                    <small class="d-block mt-2">Please use the term <mark>unlimited</mark> to indicate the granting of unrestricted permission.</small>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Manual Payment Methods Limit</label>
                    <input type="text" name="manual_payment_methods_limit" class="form-control form-control-solid" value="<?= $plan->manual_payment_methods_limit ?>" />
                    <small class="d-block mt-2">Please use the term <mark>unlimited</mark> to indicate the granting of unrestricted permission.</small>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">UPI Payment Methods Limit</label>
                    <input type="text" name="upi_payment_methods_limit" class="form-control form-control-solid" value="<?= $plan->upi_payment_methods_limit ?? 'unlimited' ?>" />
                    <small class="d-block mt-2">Please use the term <mark>unlimited</mark> to indicate the granting of unrestricted permission.</small>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Free Credits</label>
                    <input type="text" name="free_credits" class="form-control form-control-solid" value="<?= $plan->free_credits ?>" />
                    <small class="d-block mt-2">Please use the term <mark>unlimited</mark> to indicate the granting of unrestricted permission.</small>
                </div>

                <!-- Change this section in update.php -->
                <div class="col-sm-6 mb-5">
                    <label class="form-label">Payment Methods</label>

                    <div class="d-flex align-items-center flex-wrap gap-3 mt-2">
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="razorpay" name="razorpay" value="1" <?= $plan->razorpay ? 'checked' : '' ?> />
                            <label class="form-check-label" for="razorpay">
                                Razorpay
                            </label>
                        </div>

                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="phonepe" name="phonepe" value="1" <?= $plan->phonepe ? 'checked' : '' ?> />
                            <label class="form-check-label" for="phonepe">
                                Phonepe
                            </label>
                        </div>

                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="payu" name="payu" value="1" <?= $plan->payu ? 'checked' : '' ?> />
                            <label class="form-check-label" for="payu">
                                Payu
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Custom Domain Toggle -->
                <div class="col-sm-6 mb-5">
                    <label class="form-label">Custom Domain</label>
                    <div class="d-flex align-items-center">
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="custom_domain" name="custom_domain" value="1" <?= ($plan->custom_domain ?? 0) ? 'checked' : '' ?> />
                            <label class="form-check-label" for="custom_domain">
                                Enable Custom Domain
                            </label>
                        </div>
                    </div>
                    
                </div>

                <div class="col-sm-12 mb-5">
                    <label class="required form-label">GST Type</label>

                    <select name="gst_type" class="form-select form-select-solid">
                        <option value="exclusive" <?= $plan->gst_type === "exclusive" ? 'selected' : null ?>>Exclusive</option>
                        <option value="inclusive" <?= $plan->gst_type === "inclusive" ? 'selected' : null ?>>Inclusive</option>
                    </select>
                </div>

                <div class="col-sm-12 mb-5">
                    <label class="required form-label">Feature Lists</label>
                    <textarea name="feature_lists" class="form-control form-control-solid" rows="10"><?= $plan->feature_lists ?></textarea>

                    <small class="d-block mt-2">Remember to add a comma <mark>,</mark> after listing a feature</small>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Update Plan</button>
                </div>
            </form>
        </div>
        <!--end::Content container-->
    </div>
    <!--end::Content-->
</div>
<!--end::Content wrapper-->

<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->

<!--begin::Vendors Javascript(used for this page only)-->
<script src="assets/plugins/custom/jquery/jquery-3.7.1.min.js"></script>
<!--end::Vendors Javascript-->
<!--begin::Custom Javascript(used for this page only)-->
<script src="assets/js/custom/subscription-plans/update.js"></script>
<!--end::Custom Javascript-->