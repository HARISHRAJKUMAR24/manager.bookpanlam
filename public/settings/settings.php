<!--begin:Header-->
<?php
require_once '../../src/database.php';
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
                        <li class="breadcrumb-item text-gray-700">Settings</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Settings</h1>
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
            <!--begin::Row-->
            <div class="row g-6">
                <!--begin::General Settings-->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-flush h-100 bg-light-primary">
                        <!--begin::Card body-->
                        <div class="card-body d-flex flex-column justify-content-between">
                            <!--begin::Header-->
                            <div class="mb-5">
                                <!--begin::Icon-->
                                <div class="symbol symbol-60px symbol-circle mb-4">
                                    <span class="symbol-label bg-light-primary">
                                        <i class="ki-duotone ki-setting-3 fs-2x text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                        </i>
                                    </span>
                                </div>
                                <!--end::Icon-->
                                <!--begin::Title-->
                                <h3 class="fw-bold text-gray-800 mb-2">General</h3>
                                <!--end::Title-->
                                <!--begin::Description-->
                                <p class="text-gray-600 fs-6">Manage website name, logo, favicon, etc</p>
                                <!--end::Description-->
                            </div>
                            <!--end::Header-->
                            <!--begin::Footer-->
                            <div class="pt-4 border-top">
                                <a href="settings/general" class="btn btn-light-primary w-100">
                                    Click to Manage
                                    <i class="ki-duotone ki-arrow-right fs-3 ms-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </a>
                            </div>
                            <!--end::Footer-->
                        </div>
                        <!--end::Card body-->
                    </div>
                </div>
                <!--end::General Settings-->

                <!--begin::Currencies-->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-flush h-100 bg-light-success">
                        <!--begin::Card body-->
                        <div class="card-body d-flex flex-column justify-content-between">
                            <!--begin::Header-->
                            <div class="mb-5">
                                <!--begin::Icon-->
                                <div class="symbol symbol-60px symbol-circle mb-4">
                                    <span class="symbol-label bg-light-success">
                                        <i class="ki-duotone ki-dollar fs-2x text-success">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                </div>
                                <!--end::Icon-->
                                <!--begin::Title-->
                                <h3 class="fw-bold text-gray-800 mb-2">Currencies</h3>
                                <!--end::Title-->
                                <!--begin::Description-->
                                <p class="text-gray-600 fs-6">Manage sellers store currencies</p>
                                <!--end::Description-->
                            </div>
                            <!--end::Header-->
                            <!--begin::Footer-->
                            <div class="pt-4 border-top">
                                <a href="settings/currency" class="btn btn-light-success w-100">
                                    Click to Manage
                                    <i class="ki-duotone ki-arrow-right fs-3 ms-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </a>
                            </div>
                            <!--end::Footer-->
                        </div>
                        <!--end::Card body-->
                    </div>
                </div>
                <!--end::Currencies-->

                <!--begin::Languages-->
                <!-- <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-flush h-100 bg-light-warning"> -->
                <!--begin::Card body-->
                <!-- <div class="card-body d-flex flex-column justify-content-between"> -->
                <!--begin::Header-->
                <!-- <div class="mb-5"> -->
                <!--begin::Icon-->
                <!-- <div class="symbol symbol-60px symbol-circle mb-4">
                                    <span class="symbol-label bg-light-warning">
                                        <i class="ki-duotone ki-message-text-2 fs-2x text-warning">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                </div> -->
                <!--end::Icon-->
                <!--begin::Title-->
                <!-- <h3 class="fw-bold text-gray-800 mb-2">Languages</h3> -->
                <!--end::Title-->
                <!--begin::Description-->
                <!-- <p class="text-gray-600 fs-6">Manage sellers store languages</p> -->
                <!--end::Description-->
                <!-- </div> -->
                <!--end::Header-->
                <!--begin::Footer-->
                <!-- <div class="pt-4 border-top">
                                <a href="languages" class="btn btn-light-warning w-100">
                                    Click to Manage
                                    <i class="ki-duotone ki-arrow-right fs-3 ms-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </a>
                            </div> -->
                <!--end::Footer-->
                <!-- </div> -->
                <!--end::Card body-->
                <!-- </div> -->
                <!-- </div> -->
                <!--end::Languages-->

                <!--begin::Taxes-->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-flush h-100 bg-light-danger">
                        <!--begin::Card body-->
                        <div class="card-body d-flex flex-column justify-content-between">
                            <!--begin::Header-->
                            <div class="mb-5">
                                <!--begin::Icon-->
                                <div class="symbol symbol-60px symbol-circle mb-4">
                                    <span class="symbol-label bg-light-danger">
                                        <i class="ki-duotone ki-chart-simple-2 fs-2x text-danger">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                </div>
                                <!--end::Icon-->
                                <!--begin::Title-->
                                <h3 class="fw-bold text-gray-800 mb-2">Taxes</h3>
                                <!--end::Title-->
                                <!--begin::Description-->
                                <p class="text-gray-600 fs-6">Manage taxes such as GST</p>
                                <!--end::Description-->
                            </div>
                            <!--end::Header-->
                            <!--begin::Footer-->
                            <div class="pt-4 border-top">
                                <a href="settings/taxes" class="btn btn-light-danger w-100">
                                    Click to Manage
                                    <i class="ki-duotone ki-arrow-right fs-3 ms-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </a>
                            </div>
                            <!--end::Footer-->
                        </div>
                        <!--end::Card body-->
                    </div>
                </div>
                <!--end::Taxes-->

                <!--begin::Payments-->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-flush h-100 bg-light-info">
                        <!--begin::Card body-->
                        <div class="card-body d-flex flex-column justify-content-between">
                            <!--begin::Header-->
                            <div class="mb-5">
                                <!--begin::Icon-->
                                <div class="symbol symbol-60px symbol-circle mb-4">
                                    <span class="symbol-label bg-light-info">
                                        <i class="ki-duotone ki-credit-cart fs-2x text-info">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                </div>
                                <!--end::Icon-->
                                <!--begin::Title-->
                                <h3 class="fw-bold text-gray-800 mb-2">Payments</h3>
                                <!--end::Title-->
                                <!--begin::Description-->
                                <p class="text-gray-600 fs-6">Manage payment methods and gateways</p>
                                <!--end::Description-->
                            </div>
                            <!--end::Header-->
                            <!--begin::Footer-->
                            <div class="pt-4 border-top">
                                <a href="settings/payments" class="btn btn-light-info w-100">
                                    Click to Manage
                                    <i class="ki-duotone ki-arrow-right fs-3 ms-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </a>
                            </div>
                            <!--end::Footer-->
                        </div>
                        <!--end::Card body-->
                    </div>
                </div>
                <!--end::Payments-->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Content container-->
    </div>
    <!--end::Content-->
</div>
<!--end::Content wrapper-->

<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->