<?php $settings = fetchSettings(); ?>

<!--begin::Footer-->
<div
    id="kt_app_footer"
    class="app-footer align-items-center justify-content-center justify-content-md-between flex-column flex-md-row py-3">
    <!--begin::Copyright-->
    <div class="text-dark order-2 order-md-1">
        <span class="text-muted fw-semibold me-1"><?= date("Y") ?></span>
        <a
            href="javascript:void(0)"
            target="_blank"
            class="text-gray-800 text-hover-primary">@<?= $settings->app_name ?></a>
    </div>
    <!--end::Copyright-->
</div>
<!--end::Footer-->
</div>
<!--end:::Main-->
</div>
<!--end::Wrapper-->
</div>
<!--end::Page-->
</div>
<!--end::App-->
<!--begin::Scrolltop-->
<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
    <i class="ki-duotone ki-arrow-up">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</div>
<!--end::Scrolltop-->
<!--begin::Javascript-->
<script>
    var hostUrl = "assets/";
</script>
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<!--end::Global Javascript Bundle-->

<!--end::Javascript-->
</body>
<!--end::Body-->

</html>