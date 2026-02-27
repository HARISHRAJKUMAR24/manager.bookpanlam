<!--begin:Header-->
<?php
require_once '../../src/functions.php';
require_once '../../src/database.php';
renderTemplate('header');

$pdo = getDbConnection();

// Get domain statistics
$stats = getDomainStats($pdo);
?>
<!--end:Header-->

<!--begin::Content wrapper-->
<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar pt-5">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex align-items-stretch">
            <div class="app-toolbar-wrapper d-flex flex-stack flex-wrap gap-4 w-100">
                <div class="page-title d-flex flex-column gap-1 me-3 mb-2">
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold mb-6">
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                            <a href="<?= BASE_URL ?>" class="text-gray-500">
                                <i class="ki-duotone ki-home fs-3 text-gray-400 me-n1"></i>
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <i class="ki-duotone ki-right fs-4 text-gray-700 mx-n1"></i>
                        </li>
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">Custom Domain Requests</li>
                    </ul>
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">
                        Custom Domain Management
                    </h1>
                </div>
            </div>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            
            <!-- Statistics Cards -->
            <div class="row g-5 g-xl-8 mb-5">
                <!-- Total Requests -->
                <div class="col-md-2">
                    <div class="card card-dashed bg-light-primary">
                        <div class="card-body d-flex align-items-center p-6">
                            <i class="ki-duotone ki-global fs-2x text-primary me-4"></i>
                            <div>
                                <span class="text-gray-600 fw-semibold fs-7 d-block">Total Requests</span>
                                <span class="fs-2x fw-bold text-dark total-count"><?= $stats['total'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pending -->
                <div class="col-md-2">
                    <div class="card card-dashed bg-light-warning">
                        <div class="card-body d-flex align-items-center p-6">
                            <i class="ki-duotone ki-clock fs-2x text-warning me-4"></i>
                            <div>
                                <span class="text-gray-600 fw-semibold fs-7 d-block">Pending</span>
                                <span class="fs-2x fw-bold text-dark pending-count"><?= $stats['pending'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Active -->
                <div class="col-md-2">
                    <div class="card card-dashed bg-light-success">
                        <div class="card-body d-flex align-items-center p-6">
                            <i class="ki-duotone ki-check-circle fs-2x text-success me-4"></i>
                            <div>
                                <span class="text-gray-600 fw-semibold fs-7 d-block">Active</span>
                                <span class="fs-2x fw-bold text-dark active-count"><?= $stats['active'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Inactive -->
                <div class="col-md-2">
                    <div class="card card-dashed bg-light-secondary">
                        <div class="card-body d-flex align-items-center p-6">
                            <i class="ki-duotone ki-cross-circle fs-2x text-secondary me-4"></i>
                            <div>
                                <span class="text-gray-600 fw-semibold fs-7 d-block">Inactive</span>
                                <span class="fs-2x fw-bold text-dark inactive-count"><?= $stats['inactive'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Rejected -->
                <div class="col-md-2">
                    <div class="card card-dashed bg-light-danger">
                        <div class="card-body d-flex align-items-center p-6">
                            <i class="ki-duotone ki-cross-circle fs-2x text-danger me-4"></i>
                            <div>
                                <span class="text-gray-600 fw-semibold fs-7 d-block">Rejected</span>
                                <span class="fs-2x fw-bold text-dark rejected-count"><?= $stats['rejected'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

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
                            <input type="text" class="form-control form-control-solid w-350px ps-13" placeholder="Search users, domains..." id="searchFilter" />
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
                                        <!--Status Filter-->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fs-6 fw-semibold">Status</label>
                                            <select class="form-select form-select-solid fw-bold" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="pending">Pending</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="rejected">Rejected</option>
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
                                            <div class="form-text text-muted mt-1">Filter by requested date</div>
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
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_custom_domains">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-100px">User ID</th>
                                <th class="min-w-200px">User Details</th>
                                <th class="min-w-150px">Site Name</th>
                                <th class="min-w-150px">Requested Domain</th>
                                <th class="min-w-150px">Requested Date</th>
                                <th class="min-w-100px">Status</th>
                                <th class="text-end min-w-150px">Actions</th>
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

<!-- Toastr -->
<link href="assets/plugins/custom/toastr/toastr.min.css" rel="stylesheet" type="text/css" />
<script src="assets/plugins/custom/toastr/toastr.min.js"></script>

<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->

<!--begin::Vendors Javascript-->
<script src="assets/plugins/custom/jquery/jquery-3.7.1.min.js"></script>
<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<!--end::Vendors Javascript-->

<!--begin::Custom Javascript-->
<script src="assets/js/custom/custom-domain/list.js"></script>
<!--end::Custom Javascript-->