<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/database.php';

$settings = fetchSettings();
?>

<head>
    <base href="<?= BASE_URL ?>" />
    <title><?= $settings->app_name ?> Portal</title>
    <meta charset="utf-8" />
    <meta name="robots" content="noindex, nofollow">
   <link rel="icon" type="image/x-icon" href="<?= BASE_URL . UPLOADS_URL . getData("favicon", "settings") ?>" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link
        href="assets/plugins/global/plugins.bundle.css"
        rel="stylesheet"
        type="text/css" />
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->

    <!-- Bootstrap Icon Link -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>

    <script>
        const BASE_URL = '<?= BASE_URL ?>'
    </script>
</head>