<?php
require_once '../../config/config.php';
require_once '../../src/database.php';
require_once '../../src/functions.php';

if (isLoggedIn()) header("Location: " . BASE_URL);

$settings = fetchSettings();

?>


<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->
<?php renderTemplate("head") ?>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="app-blank">
	<!--begin::Theme mode setup on page load-->
	<script>
		var defaultThemeMode = "light";
		var themeMode;
		if (document.documentElement) {
			if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
				themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
			} else {
				if (localStorage.getItem("data-bs-theme") !== null) {
					themeMode = localStorage.getItem("data-bs-theme");
				} else {
					themeMode = defaultThemeMode;
				}
			}
			if (themeMode === "system") {
				themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
			}
			document.documentElement.setAttribute("data-bs-theme", themeMode);
		}
	</script>
	<!--end::Theme mode setup on page load-->
	<!--begin::Root-->
	<div class="d-flex flex-column flex-root" id="kt_app_root">
		<!--begin::Authentication - Sign-in -->
		<div class="d-flex flex-column flex-lg-row flex-column-fluid">
			<!--begin::Aside-->
			<div class="d-flex flex-column flex-lg-row-auto bg-primary w-xl-600px positon-xl-relative">
				<!--begin::Wrapper-->
				<div class="d-flex flex-column position-xl-fixed top-0 bottom-0 w-xl-600px scroll-y">
					<!--begin::Header-->
					<div class="d-flex flex-row-fluid flex-column text-center p-5 p-lg-10 pt-lg-20">
						<!--begin::Logo-->
						<a href="<?= BASE_URL ?>" class="py-2 py-lg-20">
							<img alt="Logo" src="assets/media/logos/1m_logo.png" class="h-60px h-lg-70px" />
						</a>
						<!--end::Logo-->
						<!--begin::Title-->
						<h1 class="d-none d-lg-block fw-bold text-white fs-2qx pb-5 pb-md-10">Welcome to <?= $settings->app_name ?></h1>
						<!--end::Title-->
						<!--begin::Description-->
						<p class="d-none d-lg-block fw-semibold fs-2 text-white">Log In to Your Manager Dashboard for Enhanced Insights and Management Tools
						</p>
						<!--end::Description-->
					</div>
					<!--end::Header-->
					<!--begin::Illustration-->
					<div class="d-none d-lg-block d-flex flex-row-auto bgi-no-repeat bgi-position-x-center bgi-size-contain bgi-position-y-bottom min-h-100px min-h-lg-350px" style="background-image: url(assets/media/illustrations/sketchy-1/17.png)"></div>
					<!--end::Illustration-->
				</div>
				<!--end::Wrapper-->
			</div>
			<!--begin::Aside-->
			<!--begin::Body-->
			<div class="d-flex flex-column flex-lg-row-fluid py-10">
				<!--begin::Content-->
				<div class="d-flex flex-center flex-column flex-column-fluid">
					<!--begin::Wrapper-->
					<div class="w-lg-500px p-10 p-lg-15 mx-auto">
						<!--begin::Form-->
						<form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" data-kt-redirect-url="<?= BASE_URL ?>" action="<?= BASE_URL ?>ajax/authentication/sign-in.php">
							<!--begin::Heading-->
							<div class="text-center mb-10">
								<!--begin::Title-->
								<h1 class="text-dark mb-3">Sign In to <?= $settings->app_name ?> Portal</h1>
								<!--end::Title-->
							</div>
							<!--begin::Heading-->
							<!--begin::Input group-->
							<div class="fv-row mb-10">
								<!--begin::Label-->
								<label class="form-label fs-6 fw-bold text-dark">Email</label>
								<!--end::Label-->
								<!--begin::Input-->
								<input class="form-control form-control-lg form-control-solid" type="text" name="email" autocomplete="off" />
								<!--end::Input-->
							</div>
							<!--end::Input group-->
							<!--begin::Input group-->
							<div class="fv-row mb-10">
								<!--begin::Wrapper-->
								<div class="d-flex flex-stack mb-2">
									<!--begin::Label-->
									<label class="form-label fw-bold text-dark fs-6 mb-0">Password</label>
									<!--end::Label-->
								</div>
								<!--end::Wrapper-->
								<!--begin::Input-->
								<input class="form-control form-control-lg form-control-solid" type="password" name="password" autocomplete="off" />
								<!--end::Input-->
							</div>
							<!--end::Input group-->
							<!--begin::Actions-->
							<div class="text-center">
								<!--begin::Submit button-->
								<button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-5">
									<span class="indicator-label">Continue</span>
									<span class="indicator-progress">Please wait...
										<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
								</button>
								<!--end::Submit button-->
							</div>
							<!--end::Actions-->
						</form>
						<!--end::Form-->
					</div>
					<!--end::Wrapper-->
				</div>
				<!--end::Content-->
			</div>
			<!--end::Body-->
		</div>
		<!--end::Authentication - Sign-in-->
	</div>
	<!--end::Root-->
	<!--begin::Javascript-->
	<script>
		var hostUrl = "assets/";
	</script>
	<!--begin::Global Javascript Bundle(mandatory for all pages)-->
	<script src="assets/plugins/global/plugins.bundle.js"></script>
	<script src="assets/js/scripts.bundle.js"></script>
	<!--end::Global Javascript Bundle-->
	<!--begin::Custom Javascript(used for this page only)-->
	<script src="assets/js/custom/authentication/sign-in.js"></script>
	<!--end::Custom Javascript-->
	<!--end::Javascript-->
</body>
<!--end::Body-->

</html>