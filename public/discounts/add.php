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
                            <a href="discounts" class="text-gray-500">
                                Discounts
                            </a>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <i class="ki-duotone ki-right fs-4 text-gray-700 mx-n1"></i>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">Add Discount</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Add Discount</h1>
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
            <form action="<?= BASE_URL ?>ajax/discounts/add.php" id="addForm" class="row">

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Code</label>
                    <input type="text" name="code" class="form-control form-control-solid" />
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Type</label>
                    <select name="type" class="form-select form-select-solid">
                        <option value="fixed">Fixed</option>
                        <option value="percentage">Percentage</option>
                    </select>
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Discount</label>
                    <input type="number" name="discount" class="form-control form-control-solid" />
                </div>

                <div class="col-sm-6 mb-5">
                    <label class="required form-label">Eligibility</label>
                    <select name="eligibility" class="form-select form-select-solid">
                        <option value="">Everyone</option>
                        <?php
                        $data = fetchSubscriptionPlans();
                        foreach ($data as $row):
                        ?>
                            <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Add Staff</button>
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
<script src="assets/js/custom/discounts/add.js"></script>
<!--end::Custom Javascript-->