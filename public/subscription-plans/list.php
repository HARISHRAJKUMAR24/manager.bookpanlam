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
                        <li class="breadcrumb-item text-gray-700 fw-bold lh-1">Subscription Plans</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Subscription Plans</h1>

                        <div class="d-flex align-items-center gap-3">
                            <!-- Button Style Toggle -->
                            <div class="btn-group btn-group-sm" role="group" aria-label="Plan type toggle">
                                <button type="button" class="btn btn-primary plan-toggle-btn active" data-type="monthly">
                                    <i class="ki-duotone ki-calendar-8 me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Monthly
                                </button>
                                <button type="button" class="btn btn-light plan-toggle-btn" data-type="yearly">
                                    <i class="ki-duotone ki-calendar me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Yearly
                                </button>
                            </div>

                            <a href="subscription-plans/add" class="btn btn-sm btn-primary">
                                <i class="ki-duotone ki-plus me-2"></i>
                                Add Plan
                            </a>
                        </div>
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
            <!-- Plans Container -->
            <div id="plansContainer" class="row">
                <?php
                $data = fetchSubscriptionPlans();

                // Show all plans initially (will be filtered by JavaScript)
                foreach ($data as $row):
                    // Determine plan type based on duration
                    $is_monthly = ($row['duration'] % 30 === 0) && ($row['duration'] % 365 !== 0);
                    $is_yearly = ($row['duration'] % 365 === 0);
                    $plan_type = $is_monthly ? 'monthly' : ($is_yearly ? 'yearly' : 'other');
                ?>
                    <div class="col-lg-3 plan-card" data-plan-type="<?= $plan_type ?>">
                        <div class="card border shadow-none mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="text-start text-uppercase mb-0"><?= $row['name'] ?></h5>
                                    <span class="badge badge-<?= $plan_type === 'monthly' ? 'info' : 'success' ?> plan-badge">
                                        <?= $plan_type === 'monthly' ? 'Monthly' : 'Yearly' ?>
                                    </span>
                                </div>

                                <div class="text-center position-relative mb-2 pb-1">
                                    <div class="mb-2 d-flex">
                                        <h1 class="price-toggle text-primary price-yearly mb-0 d-flex">
                                            <?= getCurrencySymbol($settings->currency) . ' ' . $row['amount'] ?>
                                        </h1>
                                        <sub class="h5 text-muted pricing-duration mt-auto mb-2">
                                            /<?= convertDurationForDisplay($row['duration']) ?>
                                        </sub>
                                    </div>
                                </div>

                                <p><?= $row['description'] ?></p>

                                <hr>

                                <ul class="list-unstyled pt-2 pb-1">
                                    <?php foreach (explode(',', $row['feature_lists']) as $item): ?>
                                        <li class="mb-2">
                                            <span class="badge badge-center w-px-20 h-px-20 rounded-pill bg-label-primary me-2">
                                                <i class="ki-duotone ki-check-circle" style="font-size: 1.2rem;">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </span>
                                            <?= trim($item) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="d-grid gap-2">
                                    <a href="subscription-plans/<?= $row['plan_id'] ?>" class="btn btn-info">
                                        <i class="ki-duotone ki-pencil me-2"></i>
                                        Update
                                    </a>
                                    <button class="toggleIsDisabled btn btn-<?= $row['is_disabled'] ? 'danger' : 'success' ?>" data-id="<?= $row['id'] ?>">
                                        <i class="ki-duotone ki-<?= $row['is_disabled'] ? 'cross' : 'check' ?> me-2"></i>
                                        <?= $row['is_disabled'] ? "Disabled" : "Enabled" ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- No Plans Message (Hidden by default) -->
            <div id="noPlansMessage" class="d-none">
                <div class="col-12">
                    <div class="alert alert-info d-flex align-items-center p-5">
                        <i class="ki-duotone ki-information fs-2x me-3 text-info">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-6">No plans found</span>
                            <span class="text-gray-600">There are no subscription plans available for the selected view. Click "Add Plan" to create one.</span>
                        </div>
                    </div>
                </div>
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

<!--begin::Vendors Javascript(used for this page only)-->
<script src="assets/plugins/custom/jquery/jquery-3.7.1.min.js"></script>
<!--end::Vendors Javascript-->
<!--begin::Custom Javascript(used for this page only)-->
<script>
    $(document).ready(function() {
        // Default: Show monthly plans
        filterPlans('monthly');

        // Button toggle event
        $('.plan-toggle-btn').on('click', function() {
            var type = $(this).data('type');

            // Update button styles
            $('.plan-toggle-btn').removeClass('btn-primary active').addClass('btn-light');
            $(this).removeClass('btn-light').addClass('btn-primary active');

            // Filter plans
            filterPlans(type);
        });

        function filterPlans(type) {
            var hasVisiblePlans = false;

            // Show/hide plans based on type
            $('.plan-card').each(function() {
                var planType = $(this).data('plan-type');

                if (planType === type) {
                    $(this).show();
                    hasVisiblePlans = true;
                } else {
                    $(this).hide();
                }
            });

            // Show/hide no plans message
            if (hasVisiblePlans) {
                $('#noPlansMessage').addClass('d-none');
                $('#plansContainer').removeClass('d-none');
            } else {
                $('#plansContainer').addClass('d-none');
                $('#noPlansMessage').removeClass('d-none');

                // Update message text
                $('#noPlansMessage .fw-bold').text('No ' + type + ' plans found');
                $('#noPlansMessage .text-gray-600').text('There are no ' + type + ' subscription plans available. Click "Add Plan" to create one.');
            }
        }

        // Keep existing toggleIsDisabled functionality
        $(document).on("click", ".toggleIsDisabled", function() {
            const id = $(this).data("id");
            const element = $(this);

            $.ajax({
                url: `${BASE_URL}ajax/subscription-plans/list.php`,
                type: "POST",
                data: {
                    planId: id,
                },
                success: function(data) {
                    if (element.text().trim() === "Disabled") {
                        element.html('<i class="ki-duotone ki-check me-2"></i>Enabled');
                        element.removeClass("btn-danger");
                        element.addClass("btn-success");
                    } else {
                        element.html('<i class="ki-duotone ki-cross me-2"></i>Disabled');
                        element.removeClass("btn-success");
                        element.addClass("btn-danger");
                    }

                    Swal.fire({
                        text: "Action applied successfully!",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary",
                        },
                    });
                },
            });
        });
    });
</script>
<!--end::Custom Javascript-->