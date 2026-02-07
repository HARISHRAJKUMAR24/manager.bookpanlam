<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/database.php';

if (!isLoggedIn()) exit(header("Location: " . BASE_URL . "auth/sign-in"));
$user = fetchCurrentUser();

$page = $_SERVER['REQUEST_URI'];
if (!isAdmin() && (str_contains($page, 'staffs') || str_contains($page, 'settings'))) {
    exit(header("Location: " . BASE_URL));
}

?>

<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->
<?php renderTemplate("head") ?>
<!--end::Head-->
<!--begin::Body-->

<body
    id="kt_app_body"
    data-kt-app-header-fixed="true"
    data-kt-app-header-fixed-mobile="true"
    data-kt-app-sidebar-enabled="true"
    data-kt-app-sidebar-fixed="true"
    data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true"
    data-kt-app-toolbar-enabled="true"
    data-kt-app-aside-enabled="true"
    data-kt-app-aside-fixed="true"
    data-kt-app-aside-push-toolbar="true"
    data-kt-app-aside-push-footer="true"
    class="app-default">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode =
                    document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ?
                    "dark" :
                    "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::App-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <!--begin::Page-->
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <!--begin::Header-->
            <div
                id="kt_app_header"
                class="app-header d-flex flex-column flex-stack">
                <!--begin::Header main-->
                <div class="d-flex align-items-center flex-stack flex-grow-1">
                    <div
                        class="app-header-logo d-flex align-items-center flex-stack px-lg-11 mb-2"
                        id="kt_app_header_logo">
                        <!--begin::Sidebar mobile toggle-->
                        <div
                            class="btn btn-icon btn-active-color-primary w-35px h-35px ms-3 me-2 d-flex d-lg-none"
                            id="kt_app_sidebar_mobile_toggle">
                            <i class="ki-duotone ki-abstract-14 fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <!--end::Sidebar mobile toggle-->
                        <!--begin::Logo-->
                        <a href="<?= BASE_URL ?>" class="app-sidebar-logo">
                            <?php
                            $logo = getData("logo", "settings");
                            $logoUrl = $logo ? BASE_URL . UPLOADS_URL . $logo : BASE_URL . 'assets/media/logos/default.svg';
                            $logoDarkUrl = $logo ? BASE_URL . UPLOADS_URL . $logo : BASE_URL . 'assets/media/logos/default-dark.svg';
                            ?>
                            <img
                                alt="Logo"
                                src="<?= $logoUrl ?>"
                                class="h-30px theme-light-show" />
                            <img
                                alt="Logo"
                                src="<?= $logoDarkUrl ?>"
                                class="h-30px theme-dark-show" />
                        </a>
                        <!--end::Logo-->
                        <!--begin::Sidebar toggle-->
                        <div
                            id="kt_app_sidebar_toggle"
                            class="app-sidebar-toggle btn btn-sm btn-icon btn-color-warning me-n2 d-none d-lg-flex"
                            data-kt-toggle="true"
                            data-kt-toggle-state="active"
                            data-kt-toggle-target="body"
                            data-kt-toggle-name="app-sidebar-minimize">
                            <i class="ki-duotone ki-exit-left fs-2x rotate-180">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <!--end::Sidebar toggle-->
                    </div>
                    <!--begin::Navbar-->
                    <div
                        class="app-navbar flex-grow-1 justify-content-end"
                        id="kt_app_header_navbar">
                        <div
                            class="app-navbar-item d-flex align-items-stretch flex-lg-grow-1 me-2 me-lg-0">
                            <!--begin::Search-->
                            <div
                                id="kt_header_search"
                                class="header-search d-flex align-items-center w-lg-350px"
                                data-kt-search-keypress="true"
                                data-kt-search-min-length="2"
                                data-kt-search-enter="enter"
                                data-kt-search-layout="menu"
                                data-kt-search-responsive="true"
                                data-kt-menu-trigger="auto"
                                data-kt-menu-permanent="true"
                                data-kt-menu-placement="bottom-start">
                                <!--begin::Tablet and mobile search toggle-->
                                <div
                                    data-kt-search-element="toggle"
                                    class="search-toggle-mobile d-flex d-lg-none align-items-center">
                                    <div class="d-flex">
                                        <i class="ki-duotone ki-magnifier fs-1 fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                                <!--end::Tablet and mobile search toggle-->
                                <!--begin::Form(use d-none d-lg-block classes for responsive search)-->
                                <form
                                    data-kt-search-element="form"
                                    class="d-none d-lg-block w-100 position-relative mb-5 mb-lg-0"
                                    autocomplete="off">
                                    <!--begin::Hidden input(Added to disable form autocomplete)-->
                                    <input type="hidden" />
                                    <!--end::Hidden input-->
                                    <!--begin::Icon-->
                                    <i
                                        class="ki-duotone ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <!--end::Icon-->
                                    <!--begin::Input-->
                                    <input
                                        type="text"
                                        class="search-input form-control form-control border-0 h-lg-40px ps-13"
                                        name="search"
                                        value=""
                                        placeholder="Search..."
                                        data-kt-search-element="input" />
                                    <!--end::Input-->
                                    <!--begin::Spinner-->
                                    <span
                                        class="search-spinner position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-5"
                                        data-kt-search-element="spinner">
                                        <span
                                            class="spinner-border h-15px w-15px align-middle text-gray-400"></span>
                                    </span>
                                    <!--end::Spinner-->
                                    <!--begin::Reset-->
                                    <span
                                        class="search-reset btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-4"
                                        data-kt-search-element="clear">
                                        <i class="ki-duotone ki-cross fs-2 fs-lg-1 me-0">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                    <!--end::Reset-->
                                </form>
                                <!--end::Form-->
                                <!--begin::Menu-->
                                <div
                                    data-kt-search-element="content"
                                    class="menu menu-sub menu-sub-dropdown py-7 px-7 overflow-hidden w-300px w-md-350px">
                                    <!--begin::Wrapper-->
                                    <div data-kt-search-element="wrapper">
                                        <!--begin::Recently viewed-->
                                        <div data-kt-search-element="results" class="d-none">
                                            <!--begin::Items-->
                                            <div class="scroll-y mh-200px mh-lg-350px">
                                                <!--begin::Category title-->
                                                <h3
                                                    class="fs-5 text-muted m-0 pb-5"
                                                    data-kt-search-element="category-title">
                                                    Users
                                                </h3>
                                                <!--end::Category title-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <img
                                                            src="<?= BASE_URL ?>assets/media/avatars/300-6.jpg"
                                                            alt="" />
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Karina Clark</span>
                                                        <span class="fs-7 fw-semibold text-muted">Marketing Manager</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <img
                                                            src="<?= BASE_URL ?>assets/media/avatars/300-2.jpg"
                                                            alt="" />
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Olivia Bold</span>
                                                        <span class="fs-7 fw-semibold text-muted">Software Engineer</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <img
                                                            src="<?= BASE_URL ?>assets/media/avatars/300-9.jpg"
                                                            alt="" />
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Ana Clark</span>
                                                        <span class="fs-7 fw-semibold text-muted">UI/UX Designer</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <img
                                                            src="<?= BASE_URL ?>assets/media/avatars/300-14.jpg"
                                                            alt="" />
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Nick Pitola</span>
                                                        <span class="fs-7 fw-semibold text-muted">Art Director</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <img
                                                            src="<?= BASE_URL ?>assets/media/avatars/300-11.jpg"
                                                            alt="" />
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Edward Kulnic</span>
                                                        <span class="fs-7 fw-semibold text-muted">System Administrator</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Category title-->
                                                <h3
                                                    class="fs-5 text-muted m-0 pt-5 pb-5"
                                                    data-kt-search-element="category-title">
                                                    Customers
                                                </h3>
                                                <!--end::Category title-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <img
                                                                class="w-20px h-20px"
                                                                src="<?= BASE_URL ?>assets/media/svg/brand-logos/volicity-9.svg"
                                                                alt="" />
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Company Rbranding</span>
                                                        <span class="fs-7 fw-semibold text-muted">UI Design</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <img
                                                                class="w-20px h-20px"
                                                                src="<?= BASE_URL ?>assets/media/svg/brand-logos/tvit.svg"
                                                                alt="" />
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Company Re-branding</span>
                                                        <span class="fs-7 fw-semibold text-muted">Web Development</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <img
                                                                class="w-20px h-20px"
                                                                src="<?= BASE_URL ?>assets/media/svg/misc/infography.svg"
                                                                alt="" />
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Business Analytics App</span>
                                                        <span class="fs-7 fw-semibold text-muted">Administration</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <img
                                                                class="w-20px h-20px"
                                                                src="<?= BASE_URL ?>assets/media/svg/brand-logos/leaf.svg"
                                                                alt="" />
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">EcoLeaf App Launch</span>
                                                        <span class="fs-7 fw-semibold text-muted">Marketing</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <img
                                                                class="w-20px h-20px"
                                                                src="<?= BASE_URL ?>assets/media/svg/brand-logos/tower.svg"
                                                                alt="" />
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div
                                                        class="d-flex flex-column justify-content-start fw-semibold">
                                                        <span class="fs-6 fw-semibold">Tower Group Website</span>
                                                        <span class="fs-7 fw-semibold text-muted">Google Adwords</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Category title-->
                                                <h3
                                                    class="fs-5 text-muted m-0 pt-5 pb-5"
                                                    data-kt-search-element="category-title">
                                                    Projects
                                                </h3>
                                                <!--end::Category title-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-notepad fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                                <span class="path4"></span>
                                                                <span class="path5"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <span class="fs-6 fw-semibold">Si-Fi Project by AU Themes</span>
                                                        <span class="fs-7 fw-semibold text-muted">#45670</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-frame fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                                <span class="path4"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <span class="fs-6 fw-semibold">Shopix Mobile App Planning</span>
                                                        <span class="fs-7 fw-semibold text-muted">#45690</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-message-text-2 fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <span class="fs-6 fw-semibold">Finance Monitoring SAAS Discussion</span>
                                                        <span class="fs-7 fw-semibold text-muted">#21090</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <a
                                                    href="#"
                                                    class="d-flex text-dark text-hover-primary align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-profile-circle fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <span class="fs-6 fw-semibold">Dashboard Analitics Launch</span>
                                                        <span class="fs-7 fw-semibold text-muted">#34560</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </a>
                                                <!--end::Item-->
                                            </div>
                                            <!--end::Items-->
                                        </div>
                                        <!--end::Recently viewed-->
                                        <!--begin::Recently viewed-->
                                        <div class="" data-kt-search-element="main">
                                            <!--begin::Heading-->
                                            <div class="d-flex flex-stack fw-semibold mb-4">
                                                <!--begin::Label-->
                                                <span class="text-muted fs-6 me-2">Recently Searched:</span>
                                                <!--end::Label-->
                                                <!--begin::Toolbar-->
                                                <div class="d-flex" data-kt-search-element="toolbar">
                                                    <!--begin::Preferences toggle-->
                                                    <div
                                                        data-kt-search-element="preferences-show"
                                                        class="btn btn-icon w-20px btn-sm btn-active-color-primary me-2 data-bs-toggle="
                                                        title="Show search preferences">
                                                        <i class="ki-duotone ki-setting-2 fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </div>
                                                    <!--end::Preferences toggle-->
                                                    <!--begin::Advanced search toggle-->
                                                    <div
                                                        data-kt-search-element="advanced-options-form-show"
                                                        class="btn btn-icon w-20px btn-sm btn-active-color-primary me-n1"
                                                        data-bs-toggle="tooltip"
                                                        title="Show more search options">
                                                        <i class="ki-duotone ki-down fs-2"></i>
                                                    </div>
                                                    <!--end::Advanced search toggle-->
                                                </div>
                                                <!--end::Toolbar-->
                                            </div>
                                            <!--end::Heading-->
                                            <!--begin::Items-->
                                            <div class="scroll-y mh-200px mh-lg-325px">
                                                <!--begin::Item-->
                                                <div class="d-flex align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-laptop fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-semibold">BoomApp by Keenthemes</a>
                                                        <span class="fs-7 text-muted fw-semibold">#45789</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <div class="d-flex align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-chart-simple fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                                <span class="path4"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-semibold">"Kept API Project Meeting</a>
                                                        <span class="fs-7 text-muted fw-semibold">#84050</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <div class="d-flex align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-chart fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-semibold">"KPI Monitoring App Launch</a>
                                                        <span class="fs-7 text-muted fw-semibold">#84250</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <div class="d-flex align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-chart-line-down fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-semibold">Project Reference FAQ</a>
                                                        <span class="fs-7 text-muted fw-semibold">#67945</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <div class="d-flex align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i class="ki-duotone ki-sms fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-semibold">"FitPro App Development</a>
                                                        <span class="fs-7 text-muted fw-semibold">#84250</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <div class="d-flex align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i class="ki-duotone ki-bank fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-semibold">Shopix Mobile App</a>
                                                        <span class="fs-7 text-muted fw-semibold">#45690</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Item-->
                                                <!--begin::Item-->
                                                <div class="d-flex align-items-center mb-5">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-40px me-4">
                                                        <span class="symbol-label bg-light">
                                                            <i
                                                                class="ki-duotone ki-chart-line-down fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="d-flex flex-column">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-semibold">"Landing UI Design" Launch</a>
                                                        <span class="fs-7 text-muted fw-semibold">#24005</span>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Item-->
                                            </div>
                                            <!--end::Items-->
                                        </div>
                                        <!--end::Recently viewed-->
                                        <!--begin::Empty-->
                                        <div
                                            data-kt-search-element="empty"
                                            class="text-center d-none">
                                            <!--begin::Icon-->
                                            <div class="pt-10 pb-10">
                                                <i class="ki-duotone ki-search-list fs-4x opacity-50">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                </i>
                                            </div>
                                            <!--end::Icon-->
                                            <!--begin::Message-->
                                            <div class="pb-15 fw-semibold">
                                                <h3 class="text-gray-600 fs-5 mb-2">
                                                    No result found
                                                </h3>
                                                <div class="text-muted fs-7">
                                                    Please try again with a different query
                                                </div>
                                            </div>
                                            <!--end::Message-->
                                        </div>
                                        <!--end::Empty-->
                                    </div>
                                    <!--end::Wrapper-->
                                    <!--begin::Preferences-->
                                    <form
                                        data-kt-search-element="advanced-options-form"
                                        class="pt-1 d-none">
                                        <!--begin::Heading-->
                                        <h3 class="fw-semibold text-dark mb-7">
                                            Advanced Search
                                        </h3>
                                        <!--end::Heading-->
                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <input
                                                type="text"
                                                class="form-control form-control-sm form-control-solid"
                                                placeholder="Contains the word"
                                                name="query" />
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <!--begin::Radio group-->
                                            <div class="nav-group nav-group-fluid">
                                                <!--begin::Option-->
                                                <label>
                                                    <input
                                                        type="radio"
                                                        class="btn-check"
                                                        name="type"
                                                        value="has"
                                                        checked="checked" />
                                                    <span
                                                        class="btn btn-sm btn-color-muted btn-active btn-active-primary">All</span>
                                                </label>
                                                <!--end::Option-->
                                                <!--begin::Option-->
                                                <label>
                                                    <input
                                                        type="radio"
                                                        class="btn-check"
                                                        name="type"
                                                        value="users" />
                                                    <span
                                                        class="btn btn-sm btn-color-muted btn-active btn-active-primary px-4">Users</span>
                                                </label>
                                                <!--end::Option-->
                                                <!--begin::Option-->
                                                <label>
                                                    <input
                                                        type="radio"
                                                        class="btn-check"
                                                        name="type"
                                                        value="orders" />
                                                    <span
                                                        class="btn btn-sm btn-color-muted btn-active btn-active-primary px-4">Orders</span>
                                                </label>
                                                <!--end::Option-->
                                                <!--begin::Option-->
                                                <label>
                                                    <input
                                                        type="radio"
                                                        class="btn-check"
                                                        name="type"
                                                        value="projects" />
                                                    <span
                                                        class="btn btn-sm btn-color-muted btn-active btn-active-primary px-4">Projects</span>
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                            <!--end::Radio group-->
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <input
                                                type="text"
                                                name="assignedto"
                                                class="form-control form-control-sm form-control-solid"
                                                placeholder="Assigned to"
                                                value="" />
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <input
                                                type="text"
                                                name="collaborators"
                                                class="form-control form-control-sm form-control-solid"
                                                placeholder="Collaborators"
                                                value="" />
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <!--begin::Radio group-->
                                            <div class="nav-group nav-group-fluid">
                                                <!--begin::Option-->
                                                <label>
                                                    <input
                                                        type="radio"
                                                        class="btn-check"
                                                        name="attachment"
                                                        value="has"
                                                        checked="checked" />
                                                    <span
                                                        class="btn btn-sm btn-color-muted btn-active btn-active-primary">Has attachment</span>
                                                </label>
                                                <!--end::Option-->
                                                <!--begin::Option-->
                                                <label>
                                                    <input
                                                        type="radio"
                                                        class="btn-check"
                                                        name="attachment"
                                                        value="any" />
                                                    <span
                                                        class="btn btn-sm btn-color-muted btn-active btn-active-primary px-4">Any</span>
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                            <!--end::Radio group-->
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <select
                                                name="timezone"
                                                aria-label="Select a Timezone"
                                                data-control="select2"
                                                data-dropdown-parent="#kt_header_search"
                                                data-placeholder="date_period"
                                                class="form-select form-select-sm form-select-solid">
                                                <option value="next">Within the next</option>
                                                <option value="last">Within the last</option>
                                                <option value="between">Between</option>
                                                <option value="on">On</option>
                                            </select>
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="row mb-8">
                                            <!--begin::Col-->
                                            <div class="col-6">
                                                <input
                                                    type="number"
                                                    name="date_number"
                                                    class="form-control form-control-sm form-control-solid"
                                                    placeholder="Lenght"
                                                    value="" />
                                            </div>
                                            <!--end::Col-->
                                            <!--begin::Col-->
                                            <div class="col-6">
                                                <select
                                                    name="date_typer"
                                                    aria-label="Select a Timezone"
                                                    data-control="select2"
                                                    data-dropdown-parent="#kt_header_search"
                                                    data-placeholder="Period"
                                                    class="form-select form-select-sm form-select-solid">
                                                    <option value="days">Days</option>
                                                    <option value="weeks">Weeks</option>
                                                    <option value="months">Months</option>
                                                    <option value="years">Years</option>
                                                </select>
                                            </div>
                                            <!--end::Col-->
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Actions-->
                                        <div class="d-flex justify-content-end">
                                            <button
                                                type="reset"
                                                class="btn btn-sm btn-light fw-bold btn-active-light-primary me-2"
                                                data-kt-search-element="advanced-options-form-cancel">
                                                Cancel
                                            </button>
                                            <a
                                                href="../dist/pages/search/horizontal.html"
                                                class="btn btn-sm fw-bold btn-primary"
                                                data-kt-search-element="advanced-options-form-search">Search</a>
                                        </div>
                                        <!--end::Actions-->
                                    </form>
                                    <!--end::Preferences-->
                                    <!--begin::Preferences-->
                                    <form
                                        data-kt-search-element="preferences"
                                        class="pt-1 d-none">
                                        <!--begin::Heading-->
                                        <h3 class="fw-semibold text-dark mb-7">
                                            Search Preferences
                                        </h3>
                                        <!--end::Heading-->
                                        <!--begin::Input group-->
                                        <div class="pb-4 border-bottom">
                                            <label
                                                class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                                                <span
                                                    class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">Projects</span>
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    value="1"
                                                    checked="checked" />
                                            </label>
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="py-4 border-bottom">
                                            <label
                                                class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                                                <span
                                                    class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">Targets</span>
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    value="1"
                                                    checked="checked" />
                                            </label>
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="py-4 border-bottom">
                                            <label
                                                class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                                                <span
                                                    class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">Affiliate Programs</span>
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    value="1" />
                                            </label>
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="py-4 border-bottom">
                                            <label
                                                class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                                                <span
                                                    class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">Referrals</span>
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    value="1"
                                                    checked="checked" />
                                            </label>
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="py-4 border-bottom">
                                            <label
                                                class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                                                <span
                                                    class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">Users</span>
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    value="1" />
                                            </label>
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Actions-->
                                        <div class="d-flex justify-content-end pt-7">
                                            <button
                                                type="reset"
                                                class="btn btn-sm btn-light fw-bold btn-active-light-primary me-2"
                                                data-kt-search-element="preferences-dismiss">
                                                Cancel
                                            </button>
                                            <button
                                                type="submit"
                                                class="btn btn-sm fw-bold btn-primary">
                                                Save Changes
                                            </button>
                                        </div>
                                        <!--end::Actions-->
                                    </form>
                                    <!--end::Preferences-->
                                </div>
                                <!--end::Menu-->
                            </div>
                            <!--end::Search-->
                        </div>
                        <!--begin::Notifications-->
                        <div class="app-navbar-item me-lg-1">
                            <!--begin::Menu- wrapper-->
                            <div
                                class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-35px h-35px w-md-40px h-md-40px"
                                data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                                data-kt-menu-attach="parent"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-graph-3 fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--begin::Menu-->
                            <div
                                class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px"
                                data-kt-menu="true"
                                id="kt_menu_notifications">
                                <!--begin::Heading-->
                                <div
                                    class="d-flex flex-column bgi-no-repeat rounded-top"
                                    style="
                      background-image: url('assets/media/misc/menu-header-bg.jpg');
                    ">
                                    <!--begin::Title-->
                                    <h3 class="text-white fw-semibold px-9 mt-10 mb-6">
                                        Notifications
                                        <span class="fs-8 opacity-75 ps-3">24 reports</span>
                                    </h3>
                                    <!--end::Title-->
                                    <!--begin::Tabs-->
                                    <ul
                                        class="nav nav-line-tabs nav-line-tabs-2x nav-stretch fw-semibold px-9">
                                        <li class="nav-item">
                                            <a
                                                class="nav-link text-white opacity-75 opacity-state-100 pb-4"
                                                data-bs-toggle="tab"
                                                href="#kt_topbar_notifications_1">Alerts</a>
                                        </li>
                                        <li class="nav-item">
                                            <a
                                                class="nav-link text-white opacity-75 opacity-state-100 pb-4 active"
                                                data-bs-toggle="tab"
                                                href="#kt_topbar_notifications_2">Updates</a>
                                        </li>
                                        <li class="nav-item">
                                            <a
                                                class="nav-link text-white opacity-75 opacity-state-100 pb-4"
                                                data-bs-toggle="tab"
                                                href="#kt_topbar_notifications_3">Logs</a>
                                        </li>
                                    </ul>
                                    <!--end::Tabs-->
                                </div>
                                <!--end::Heading-->
                                <!--begin::Tab content-->
                                <div class="tab-content">
                                    <!--begin::Tab panel-->
                                    <div
                                        class="tab-pane fade"
                                        id="kt_topbar_notifications_1"
                                        role="tabpanel">
                                        <!--begin::Items-->
                                        <div class="scroll-y mh-325px my-5 px-8">
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-35px me-4">
                                                        <span class="symbol-label bg-light-primary">
                                                            <i
                                                                class="ki-duotone ki-abstract-28 fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="mb-0 me-2">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-bold">Project Alice</a>
                                                        <div class="text-gray-400 fs-7">
                                                            Phase 1 development
                                                        </div>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">1 hr</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-35px me-4">
                                                        <span class="symbol-label bg-light-danger">
                                                            <i
                                                                class="ki-duotone ki-information fs-2 text-danger">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="mb-0 me-2">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-bold">HR Confidential</a>
                                                        <div class="text-gray-400 fs-7">
                                                            Confidential staff documents
                                                        </div>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">2 hrs</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-35px me-4">
                                                        <span class="symbol-label bg-light-warning">
                                                            <i
                                                                class="ki-duotone ki-briefcase fs-2 text-warning">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="mb-0 me-2">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-bold">Company HR</a>
                                                        <div class="text-gray-400 fs-7">
                                                            Corporeate staff profiles
                                                        </div>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">5 hrs</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-35px me-4">
                                                        <span class="symbol-label bg-light-success">
                                                            <i
                                                                class="ki-duotone ki-abstract-12 fs-2 text-success">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="mb-0 me-2">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-bold">Project Redux</a>
                                                        <div class="text-gray-400 fs-7">
                                                            New frontend admin theme
                                                        </div>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">2 days</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-35px me-4">
                                                        <span class="symbol-label bg-light-primary">
                                                            <i
                                                                class="ki-duotone ki-colors-square fs-2 text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                                <span class="path4"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="mb-0 me-2">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-bold">Project Breafing</a>
                                                        <div class="text-gray-400 fs-7">
                                                            Product launch status update
                                                        </div>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">21 Jan</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-35px me-4">
                                                        <span class="symbol-label bg-light-info">
                                                            <i
                                                                class="ki-duotone ki-picture fs-2 text-info"></i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="mb-0 me-2">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-bold">Banner Assets</a>
                                                        <div class="text-gray-400 fs-7">
                                                            Collection of banner images
                                                        </div>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">21 Jan</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center">
                                                    <!--begin::Symbol-->
                                                    <div class="symbol symbol-35px me-4">
                                                        <span class="symbol-label bg-light-warning">
                                                            <i
                                                                class="ki-duotone ki-color-swatch fs-2 text-warning">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                                <span class="path4"></span>
                                                                <span class="path5"></span>
                                                                <span class="path6"></span>
                                                                <span class="path7"></span>
                                                                <span class="path8"></span>
                                                                <span class="path9"></span>
                                                                <span class="path10"></span>
                                                                <span class="path11"></span>
                                                                <span class="path12"></span>
                                                                <span class="path13"></span>
                                                                <span class="path14"></span>
                                                                <span class="path15"></span>
                                                                <span class="path16"></span>
                                                                <span class="path17"></span>
                                                                <span class="path18"></span>
                                                                <span class="path19"></span>
                                                                <span class="path20"></span>
                                                                <span class="path21"></span>
                                                            </i>
                                                        </span>
                                                    </div>
                                                    <!--end::Symbol-->
                                                    <!--begin::Title-->
                                                    <div class="mb-0 me-2">
                                                        <a
                                                            href="#"
                                                            class="fs-6 text-gray-800 text-hover-primary fw-bold">Icon Assets</a>
                                                        <div class="text-gray-400 fs-7">
                                                            Collection of SVG icons
                                                        </div>
                                                    </div>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">20 March</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                        </div>
                                        <!--end::Items-->
                                        <!--begin::View more-->
                                        <div class="py-3 text-center border-top">
                                            <a
                                                href="#"
                                                class="btn btn-color-gray-600 btn-active-color-primary">View All
                                                <i class="ki-duotone ki-arrow-right fs-5">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span> </i></a>
                                        </div>
                                        <!--end::View more-->
                                    </div>
                                    <!--end::Tab panel-->
                                    <!--begin::Tab panel-->
                                    <div
                                        class="tab-pane fade show active"
                                        id="kt_topbar_notifications_2"
                                        role="tabpanel">
                                        <!--begin::Wrapper-->
                                        <div class="d-flex flex-column px-9">
                                            <!--begin::Section-->
                                            <div class="pt-10 pb-0">
                                                <!--begin::Title-->
                                                <h3 class="text-dark text-center fw-bold">
                                                    Get Pro Access
                                                </h3>
                                                <!--end::Title-->
                                                <!--begin::Text-->
                                                <div
                                                    class="text-center text-gray-600 fw-semibold pt-1">
                                                    Outlines keep you honest. They stoping you from
                                                    amazing poorly about drive
                                                </div>
                                                <!--end::Text-->
                                                <!--begin::Action-->
                                                <div class="text-center mt-5 mb-9">
                                                    <a
                                                        href="#"
                                                        class="btn btn-sm btn-primary px-6"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#kt_modal_upgrade_plan">Upgrade</a>
                                                </div>
                                                <!--end::Action-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Illustration-->
                                            <div class="text-center px-4">
                                                <img
                                                    class="mw-100 mh-200px"
                                                    alt="image"
                                                    src="<?= BASE_URL ?>assets/media/illustrations/sketchy-1/1.png" />
                                            </div>
                                            <!--end::Illustration-->
                                        </div>
                                        <!--end::Wrapper-->
                                    </div>
                                    <!--end::Tab panel-->
                                    <!--begin::Tab panel-->
                                    <div
                                        class="tab-pane fade"
                                        id="kt_topbar_notifications_3"
                                        role="tabpanel">
                                        <!--begin::Items-->
                                        <div class="scroll-y mh-325px my-5 px-8">
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-success me-4">200 OK</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">New order</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">Just now</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-danger me-4">500 ERR</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">New customer</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">2 hrs</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-success me-4">200 OK</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">Payment process</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">5 hrs</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-warning me-4">300 WRN</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">Search query</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">2 days</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-success me-4">200 OK</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">API connection</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">1 week</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-success me-4">200 OK</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">Database restore</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">Mar 5</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-warning me-4">300 WRN</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">System update</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">May 15</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-warning me-4">300 WRN</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">Server OS update</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">Apr 3</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-warning me-4">300 WRN</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">API rollback</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">Jun 30</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-danger me-4">500 ERR</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">Refund process</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">Jul 10</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-danger me-4">500 ERR</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">Withdrawal process</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">Sep 10</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex flex-stack py-4">
                                                <!--begin::Section-->
                                                <div class="d-flex align-items-center me-2">
                                                    <!--begin::Code-->
                                                    <span class="w-70px badge badge-light-danger me-4">500 ERR</span>
                                                    <!--end::Code-->
                                                    <!--begin::Title-->
                                                    <a
                                                        href="#"
                                                        class="text-gray-800 text-hover-primary fw-semibold">Mail tasks</a>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Section-->
                                                <!--begin::Label-->
                                                <span class="badge badge-light fs-8">Dec 10</span>
                                                <!--end::Label-->
                                            </div>
                                            <!--end::Item-->
                                        </div>
                                        <!--end::Items-->
                                        <!--begin::View more-->
                                        <div class="py-3 text-center border-top">
                                            <a
                                                href="#"
                                                class="btn btn-color-gray-600 btn-active-color-primary">View All
                                                <i class="ki-duotone ki-arrow-right fs-5">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span> </i></a>
                                        </div>
                                        <!--end::View more-->
                                    </div>
                                    <!--end::Tab panel-->
                                </div>
                                <!--end::Tab content-->
                            </div>
                            <!--end::Menu-->
                            <!--end::Menu wrapper-->
                        </div>
                        <!--end::Notifications-->
                        <!--begin::Quick links-->
                        <div class="app-navbar-item">
                            <!--begin::Menu- wrapper-->
                            <div
                                class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-35px h-35px w-md-40px h-md-40px"
                                data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                                data-kt-menu-attach="parent"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-notification-status fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </div>
                            <!--begin::Menu-->
                            <div
                                class="menu menu-sub menu-sub-dropdown menu-column w-250px w-lg-325px"
                                data-kt-menu="true">
                                <!--begin::Heading-->
                                <div
                                    class="d-flex flex-column flex-center bgi-no-repeat rounded-top px-9 py-10"
                                    style="
                      background-image: url('assets/media/misc/menu-header-bg.jpg');">
                                    <!--begin::Title-->
                                    <h3 class="text-white fw-semibold mb-3">Quick Links</h3>
                                    <!--end::Title-->
                                    <!--begin::Status-->
                                    <span
                                        class="badge bg-primary text-inverse-primary py-2 px-3">25 pending tasks</span>
                                    <!--end::Status-->
                                </div>
                                <!--end::Heading-->
                                <!--begin:Nav-->
                                <div class="row g-0">
                                    <!--begin:Item-->
                                    <div class="col-6">
                                        <a
                                            href="#"
                                            class="d-flex flex-column flex-center h-100 p-6 bg-hover-light border-end border-bottom">
                                            <i class="ki-duotone ki-dollar fs-3x text-primary mb-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                            <span class="fs-5 fw-semibold text-gray-800 mb-0">Accounting</span>
                                            <span class="fs-7 text-gray-400">eCommerce</span>
                                        </a>
                                    </div>
                                    <!--end:Item-->
                                    <!--begin:Item-->
                                    <div class="col-6">
                                        <a
                                            href="#"
                                            class="d-flex flex-column flex-center h-100 p-6 bg-hover-light border-bottom">
                                            <i class="ki-duotone ki-sms fs-3x text-primary mb-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <span class="fs-5 fw-semibold text-gray-800 mb-0">Administration</span>
                                            <span class="fs-7 text-gray-400">Console</span>
                                        </a>
                                    </div>
                                    <!--end:Item-->
                                    <!--begin:Item-->
                                    <div class="col-6">
                                        <a
                                            href="../dist/apps/projects/list.html"
                                            class="d-flex flex-column flex-center h-100 p-6 bg-hover-light border-end">
                                            <i
                                                class="ki-duotone ki-abstract-41 fs-3x text-primary mb-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <span class="fs-5 fw-semibold text-gray-800 mb-0">Projects</span>
                                            <span class="fs-7 text-gray-400">Pending Tasks</span>
                                        </a>
                                    </div>
                                    <!--end:Item-->
                                    <!--begin:Item-->
                                    <div class="col-6">
                                        <a
                                            href="#"
                                            class="d-flex flex-column flex-center h-100 p-6 bg-hover-light">
                                            <i
                                                class="ki-duotone ki-briefcase fs-3x text-primary mb-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <span class="fs-5 fw-semibold text-gray-800 mb-0">Customers</span>
                                            <span class="fs-7 text-gray-400">Latest cases</span>
                                        </a>
                                    </div>
                                    <!--end:Item-->
                                </div>
                                <!--end:Nav-->
                                <!--begin::View more-->
                                <div class="py-2 text-center border-top">
                                    <a
                                        href="#"
                                        class="btn btn-color-gray-600 btn-active-color-primary">View All
                                        <i class="ki-duotone ki-arrow-right fs-5">
                                            <span class="path1"></span>
                                            <span class="path2"></span> </i></a>
                                </div>
                                <!--end::View more-->
                            </div>
                            <!--end::Menu-->
                            <!--end::Menu wrapper-->
                        </div>
                        <!--end::Quick links-->
                        <!--begin::User menu-->
                        <div class="app-navbar-item ms-3 ms-lg-4 me-lg-2 pe-6" id="kt_header_user_menu_toggle">
                            <!--begin::Menu wrapper-->
                            <div class="cursor-pointer symbol symbol-30px symbol-lg-40px"
                                data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                                data-kt-menu-attach="parent"
                                data-kt-menu-placement="bottom-end">
                                <img src="<?= UPLOADS_URL . $user->image ?>" alt="<?= $user->name ?>" />
                            </div>

                            <!--begin::User account menu-->
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
                                data-kt-menu="true">

                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <div class="menu-content d-flex flex-column align-items-center px-3 py-4">
                                        <!--begin::Avatar-->
                                        <div class="symbol symbol-70px mb-3">
                                            <img alt="<?= $user->name ?>" src="<?= UPLOADS_URL . $user->image ?>" class="rounded-circle" />
                                        </div>
                                        <!--end::Avatar-->

                                        <!--begin::Username-->
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="fw-bold d-flex align-items-center fs-5 mb-1">
                                                <?= $user->name ?>
                                            </div>
                                            <span class="badge badge-light-success fw-bold fs-7 px-3 py-2">
                                                <?= ucfirst($user->role) ?>
                                            </span>
                                        </div>
                                        <!--end::Username-->
                                    </div>
                                </div>
                                <!--end::Menu item-->

                                <!--begin::Menu separator-->
                                <div class="separator my-2"></div>
                                <!--end::Menu separator-->

                                <!--begin::Menu item-->
                                <div class="menu-item px-5">
                                    <a href="../dist/account/overview.html" class="menu-link px-5">
                                        <i class="bi bi-person me-2 fs-5"></i>
                                        My Profile
                                    </a>
                                </div>
                                <!--end::Menu item-->

                                <!--begin::Menu item-->
                                <div class="menu-item px-5">
                                    <a href="../dist/apps/projects/list.html" class="menu-link px-5">
                                        <i class="bi bi-folder me-2 fs-5"></i>
                                        <span class="menu-text">My Projects</span>
                                        <span class="menu-badge">
                                            <span class="badge badge-light-danger badge-circle fw-bold fs-7">3</span>
                                        </span>
                                    </a>
                                </div>
                                <!--end::Menu item-->

                                <!--begin::Menu separator-->
                                <div class="separator my-2"></div>
                                <!--end::Menu separator-->

                                <!--begin::Menu item-->
                                <div class="menu-item px-5 my-1">
                                    <a href="../dist/account/settings.html" class="menu-link px-5">
                                        <i class="bi bi-gear me-2 fs-5"></i>
                                        Account Settings
                                    </a>
                                </div>
                                <!--end::Menu item-->

                                <!--begin::Menu item-->
                                <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                                    data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
                                    <a href="#" class="menu-link px-5">
                                        <i class="bi bi-palette me-2 fs-5"></i>
                                        <span class="menu-title position-relative">Theme</span>
                                        <span class="ms-5 position-absolute translate-middle-y top-50 end-0">
                                            <i class="fs-2">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                                <span class="path4"></span><span class="path5"></span><span class="path6"></span>
                                                <span class="path7"></span><span class="path8"></span><span class="path9"></span>
                                                <span class="path10"></span>
                                            </i>
                                            <i class="ki-duotone ki-moon theme-dark-show fs-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                        </span>
                                    </a>

                                    <!--begin::Menu-->
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                                        data-kt-menu="true" data-kt-element="theme-mode-menu">

                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3 my-0">
                                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                                <span class="menu-icon" data-kt-element="icon">
                                                    <i class="ki-duotone ki-night-day fs-2">
                                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                                        <span class="path4"></span><span class="path5"></span><span class="path6"></span>
                                                        <span class="path7"></span><span class="path8"></span><span class="path9"></span>
                                                        <span class="path10"></span>
                                                    </i>
                                                </span>
                                                <span class="menu-title">Light</span>
                                            </a>
                                        </div>
                                        <!--end::Menu item-->

                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3 my-0">
                                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                                <span class="menu-icon" data-kt-element="icon">
                                                    <i class="ki-duotone ki-moon fs-2">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                </span>
                                                <span class="menu-title">Dark</span>
                                            </a>
                                        </div>
                                        <!--end::Menu item-->

                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3 my-0">
                                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                                <span class="menu-icon" data-kt-element="icon">
                                                    <i class="ki-duotone ki-screen fs-2">
                                                        <span class="path1"></span><span class="path2"></span>
                                                        <span class="path3"></span><span class="path4"></span>
                                                    </i>
                                                </span>
                                                <span class="menu-title">System</span>
                                            </a>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu-->
                                </div>
                                <!--end::Menu item-->

                                <!--begin::Menu separator-->
                                <div class="separator my-2"></div>
                                <!--end::Menu separator-->

                                <!--begin::Menu item-->
                                <div class="menu-item px-5">
                                    <a href="<?= BASE_URL ?>auth/sign-out" class="menu-link px-5 text-danger">
                                        <i class="bi bi-power me-2 fs-5 text-danger"></i>
                                        Sign Out
                                    </a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::User account menu-->
                        </div>
                        <!--end::User menu-->
                        <!--end::Action-->
                        <!--begin::Header menu toggle-->
                        <div
                            class="app-navbar-item ms-3 ms-lg-4 ms-n2 me-3 d-flex d-lg-none">
                            <div
                                class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-35px h-35px w-md-40px h-md-40px"
                                id="kt_app_aside_mobile_toggle">
                                <i class="ki-duotone ki-burger-menu-2 fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                    <span class="path6"></span>
                                    <span class="path7"></span>
                                    <span class="path8"></span>
                                    <span class="path9"></span>
                                    <span class="path10"></span>
                                </i>
                            </div>
                        </div>
                        <!--end::Header menu toggle-->
                    </div>
                    <!--end::Navbar-->
                </div>
                <!--end::Header main-->
                <!--begin::Separator-->
                <div class="app-header-separator"></div>
                <!--end::Separator-->
            </div>
            <!--end::Header-->
            <!--begin::Wrapper-->
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <!--begin::Sidebar-->
                <div
                    id="kt_app_sidebar"
                    class="app-sidebar flex-column"
                    data-kt-drawer="true"
                    data-kt-drawer-name="app-sidebar"
                    data-kt-drawer-activate="{default: true, lg: false}"
                    data-kt-drawer-overlay="true"
                    data-kt-drawer-width="250px"
                    data-kt-drawer-direction="start"
                    data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
                    <!--begin::Main-->
                    <div
                        class="d-flex flex-column justify-content-between h-100 hover-scroll-overlay-y my-2 d-flex flex-column"
                        id="kt_app_sidebar_main"
                        data-kt-scroll="true"
                        data-kt-scroll-activate="true"
                        data-kt-scroll-height="auto"
                        data-kt-scroll-dependencies="#kt_app_header"
                        data-kt-scroll-wrappers="#kt_app_main"
                        data-kt-scroll-offset="5px">
                        <!--begin::Sidebar menu-->
                        <div
                            id="#kt_app_sidebar_menu"
                            data-kt-menu="true"
                            data-kt-menu-expand="false"
                            class="flex-column-fluid menu menu-sub-indention menu-column menu-rounded menu-active-bg mb-7">
                            <!--begin:Menu item-->
                            <a href="<?= BASE_URL ?>" class="menu-item">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-element-11 fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Dashboard</span>
                                </span>
                                <!--end:Menu link-->
                            </a>
                            <!--end:Menu item-->

                            <?php if (isAdmin()): ?>
                                <!--begin:Menu item-->
                                <a href="staffs" class="menu-item">
                                    <!--begin:Menu link-->
                                    <span class="menu-link">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-security-user fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Staffs</span>
                                    </span>
                                    <!--end:Menu link-->
                                </a>
                                <!--end:Menu item-->
                            <?php endif; ?>

                             <?php if (isAdmin()): ?>
                            <!--begin:Menu item-->
                            <a href="manual-billing" class="menu-item">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-wallet fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            
                                        </i>
                                    </span>
                                    <span class="menu-title">Manual Billing</span>
                                </span>
                                <!--end:Menu link-->
                            </a>
                            <!--end:Menu item-->
                             <?php endif; ?>

                            <?php if (isAdmin()): ?>
                                <!--begin:Menu item-->
                                <a href="dashboard-message/add" class="menu-item">
                                    <!--begin:Menu link-->
                                    <span class="menu-link">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-notification fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">DashBoard Message</span>
                                    </span>
                                    <!--end:Menu link-->
                                </a>
                                <!--end:Menu item-->
                            <?php endif; ?>


                            <!--begin:Menu item-->
                            <a href="users" class="menu-item">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-profile-user fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Users</span>
                                </span>
                                <!--end:Menu link-->
                            </a>
                            <!--end:Menu item-->

                            <!--begin:Menu item-->
                            <a href="subscription-plans" class="menu-item">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-price-tag fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Subscription Plans</span>
                                </span>
                                <!--end:Menu link-->
                            </a>
                            <!--end:Menu item-->

                            <!--begin:Menu item-->
                            <a href="subscription-histories" class="menu-item">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-search-list fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Subscription Histories</span>
                                </span>
                                <!--end:Menu link-->
                            </a>
                            <!--end:Menu item-->

                            <!--begin:Menu item-->
                            <a href="discounts" class="menu-item">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-discount fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Discounts</span>
                                </span>
                                <!--end:Menu link-->
                            </a>
                            <!--end:Menu item-->

                            <!--begin:Menu item-->
                            <a href="settings" class="menu-item">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-setting-2 fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Settings</span>
                                </span>
                                <!--end:Menu link-->
                            </a>
                            <!--end:Menu item-->
                        </div>
                        <!--end::Sidebar menu-->
                    </div>
                    <!--end::Main-->
                </div>
                <!--end::Sidebar-->
                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">