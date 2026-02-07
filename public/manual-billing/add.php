<!--begin:Header-->
<?php
require_once '../../src/functions.php';
renderTemplate('header');

$settings = fetchSettings();
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
                            <a href="manual-billing" class="text-gray-500">
                                Manual Billing
                            </a>
                        </li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
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
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card body-->
                <div class="card-body p-9">
                    <form id="manualBillingForm" class="row">
                        <!-- Plan & Seller -->
                        <div class="col-sm-6 mb-5">
                            <label class="required form-label">Plan</label>
                            <select name="plan_id" id="planSelect" class="form-select form-select-lg form-select-solid" data-control="select2" data-placeholder="Search and select plan" required>
                                <option value="">Select plan...</option>
                            </select>
                            <div class="form-text">Search plan by name or amount</div>
                        </div>

                        <div class="col-sm-6 mb-5">
                            <label class="required form-label">User</label>
                            <select name="user_id" id="userSelect" class="form-select form-select-lg form-select-solid" data-control="select2" data-placeholder="Search and select user">
                                <option value="">Select User...</option>
                            </select>
                            <div class="form-text">Search by ID, name, email, or mobile</div>
                        </div>

                        <!-- Amount & Duration -->
                        <div class="col-sm-6 mb-5">
                            <label class="required form-label">Amount</label>
                            <input type="number" name="amount" class="form-control form-control-lg form-control-solid" required min="1">
                            <div class="form-text">Amount in <span id="currencyDisplay">INR</span></div>
                        </div>

                        <!-- Change the duration field section in add.php -->
                        <div class="col-sm-6 mb-5">
                            <label class="form-label">Duration</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="duration_value" class="form-control form-control-solid"
                                        placeholder="1" min="1" max="100" required />
                                    <div class="form-text text-gray-600">Duration value</div>
                                </div>
                                <div class="col-6">
                                    <select name="duration_type" class="form-select form-select-solid" required>
                                        <option value="month">Month</option>
                                        <option value="year" selected>Year</option>
                                    </select>
                                    <div class="form-text text-gray-600">Time unit</div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment -->
                        <div class="col-sm-6 mb-5">
                            <label class="required form-label">Payment Method</label>
                            <select name="payment_method" id="paymentMethodSelect" class="form-select form-select-lg form-select-solid" data-control="select2" data-placeholder="Search and select payment method" required>

                            </select>
                        </div>

                        <div class="col-sm-6 mb-5">
                            <label class="form-label">Upload Transaction Screenshot</label>
                            <input type="file"
                                id="transactionImage"
                                accept="image/png,image/jpeg"
                                class="form-control form-control-lg form-control-solid">
                            <div class="form-text">
                                Upload UPI screenshot to auto-detect Transaction ID
                            </div>
                        </div>

                        <div class="col-sm-6 mb-5">
                            <label class="form-label">Payment ID / Reference</label>
                            <input type="text" name="payment_id" class="form-control form-control-lg form-control-solid" placeholder="Transaction ID, UPI Reference, etc." required>
                        </div>

                        <!-- Customer Details -->
                        <div class="col-12 mb-10">
                            <div class="d-flex align-items-center mb-5">
                                <i class="ki-duotone ki-user-tick fs-2x text-primary me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <h3 class="fw-bold text-gray-900 mb-0">Customer Details</h3>
                            </div>
                        </div>

                        <div class="col-sm-4 mb-5">
                            <label class="required form-label">Full Name</label>
                            <input type="text" name="customer_name" class="form-control form-control-lg form-control-solid" required>
                        </div>

                        <div class="col-sm-4 mb-5">
                            <label class="required form-label">Email Address</label>
                            <input type="email" name="customer_email" class="form-control form-control-lg form-control-solid" required>
                        </div>

                        <div class="col-sm-4 mb-5">
                            <label class="required form-label">Mobile Number</label>
                            <input type="text" name="customer_mobile" class="form-control form-control-lg form-control-solid" required pattern="[0-9]{10}" maxlength="10">
                            <div class="form-text">10-digit mobile number</div>
                        </div>

                        <!-- Address -->
                        <div class="col-12 mb-10">
                            <div class="d-flex align-items-center mb-5">
                                <i class="ki-duotone ki-location fs-2x text-primary me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <h3 class="fw-bold text-gray-900 mb-0">Address Details</h3>
                            </div>
                        </div>

                        <div class="col-sm-4 mb-5">
                            <label class="required form-label">State</label>
                            <input type="text" name="state" class="form-control form-control-lg form-control-solid" required placeholder="Tamil Nadu">
                        </div>

                        <div class="col-sm-4 mb-5">
                            <label class="required form-label">City</label>
                            <input type="text" name="city" class="form-control form-control-lg form-control-solid" required placeholder="Chennai">
                        </div>

                        <div class="col-sm-4 mb-5">
                            <label class="required form-label">Pincode</label>
                            <input type="text" name="pincode" class="form-control form-control-lg form-control-solid" required pattern="[0-9]{6}" maxlength="6" placeholder="600001">
                            <div class="form-text">6-digit postal code</div>
                        </div>

                        <div class="col-sm-6 mb-5">
                            <label class="required form-label">Address Line 1</label>
                            <input type="text" name="address_1" class="form-control form-control-lg form-control-solid" required>
                        </div>

                        <div class="col-sm-6 mb-5">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="address_2" class="form-control form-control-lg form-control-solid">
                        </div>

                        <!-- Currency & Country -->
                        <div class="col-sm-6 mb-5">
                            <label class="required form-label">Currency</label>
                            <select name="currency" id="currencySelect" class="form-select form-select-lg form-select-solid" data-control="select2" data-placeholder="Search and select currency" required>
                                <option value="">Select currency...</option>
                                <!-- Currencies will be loaded dynamically -->
                            </select>
                        </div>

                        <div class="col-sm-6 mb-5">
                            <label class="required form-label">Country Code</label>
                            <select name="country_code" id="countrySelect" class="form-select form-select-lg form-select-solid" data-control="select2" data-placeholder="Search and select country" required>
                                <option value="">Select country...</option>
                                <option value="IN" selected>India (+91)</option>
                                <option value="US">United States (+1)</option>
                                <option value="GB">United Kingdom (+44)</option>
                                <option value="AE">United Arab Emirates (+971)</option>
                                <option value="SG">Singapore (+65)</option>
                                <option value="MY">Malaysia (+60)</option>
                            </select>
                        </div>

                        <div class="col-sm-6 mb-5">
                            <label class="form-label">GST Number (Optional)</label>
                            <div class="position-relative">
                                <input type="text"
                                    name="gst_number"
                                    id="gstNumber"
                                    class="form-control form-control-lg form-control-solid"
                                    placeholder="33AAACP1935C1Z0"
                                    maxlength="15"
                                    pattern="[0-9A-Za-z]{15}"
                                    title="15-character GST number">
                                <div class="position-absolute end-0 top-0 mt-2 me-3">
                                    <span id="gstStatusIcon" style="display: none;"></span>
                                </div>
                            </div>
                            <div class="form-text">Enter 15-character GSTIN for tax invoice (Optional)</div>
                            <div id="gstValidationMessage" class="mt-1 small"></div>
                        </div>

                        <!-- Form Actions -->
                        <div class="col-12 mt-10">
                            <div class="d-flex justify-content-end gap-3">
                                <button type="button" class="btn btn-light" onclick="resetForm()">
                                    <i class="ki-duotone ki-refresh fs-2 me-2"></i>Reset Form
                                </button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span class="indicator-label">
                                        <i class="ki-duotone ki-check-square fs-2 me-2"></i>Create Manual Billing
                                    </span>
                                    <span class="indicator-progress">
                                        Processing... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Content container-->
    </div>
    <!--end::Content-->
</div>
<!--end::Content wrapper-->

<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>


<!--begin::Custom Javascript(used for this page only)-->
<script src="<?= BASE_URL ?>assets/js/custom/manual-billing/manual-billing.js"></script>
<!--end::Custom Javascript-->