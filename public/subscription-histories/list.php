<!--begin:Header-->
<?php
require_once '../../src/functions.php';
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
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">Subscription Histories</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Subscription Histories</h1>
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
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search Box-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" class="form-control form-control-solid w-350px ps-13" placeholder="Search payment ID, customer, plan..." id="searchFilter" />
                        </div>
                        <!--end::Search Box-->
                    </div>
                    <!--begin::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end align-items-center gap-3" data-kt-user-table-toolbar="base">
                            <!--begin::Applied Filters (with X buttons)-->
                            <div class="d-flex align-items-center gap-2" id="appliedFilters">
                                <!-- Applied filters will appear here -->
                            </div>
                            <!--end::Applied Filters-->

                            <!--begin::Filter Dropdown Button-->
                            <button type="button" class="btn btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-filter fs-2 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span class="d-none d-md-inline">More Filters</span>
                            </button>
                            <!--end::Filter Dropdown Button-->

                            <!--begin::Filter Menu-->
                            <div class="menu menu-sub menu-sub-dropdown w-350px w-md-400px" data-kt-menu="true">
                                <!--begin::Header-->
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-dark fw-bold">Filter Options</div>
                                </div>
                                <!--end::Header-->
                                <!--begin::Separator-->
                                <div class="separator border-gray-200"></div>
                                <!--end::Separator-->
                                <!--begin::Content-->
                                <div class="px-7 py-5" data-kt-user-table-filter="form">
                                    <!--begin::Grid Container for Filters-->
                                    <div class="row g-5">
                                        <!--Plan Filter-->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fs-6 fw-semibold">Plan Name</label>
                                            <select class="form-select form-select-solid fw-bold" id="planFilter">
                                                <option value="">All</option>
                                                <?php
                                                $data = fetchSubscriptionPlans();
                                                foreach ($data as $row):
                                                ?>
                                                    <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!--GST Filter-->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fs-6 fw-semibold">GST Status</label>
                                            <select class="form-select form-select-solid fw-bold" id="gstFilter">
                                                <option value="">All</option>
                                                <option value="yes">Yes (GST Provided)</option>
                                                <option value="no">No (No GST)</option>
                                            </select>
                                        </div>

                                        <!--Payment Method Filter-->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fs-6 fw-semibold">Payment Method</label>
                                            <select class="form-select form-select-solid fw-bold" id="paymentMethodFilter">
                                                <option value="">All</option>
                                                <option value="razorpay">Razorpay</option>
                                                <option value="phone pay">Phone Pay</option>
                                                <option value="payu">PayU</option>
                                                <option value="manual">Manual Payment</option>
                                            </select>
                                        </div>

                                        <!--Status Filter - NEW -->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fs-6 fw-semibold">Status</label>
                                            <select class="form-select form-select-solid fw-bold" id="statusFilter">
                                                <option value="">All</option>
                                                <option value="active">Active</option>
                                                <option value="refunded">Refunded</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>

                                        <!--Date Range Filter - Full Width-->
                                        <div class="col-12">
                                            <label class="form-label fs-6 fw-semibold">Date Range</label>
                                            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                                                <div class="flex-fill">
                                                    <input type="date" class="form-control form-control-solid" id="startDateFilter" placeholder="Start Date" />
                                                </div>
                                                <span class="text-muted mx-2 d-none d-md-block">to</span>
                                                <div class="flex-fill">
                                                    <input type="date" class="form-control form-control-solid" id="endDateFilter" placeholder="End Date" />
                                                </div>
                                            </div>
                                            <div class="form-text text-muted mt-1">Leave empty for all dates</div>
                                        </div>
                                    </div>
                                    <!--end::Grid Container-->

                                    <!--begin::Actions-->
                                    <div class="d-flex justify-content-between align-items-center mt-8 pt-5 border-top">
                                        <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold" id="resetAllFiltersBtn">
                                            Reset All
                                        </button>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-light btn-active-light-primary fw-semibold" data-kt-menu-dismiss="true">
                                                Close
                                            </button>
                                            <button type="button" class="btn btn-primary fw-semibold" data-kt-menu-dismiss="true" id="applyFiltersBtn">
                                                Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                    <!--end::Actions-->
                                </div>
                                <!--end::Content-->
                            </div>
                            <!--end::Filter Menu-->
                        </div>
                        <!--end::Toolbar-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body py-4">
                    <!--begin::Table-->
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_subscription_histories">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_subscription_histories .form-check-input" value="1" />
                                    </div>
                                </th>
                                <th class="min-w-125px">Invoice No</th>
                                <th class="min-w-200px">Customer Details</th>
                                <th class="min-w-150px">Plan Name</th>
                                <th class="min-w-125px">Amount</th>
                                <th class="min-w-125px">Payment Method</th>
                                <th class="min-w-150px">Payment ID</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                        </tbody>
                    </table>
                    <!--end::Table-->
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

<style>
    .applied-filter-badge {
        padding: 5px 12px;
        border-radius: 6px;
        background-color: var(--bs-light);
        border: 1px solid var(--bs-gray-300);
        font-size: 0.875rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .applied-filter-badge .remove-filter {
        cursor: pointer;
        color: var(--bs-danger);
        font-size: 1rem;
        line-height: 1;
        padding: 0 2px;
        border-radius: 3px;
    }

    .applied-filter-badge .remove-filter:hover {
        background-color: var(--bs-danger);
        color: white;
    }
</style>

<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->
<!-- Toastr -->
<link href="assets/plugins/custom/toastr/toastr.min.css" rel="stylesheet" type="text/css" />
<script src="assets/plugins/custom/toastr/toastr.min.js"></script>
<!--begin::Vendors Javascript(used for this page only)-->
<script src="assets/plugins/custom/jquery/jquery-3.7.1.min.js"></script>
<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<!--end::Vendors Javascript-->
<!--begin::Custom Javascript(used for this page only)-->
<script src="assets/js/custom/subscription-histories/list.js"></script>
<!--end::Custom Javascript-->