<!--begin:Header-->
<?php
require_once '../../src/database.php';
require_once '../../src/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) exit(header("Location: " . BASE_URL . "auth/sign-in"));
if (!isAdmin()) exit(header("Location: " . BASE_URL));

// Get database connection
$pdo = getDbConnection();

// Get current settings
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_OBJ);
if (!$settings) {
    // Insert default settings if not exists
    $pdo->query("INSERT INTO settings (app_name, currency) VALUES ('Book Pannu', 'INR')");
    $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_OBJ);
}

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
                        <li class="breadcrumb-item text-gray-700">
                            <a href="settings" class="text-gray-700 text-hover-primary">Settings</a>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <i class="ki-duotone ki-right fs-4 text-gray-700 mx-n1"></i>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-gray-700">Currency</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">Currency Settings</h1>
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
            <!--begin::Form-->
            <form id="currencyForm" method="POST" class="form">
                <!--begin::Card-->
                <div class="card">
                    <!--begin::Card header-->
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Currency Settings</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Manage application currency settings</span>
                        </h3>
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0">
                        <!--begin::Input group-->
                        <!--begin::Input group-->
                        <div class="row mb-6">
                            <!--begin::Label-->
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">App Name</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="app_name" class="form-control form-control-lg form-control-solid" value="<?= htmlspecialchars($settings->app_name) ?>" readonly style="background-color: #f8f9fa; border-color: #e4e6ef; color: #7e8299;" />
                                <div class="form-text text-gray-600 mt-2">Application name is displayed for reference and cannot be edited here</div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->
                        <!--end::Input group-->

                        <!--begin::Input group-->
                        <div class="row mb-6">
                            <!--begin::Label-->
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Currency</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8 fv-row">
                                <select name="currency" class="form-select form-select-lg form-select-solid" data-control="select2" data-placeholder="Select currency" required>
                                    <option value="">Select Currency</option>
                                    <option value="AFN" <?= $settings->currency === 'AFN' ? 'selected' : '' ?>>Afghan Afghani</option>
                                    <option value="ALL" <?= $settings->currency === 'ALL' ? 'selected' : '' ?>>Albanian Lek</option>
                                    <option value="DZD" <?= $settings->currency === 'DZD' ? 'selected' : '' ?>>Algerian Dinar</option>
                                    <option value="AOA" <?= $settings->currency === 'AOA' ? 'selected' : '' ?>>Angolan Kwanza</option>
                                    <option value="ARS" <?= $settings->currency === 'ARS' ? 'selected' : '' ?>>Argentine Peso</option>
                                    <option value="AMD" <?= $settings->currency === 'AMD' ? 'selected' : '' ?>>Armenian Dram</option>
                                    <option value="AWG" <?= $settings->currency === 'AWG' ? 'selected' : '' ?>>Aruban Florin</option>
                                    <option value="AUD" <?= $settings->currency === 'AUD' ? 'selected' : '' ?>>Australian Dollar</option>
                                    <option value="AZN" <?= $settings->currency === 'AZN' ? 'selected' : '' ?>>Azerbaijani Manat</option>
                                    <option value="BSD" <?= $settings->currency === 'BSD' ? 'selected' : '' ?>>Bahamian Dollar</option>
                                    <option value="BHD" <?= $settings->currency === 'BHD' ? 'selected' : '' ?>>Bahraini Dinar</option>
                                    <option value="BDT" <?= $settings->currency === 'BDT' ? 'selected' : '' ?>>Bangladeshi Taka</option>
                                    <option value="BBD" <?= $settings->currency === 'BBD' ? 'selected' : '' ?>>Barbadian Dollar</option>
                                    <option value="BYR" <?= $settings->currency === 'BYR' ? 'selected' : '' ?>>Belarusian Ruble</option>
                                    <option value="BEF" <?= $settings->currency === 'BEF' ? 'selected' : '' ?>>Belgian Franc</option>
                                    <option value="BZD" <?= $settings->currency === 'BZD' ? 'selected' : '' ?>>Belize Dollar</option>
                                    <option value="BMD" <?= $settings->currency === 'BMD' ? 'selected' : '' ?>>Bermudan Dollar</option>
                                    <option value="BTN" <?= $settings->currency === 'BTN' ? 'selected' : '' ?>>Bhutanese Ngultrum</option>
                                    <option value="BTC" <?= $settings->currency === 'BTC' ? 'selected' : '' ?>>Bitcoin</option>
                                    <option value="BOB" <?= $settings->currency === 'BOB' ? 'selected' : '' ?>>Bolivian Boliviano</option>
                                    <option value="BAM" <?= $settings->currency === 'BAM' ? 'selected' : '' ?>>Bosnia-Herzegovina Convertible Mark</option>
                                    <option value="BWP" <?= $settings->currency === 'BWP' ? 'selected' : '' ?>>Botswanan Pula</option>
                                    <option value="BRL" <?= $settings->currency === 'BRL' ? 'selected' : '' ?>>Brazilian Real</option>
                                    <option value="GBP" <?= $settings->currency === 'GBP' ? 'selected' : '' ?>>British Pound Sterling</option>
                                    <option value="BND" <?= $settings->currency === 'BND' ? 'selected' : '' ?>>Brunei Dollar</option>
                                    <option value="BGN" <?= $settings->currency === 'BGN' ? 'selected' : '' ?>>Bulgarian Lev</option>
                                    <option value="BIF" <?= $settings->currency === 'BIF' ? 'selected' : '' ?>>Burundian Franc</option>
                                    <option value="KHR" <?= $settings->currency === 'KHR' ? 'selected' : '' ?>>Cambodian Riel</option>
                                    <option value="CAD" <?= $settings->currency === 'CAD' ? 'selected' : '' ?>>Canadian Dollar</option>
                                    <option value="CVE" <?= $settings->currency === 'CVE' ? 'selected' : '' ?>>Cape Verdean Escudo</option>
                                    <option value="KYD" <?= $settings->currency === 'KYD' ? 'selected' : '' ?>>Cayman Islands Dollar</option>
                                    <option value="XOF" <?= $settings->currency === 'XOF' ? 'selected' : '' ?>>CFA Franc BCEAO</option>
                                    <option value="XAF" <?= $settings->currency === 'XAF' ? 'selected' : '' ?>>CFA Franc BEAC</option>
                                    <option value="XPF" <?= $settings->currency === 'XPF' ? 'selected' : '' ?>>CFP Franc</option>
                                    <option value="CLP" <?= $settings->currency === 'CLP' ? 'selected' : '' ?>>Chilean Peso</option>
                                    <option value="CNY" <?= $settings->currency === 'CNY' ? 'selected' : '' ?>>Chinese Yuan</option>
                                    <option value="COP" <?= $settings->currency === 'COP' ? 'selected' : '' ?>>Colombian Peso</option>
                                    <option value="KMF" <?= $settings->currency === 'KMF' ? 'selected' : '' ?>>Comorian Franc</option>
                                    <option value="CDF" <?= $settings->currency === 'CDF' ? 'selected' : '' ?>>Congolese Franc</option>
                                    <option value="CRC" <?= $settings->currency === 'CRC' ? 'selected' : '' ?>>Costa Rican Colón</option>
                                    <option value="HRK" <?= $settings->currency === 'HRK' ? 'selected' : '' ?>>Croatian Kuna</option>
                                    <option value="CUC" <?= $settings->currency === 'CUC' ? 'selected' : '' ?>>Cuban Convertible Peso</option>
                                    <option value="CZK" <?= $settings->currency === 'CZK' ? 'selected' : '' ?>>Czech Republic Koruna</option>
                                    <option value="DKK" <?= $settings->currency === 'DKK' ? 'selected' : '' ?>>Danish Krone</option>
                                    <option value="DJF" <?= $settings->currency === 'DJF' ? 'selected' : '' ?>>Djiboutian Franc</option>
                                    <option value="DOP" <?= $settings->currency === 'DOP' ? 'selected' : '' ?>>Dominican Peso</option>
                                    <option value="XCD" <?= $settings->currency === 'XCD' ? 'selected' : '' ?>>East Caribbean Dollar</option>
                                    <option value="EGP" <?= $settings->currency === 'EGP' ? 'selected' : '' ?>>Egyptian Pound</option>
                                    <option value="ERN" <?= $settings->currency === 'ERN' ? 'selected' : '' ?>>Eritrean Nakfa</option>
                                    <option value="EEK" <?= $settings->currency === 'EEK' ? 'selected' : '' ?>>Estonian Kroon</option>
                                    <option value="ETB" <?= $settings->currency === 'ETB' ? 'selected' : '' ?>>Ethiopian Birr</option>
                                    <option value="EUR" <?= $settings->currency === 'EUR' ? 'selected' : '' ?>>Euro</option>
                                    <option value="FKP" <?= $settings->currency === 'FKP' ? 'selected' : '' ?>>Falkland Islands Pound</option>
                                    <option value="FJD" <?= $settings->currency === 'FJD' ? 'selected' : '' ?>>Fijian Dollar</option>
                                    <option value="GMD" <?= $settings->currency === 'GMD' ? 'selected' : '' ?>>Gambian Dalasi</option>
                                    <option value="GEL" <?= $settings->currency === 'GEL' ? 'selected' : '' ?>>Georgian Lari</option>
                                    <option value="DEM" <?= $settings->currency === 'DEM' ? 'selected' : '' ?>>German Mark</option>
                                    <option value="GHS" <?= $settings->currency === 'GHS' ? 'selected' : '' ?>>Ghanaian Cedi</option>
                                    <option value="GIP" <?= $settings->currency === 'GIP' ? 'selected' : '' ?>>Gibraltar Pound</option>
                                    <option value="GRD" <?= $settings->currency === 'GRD' ? 'selected' : '' ?>>Greek Drachma</option>
                                    <option value="GTQ" <?= $settings->currency === 'GTQ' ? 'selected' : '' ?>>Guatemalan Quetzal</option>
                                    <option value="GNF" <?= $settings->currency === 'GNF' ? 'selected' : '' ?>>Guinean Franc</option>
                                    <option value="GYD" <?= $settings->currency === 'GYD' ? 'selected' : '' ?>>Guyanaese Dollar</option>
                                    <option value="HTG" <?= $settings->currency === 'HTG' ? 'selected' : '' ?>>Haitian Gourde</option>
                                    <option value="HNL" <?= $settings->currency === 'HNL' ? 'selected' : '' ?>>Honduran Lempira</option>
                                    <option value="HKD" <?= $settings->currency === 'HKD' ? 'selected' : '' ?>>Hong Kong Dollar</option>
                                    <option value="HUF" <?= $settings->currency === 'HUF' ? 'selected' : '' ?>>Hungarian Forint</option>
                                    <option value="ISK" <?= $settings->currency === 'ISK' ? 'selected' : '' ?>>Icelandic Króna</option>
                                    <option value="INR" <?= $settings->currency === 'INR' ? 'selected' : '' ?>>Indian Rupee</option>
                                    <option value="IDR" <?= $settings->currency === 'IDR' ? 'selected' : '' ?>>Indonesian Rupiah</option>
                                    <option value="IRR" <?= $settings->currency === 'IRR' ? 'selected' : '' ?>>Iranian Rial</option>
                                    <option value="IQD" <?= $settings->currency === 'IQD' ? 'selected' : '' ?>>Iraqi Dinar</option>
                                    <option value="ILS" <?= $settings->currency === 'ILS' ? 'selected' : '' ?>>Israeli New Sheqel</option>
                                    <option value="ITL" <?= $settings->currency === 'ITL' ? 'selected' : '' ?>>Italian Lira</option>
                                    <option value="JMD" <?= $settings->currency === 'JMD' ? 'selected' : '' ?>>Jamaican Dollar</option>
                                    <option value="JPY" <?= $settings->currency === 'JPY' ? 'selected' : '' ?>>Japanese Yen</option>
                                    <option value="JOD" <?= $settings->currency === 'JOD' ? 'selected' : '' ?>>Jordanian Dinar</option>
                                    <option value="KZT" <?= $settings->currency === 'KZT' ? 'selected' : '' ?>>Kazakhstani Tenge</option>
                                    <option value="KES" <?= $settings->currency === 'KES' ? 'selected' : '' ?>>Kenyan Shilling</option>
                                    <option value="KWD" <?= $settings->currency === 'KWD' ? 'selected' : '' ?>>Kuwaiti Dinar</option>
                                    <option value="KGS" <?= $settings->currency === 'KGS' ? 'selected' : '' ?>>Kyrgystani Som</option>
                                    <option value="LAK" <?= $settings->currency === 'LAK' ? 'selected' : '' ?>>Laotian Kip</option>
                                    <option value="LVL" <?= $settings->currency === 'LVL' ? 'selected' : '' ?>>Latvian Lats</option>
                                    <option value="LBP" <?= $settings->currency === 'LBP' ? 'selected' : '' ?>>Lebanese Pound</option>
                                    <option value="LSL" <?= $settings->currency === 'LSL' ? 'selected' : '' ?>>Lesotho Loti</option>
                                    <option value="LRD" <?= $settings->currency === 'LRD' ? 'selected' : '' ?>>Liberian Dollar</option>
                                    <option value="LYD" <?= $settings->currency === 'LYD' ? 'selected' : '' ?>>Libyan Dinar</option>
                                    <option value="LTL" <?= $settings->currency === 'LTL' ? 'selected' : '' ?>>Lithuanian Litas</option>
                                    <option value="MOP" <?= $settings->currency === 'MOP' ? 'selected' : '' ?>>Macanese Pataca</option>
                                    <option value="MKD" <?= $settings->currency === 'MKD' ? 'selected' : '' ?>>Macedonian Denar</option>
                                    <option value="MGA" <?= $settings->currency === 'MGA' ? 'selected' : '' ?>>Malagasy Ariary</option>
                                    <option value="MWK" <?= $settings->currency === 'MWK' ? 'selected' : '' ?>>Malawian Kwacha</option>
                                    <option value="MYR" <?= $settings->currency === 'MYR' ? 'selected' : '' ?>>Malaysian Ringgit</option>
                                    <option value="MVR" <?= $settings->currency === 'MVR' ? 'selected' : '' ?>>Maldivian Rufiyaa</option>
                                    <option value="MRO" <?= $settings->currency === 'MRO' ? 'selected' : '' ?>>Mauritanian Ouguiya</option>
                                    <option value="MUR" <?= $settings->currency === 'MUR' ? 'selected' : '' ?>>Mauritian Rupee</option>
                                    <option value="MXN" <?= $settings->currency === 'MXN' ? 'selected' : '' ?>>Mexican Peso</option>
                                    <option value="MDL" <?= $settings->currency === 'MDL' ? 'selected' : '' ?>>Moldovan Leu</option>
                                    <option value="MNT" <?= $settings->currency === 'MNT' ? 'selected' : '' ?>>Mongolian Tugrik</option>
                                    <option value="MAD" <?= $settings->currency === 'MAD' ? 'selected' : '' ?>>Moroccan Dirham</option>
                                    <option value="MZM" <?= $settings->currency === 'MZM' ? 'selected' : '' ?>>Mozambican Metical</option>
                                    <option value="MMK" <?= $settings->currency === 'MMK' ? 'selected' : '' ?>>Myanmar Kyat</option>
                                    <option value="NAD" <?= $settings->currency === 'NAD' ? 'selected' : '' ?>>Namibian Dollar</option>
                                    <option value="NPR" <?= $settings->currency === 'NPR' ? 'selected' : '' ?>>Nepalese Rupee</option>
                                    <option value="ANG" <?= $settings->currency === 'ANG' ? 'selected' : '' ?>>Netherlands Antillean Guilder</option>
                                    <option value="TWD" <?= $settings->currency === 'TWD' ? 'selected' : '' ?>>New Taiwan Dollar</option>
                                    <option value="NZD" <?= $settings->currency === 'NZD' ? 'selected' : '' ?>>New Zealand Dollar</option>
                                    <option value="NIO" <?= $settings->currency === 'NIO' ? 'selected' : '' ?>>Nicaraguan Córdoba</option>
                                    <option value="NGN" <?= $settings->currency === 'NGN' ? 'selected' : '' ?>>Nigerian Naira</option>
                                    <option value="KPW" <?= $settings->currency === 'KPW' ? 'selected' : '' ?>>North Korean Won</option>
                                    <option value="NOK" <?= $settings->currency === 'NOK' ? 'selected' : '' ?>>Norwegian Krone</option>
                                    <option value="OMR" <?= $settings->currency === 'OMR' ? 'selected' : '' ?>>Omani Rial</option>
                                    <option value="PKR" <?= $settings->currency === 'PKR' ? 'selected' : '' ?>>Pakistani Rupee</option>
                                    <option value="PAB" <?= $settings->currency === 'PAB' ? 'selected' : '' ?>>Panamanian Balboa</option>
                                    <option value="PGK" <?= $settings->currency === 'PGK' ? 'selected' : '' ?>>Papua New Guinean Kina</option>
                                    <option value="PYG" <?= $settings->currency === 'PYG' ? 'selected' : '' ?>>Paraguayan Guarani</option>
                                    <option value="PEN" <?= $settings->currency === 'PEN' ? 'selected' : '' ?>>Peruvian Nuevo Sol</option>
                                    <option value="PHP" <?= $settings->currency === 'PHP' ? 'selected' : '' ?>>Philippine Peso</option>
                                    <option value="PLN" <?= $settings->currency === 'PLN' ? 'selected' : '' ?>>Polish Zloty</option>
                                    <option value="QAR" <?= $settings->currency === 'QAR' ? 'selected' : '' ?>>Qatari Rial</option>
                                    <option value="RON" <?= $settings->currency === 'RON' ? 'selected' : '' ?>>Romanian Leu</option>
                                    <option value="RUB" <?= $settings->currency === 'RUB' ? 'selected' : '' ?>>Russian Ruble</option>
                                    <option value="RWF" <?= $settings->currency === 'RWF' ? 'selected' : '' ?>>Rwandan Franc</option>
                                    <option value="SVC" <?= $settings->currency === 'SVC' ? 'selected' : '' ?>>Salvadoran Colón</option>
                                    <option value="WST" <?= $settings->currency === 'WST' ? 'selected' : '' ?>>Samoan Tala</option>
                                    <option value="SAR" <?= $settings->currency === 'SAR' ? 'selected' : '' ?>>Saudi Riyal</option>
                                    <option value="RSD" <?= $settings->currency === 'RSD' ? 'selected' : '' ?>>Serbian Dinar</option>
                                    <option value="SCR" <?= $settings->currency === 'SCR' ? 'selected' : '' ?>>Seychellois Rupee</option>
                                    <option value="SLL" <?= $settings->currency === 'SLL' ? 'selected' : '' ?>>Sierra Leonean Leone</option>
                                    <option value="SGD" <?= $settings->currency === 'SGD' ? 'selected' : '' ?>>Singapore Dollar</option>
                                    <option value="SKK" <?= $settings->currency === 'SKK' ? 'selected' : '' ?>>Slovak Koruna</option>
                                    <option value="SBD" <?= $settings->currency === 'SBD' ? 'selected' : '' ?>>Solomon Islands Dollar</option>
                                    <option value="SOS" <?= $settings->currency === 'SOS' ? 'selected' : '' ?>>Somali Shilling</option>
                                    <option value="ZAR" <?= $settings->currency === 'ZAR' ? 'selected' : '' ?>>South African Rand</option>
                                    <option value="KRW" <?= $settings->currency === 'KRW' ? 'selected' : '' ?>>South Korean Won</option>
                                    <option value="XDR" <?= $settings->currency === 'XDR' ? 'selected' : '' ?>>Special Drawing Rights</option>
                                    <option value="LKR" <?= $settings->currency === 'LKR' ? 'selected' : '' ?>>Sri Lankan Rupee</option>
                                    <option value="SHP" <?= $settings->currency === 'SHP' ? 'selected' : '' ?>>St. Helena Pound</option>
                                    <option value="SDG" <?= $settings->currency === 'SDG' ? 'selected' : '' ?>>Sudanese Pound</option>
                                    <option value="SRD" <?= $settings->currency === 'SRD' ? 'selected' : '' ?>>Surinamese Dollar</option>
                                    <option value="SZL" <?= $settings->currency === 'SZL' ? 'selected' : '' ?>>Swazi Lilangeni</option>
                                    <option value="SEK" <?= $settings->currency === 'SEK' ? 'selected' : '' ?>>Swedish Krona</option>
                                    <option value="CHF" <?= $settings->currency === 'CHF' ? 'selected' : '' ?>>Swiss Franc</option>
                                    <option value="SYP" <?= $settings->currency === 'SYP' ? 'selected' : '' ?>>Syrian Pound</option>
                                    <option value="STD" <?= $settings->currency === 'STD' ? 'selected' : '' ?>>São Tomé and Príncipe Dobra</option>
                                    <option value="TJS" <?= $settings->currency === 'TJS' ? 'selected' : '' ?>>Tajikistani Somoni</option>
                                    <option value="TZS" <?= $settings->currency === 'TZS' ? 'selected' : '' ?>>Tanzanian Shilling</option>
                                    <option value="THB" <?= $settings->currency === 'THB' ? 'selected' : '' ?>>Thai Baht</option>
                                    <option value="TOP" <?= $settings->currency === 'TOP' ? 'selected' : '' ?>>Tongan pa'anga</option>
                                    <option value="TTD" <?= $settings->currency === 'TTD' ? 'selected' : '' ?>>Trinidad & Tobago Dollar</option>
                                    <option value="TND" <?= $settings->currency === 'TND' ? 'selected' : '' ?>>Tunisian Dinar</option>
                                    <option value="TRY" <?= $settings->currency === 'TRY' ? 'selected' : '' ?>>Turkish Lira</option>
                                    <option value="TMT" <?= $settings->currency === 'TMT' ? 'selected' : '' ?>>Turkmenistani Manat</option>
                                    <option value="UGX" <?= $settings->currency === 'UGX' ? 'selected' : '' ?>>Ugandan Shilling</option>
                                    <option value="UAH" <?= $settings->currency === 'UAH' ? 'selected' : '' ?>>Ukrainian Hryvnia</option>
                                    <option value="AED" <?= $settings->currency === 'AED' ? 'selected' : '' ?>>United Arab Emirates Dirham</option>
                                    <option value="UYU" <?= $settings->currency === 'UYU' ? 'selected' : '' ?>>Uruguayan Peso</option>
                                    <option value="USD" <?= $settings->currency === 'USD' ? 'selected' : '' ?>>US Dollar</option>
                                    <option value="UZS" <?= $settings->currency === 'UZS' ? 'selected' : '' ?>>Uzbekistan Som</option>
                                    <option value="VUV" <?= $settings->currency === 'VUV' ? 'selected' : '' ?>>Vanuatu Vatu</option>
                                    <option value="VEF" <?= $settings->currency === 'VEF' ? 'selected' : '' ?>>Venezuelan Bolívar</option>
                                    <option value="VND" <?= $settings->currency === 'VND' ? 'selected' : '' ?>>Vietnamese Dong</option>
                                    <option value="YER" <?= $settings->currency === 'YER' ? 'selected' : '' ?>>Yemeni Rial</option>
                                    <option value="ZMK" <?= $settings->currency === 'ZMK' ? 'selected' : '' ?>>Zambian Kwacha</option>
                                </select>
                                <div class="form-text">Select the default currency for your application</div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->
                    </div>
                    <!--end::Card body-->

                    <!--begin::Card footer-->
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Save Changes</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                    <!--end::Card footer-->
                </div>
                <!--end::Card-->
            </form>
            <!--end::Form-->
        </div>
        <!--end::Content container-->
    </div>
    <!--end::Content-->
</div>
<!--end::Content wrapper-->

<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->

<!--begin::Script-->
<script src="assets/js/custom/settings/currency-settings.js"></script>
<!--end::Script-->