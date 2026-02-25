<!--begin:Header-->
<?php
require_once '../src/functions.php';
require_once '../src/database.php';
renderTemplate('header');

// Get database connection
$pdo = getDbConnection();

// Get dashboard statistics
$stats = getDashboardStatistics($pdo);
$earningsStats = getPlatformEarningsStats($pdo);
// Get plan statistics
$planStats = getDetailedPlanStats($pdo);
$plans = $planStats['plans'];
$totalUsers = $planStats['total_users'];
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
            <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
              Dashboard
            </li>
          </ul>
          <h1 class="page-heading d-flex flex-column justify-content-center text-dark fw-bolder fs-1 lh-0">
            Analytics Dashboard
          </h1>
        </div>
      </div>
    </div>
  </div>
  <!--end::Toolbar-->

  <!--begin::Content-->
  <div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-fluid">

      <!-- Row 1: Seller Statistics -->
      <div class="row g-5 g-xl-8 mb-5">
        <!-- Total Sellers -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Total Sellers</span>
                <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['total_sellers']) ?></span>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-profile-user fs-2 text-primary me-2"></i>
                  <span class="text-gray-600 fs-7">Active: <?= number_format($stats['total_sellers'] - $stats['suspended_sellers']) ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- New Sellers (Last Month) -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">New Sellers</span>
                <span class="fs-2hx fw-bold text-dark"><?= $stats['new_sellers_last_month'] ?></span>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-chart-line-down fs-2 text-warning me-2"></i>
                  <span class="text-gray-600 fs-7">Last month analytics</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- New Sellers (Today) -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Today's New Sellers</span>
                <span class="fs-2hx fw-bold text-dark"><?= $stats['new_sellers_today'] ?></span>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-calendar fs-2 text-info me-2"></i>
                  <span class="text-gray-600 fs-7">Today analytics</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- New Sellers (This Month) -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">New Sellers</span>
                <span class="fs-2hx fw-bold text-dark"><?= $stats['new_sellers_this_month'] ?></span>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-chart-line-up fs-2 text-success me-2"></i>
                  <span class="text-gray-600 fs-7">This month analytics</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 2: Suspended Sellers -->
      <div class="row g-5 g-xl-8 mb-5">
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Suspended Sellers</span>
                <span class="fs-2hx fw-bold text-dark"><?= $stats['suspended_sellers'] ?></span>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-security-user fs-2 text-danger me-2"></i>
                  <span class="text-gray-600 fs-7">Suspended accounts</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Today Earnings -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Today Earnings</span>
                <div class="d-flex align-items-center">
                  <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                  <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['earnings_today']) ?></span>
                </div>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-arrow-up fs-2 text-success me-2"></i>
                  <span class="text-gray-600 fs-7">Today's revenue</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- This Week Earnings -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">This week Earnings</span>
                <div class="d-flex align-items-center">
                  <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                  <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['earnings_this_week']) ?></span>
                </div>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-chart-line-up fs-2 text-primary me-2"></i>
                  <span class="text-gray-600 fs-7">Weekly revenue</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Last Month Earnings -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Last month Earnings</span>
                <div class="d-flex align-items-center">
                  <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                  <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['earnings_last_month']) ?></span>
                </div>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-chart-line-down fs-2 text-warning me-2"></i>
                  <span class="text-gray-600 fs-7">Previous month</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 3: More Earnings -->
      <div class="row g-5 g-xl-8 mb-5">
        <!-- This Month Earnings -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">This month Earnings</span>
                <div class="d-flex align-items-center">
                  <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                  <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['earnings_this_month']) ?></span>
                </div>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-chart-line-up fs-2 text-success me-2"></i>
                  <span class="text-gray-600 fs-7">Current month</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Today GST -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Today GST</span>
                <div class="d-flex align-items-center">
                  <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                  <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['gst_today']) ?></span>
                </div>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-calendar fs-2 text-info me-2"></i>
                  <span class="text-gray-600 fs-7">Today's GST</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 4: GST Continued -->
      <div class="row g-5 g-xl-8 mb-5">
        <!-- This Month GST -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">This month GST</span>
                <div class="d-flex align-items-center">
                  <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                  <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['gst_this_month']) ?></span>
                </div>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-chart-line-up fs-2 text-success me-2"></i>
                  <span class="text-gray-600 fs-7">Current month GST</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Last Month GST -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Last month GST</span>
                <div class="d-flex align-items-center">
                  <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                  <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['gst_last_month']) ?></span>
                </div>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-chart-line-down fs-2 text-warning me-2"></i>
                  <span class="text-gray-600 fs-7">Previous month GST</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Total GST (Second) - Duplicate as per your layout -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between p-6">
              <div>
                <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Total GST</span>
                <div class="d-flex align-items-center">
                  <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                  <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['total_gst']) ?></span>
                </div>
              </div>
              <div class="mt-5">
                <div class="d-flex align-items-center">
                  <i class="ki-duotone ki-crown fs-2 text-primary me-2"></i>
                  <span class="text-gray-600 fs-7">Total GST collected</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>



    </div>
  </div>
  <!--end::Content-->

  <!--begin::Content-->
  <div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-fluid">
      <!-- Row 5: Subscription Plans Container -->
      <div class="row g-5 g-xl-8 mb-5 mt-5">
        <div class="col-12">
          <!--begin::Card-->
          <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header pt-7">
              <!--begin::Card title-->
              <div class="card-title">
                <h2 class="fw-bold">Total Earnings & GST</h2>
              </div>
              <!--end::Card title-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-5">

              <!-- Plan Counts Grid -->
              <div class="row g-5 g-xl-8">
                <!-- Total Earnings  -->
                <div class="col-md-6 col-xl-3">
                  <div class="card card-flush h-md-100">
                    <div class="card-body d-flex flex-column justify-content-between p-6">
                      <div>
                        <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Total Earnings</span>
                        <div class="d-flex align-items-center">
                          <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                          <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['total_earnings']) ?></span>
                        </div>
                       
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Total Without GST -->
                <div class="col-md-6 col-xl-3">
                  <div class="card card-flush h-md-100">
                    <div class="card-body d-flex flex-column justify-content-between p-6">
                      <div>
                        <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Total Without GST</span>
                        <div class="d-flex align-items-center">
                          <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                          <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['total_earnings'] - $stats['total_gst']) ?></span>
                        </div>
                        
                      </div>

                    </div>
                  </div>
                </div>

                <!-- Total GST -->
                <div class="col-md-6 col-xl-3">
                  <div class="card card-flush h-md-100">
                    <div class="card-body d-flex flex-column justify-content-between p-6">
                      <div>
                        <span class="text-gray-400 fw-semibold fs-6 d-block mb-2">Total GST</span>
                        <div class="d-flex align-items-center">
                          <span class="fs-4 fw-semibold text-gray-400 me-1">₹</span>
                          <span class="fs-2hx fw-bold text-dark"><?= number_format($stats['total_gst']) ?></span>
                        </div>
                        
                      </div>

                    </div>
                  </div>
                </div>
              </div>


            </div>
            <!--end::Card body-->
          </div>
          <!--end::Card-->
        </div>
      </div>
    </div>
  </div>
  <!--end::Content-->

  <!--begin::Content-->
  <div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-fluid">
      <!-- Row 5: Subscription Plans Container -->
      <div class="row g-5 g-xl-8 mb-5 mt-5">
        <div class="col-12">
          <!--begin::Card-->
          <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header pt-7">
              <!--begin::Card title-->
              <div class="card-title">
                <h2 class="fw-bold">Customer's Subscription Plans Overview</h2>
              </div>
              <!--end::Card title-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-5">


              <!-- Plan Counts Grid -->
              <div class="row g-5 g-xl-8">
                <?php foreach ($plans as $plan): ?>
                  <div class="col-md-6 col-xl-3">
                    <div class="card card-dashed h-md-100">
                      <div class="card-body d-flex flex-column p-6">
                        <div class="d-flex align-items-center mb-4">
                          <div class="symbol symbol-40px symbol-circle bg-light-<?= $plan['color'] ?> me-3">
                            <i class="ki-duotone ki-crown fs-2 text-<?= $plan['color'] ?>">
                              <span class="path1"></span>
                              <span class="path2"></span>
                            </i>
                          </div>
                          <div>
                            <span class="text-gray-400 fw-semibold fs-7 d-block"><?= $plan['plan_name'] ?></span>
                            <span class="fs-2x fw-bold text-dark"><?= number_format($plan['count']) ?></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>


            </div>
            <!--end::Card body-->
          </div>
          <!--end::Card-->
        </div>
      </div>
    </div>
  </div>
  <!--end::Content-->

  <!--begin::Content-->
  <div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-fluid">

      <div class="row g-5 g-xl-8 mb-5 mt-5">
        <div class="col-12">
          <!--begin::Card-->
          <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header pt-7">
              <div class="card-title">
                <h2 class="fw-bold">Platform Earnings</h2>
              </div>

            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-5">

              <!-- Total Platform Earnings Card -->
              <div class="row g-5 g-xl-8 mb-8">
                <div class="col-12">
                  <div class="card card-dashed bg-light-primary">
                    <div class="card-body p-8">
                      <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-60px symbol-circle bg-primary me-4">
                          <i class="ki-duotone ki-wallet fs-2x text-white"></i>
                        </div>
                        <div>
                          <span class="text-gray-600 fw-semibold fs-6 d-block mb-1">Total Platform Earnings</span>
                          <span class="fs-2x fw-bold text-dark">₹ <?= number_format($earningsStats['total']['total'] ?? 0) ?></span>
                        </div>
                      </div>

                      <div class="row g-5">
                        <!-- Paid -->
                        <div class="col-md-6">
                          <div class="d-flex align-items-center bg-light-success rounded p-5">
                            <i class="ki-duotone ki-check-circle fs-2x text-success me-3"></i>
                            <div>
                              <span class="text-gray-600 fs-7 d-block">Paid</span>
                              <span class="fw-bold text-success fs-1">₹ <?= number_format($earningsStats['total']['paid'] ?? 0) ?></span>

                            </div>
                          </div>
                        </div>

                        <!-- Unpaid -->
                        <div class="col-md-6">
                          <div class="d-flex align-items-center bg-light-danger rounded p-5">
                            <i class="ki-duotone ki-cross-circle fs-2x text-danger me-3"></i>
                            <div>
                              <span class="text-gray-600 fs-7 d-block">Unpaid / Pending</span>
                              <span class="fw-bold text-danger fs-1">₹ <?= number_format($earningsStats['total']['unpaid'] ?? 0) ?></span>

                            </div>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>

              <!-- Period-wise Simple Cards -->
              <div class="row g-5 g-xl-8">
                <!-- Today -->
                <div class="col-md-6 col-xl-3">
                  <div class="card card-flush">
                    <div class="card-body p-6">
                      <div class="d-flex align-items-center mb-3">
                        <i class="ki-duotone ki-calendar fs-2 text-info me-2"></i>
                        <span class="text-gray-500 fw-semibold">Today</span>
                      </div>
                      <span class="fs-2x fw-bold text-dark d-block mb-3">₹ <?= number_format($earningsStats['today']['total'] ?? 0) ?></span>
                      <div class="d-flex justify-content-between">
                        <span class="text-gray-500 fs-7">Paid:</span>
                        <span class="fw-bold text-success">₹ <?= number_format($earningsStats['today']['paid'] ?? 0) ?></span>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span class="text-gray-500 fs-7">Unpaid:</span>
                        <span class="fw-bold text-danger">₹ <?= number_format($earningsStats['today']['unpaid'] ?? 0) ?></span>
                      </div>

                    </div>
                  </div>
                </div>

                <!-- This Week -->
                <div class="col-md-6 col-xl-3">
                  <div class="card card-flush">
                    <div class="card-body p-6">
                      <div class="d-flex align-items-center mb-3">
                        <i class="ki-duotone ki-chart-line-up fs-2 text-primary me-2"></i>
                        <span class="text-gray-500 fw-semibold">This Week</span>
                      </div>
                      <span class="fs-2x fw-bold text-dark d-block mb-3">₹ <?= number_format($earningsStats['this_week']['total'] ?? 0) ?></span>
                      <div class="d-flex justify-content-between">
                        <span class="text-gray-500 fs-7">Paid:</span>
                        <span class="fw-bold text-success">₹ <?= number_format($earningsStats['this_week']['paid'] ?? 0) ?></span>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span class="text-gray-500 fs-7">Unpaid:</span>
                        <span class="fw-bold text-danger">₹ <?= number_format($earningsStats['this_week']['unpaid'] ?? 0) ?></span>
                      </div>

                    </div>
                  </div>
                </div>

                <!-- This Month -->
                <div class="col-md-6 col-xl-3">
                  <div class="card card-flush">
                    <div class="card-body p-6">
                      <div class="d-flex align-items-center mb-3">
                        <i class="ki-duotone ki-chart-simple fs-2 text-success me-2"></i>
                        <span class="text-gray-500 fw-semibold">This Month</span>
                      </div>
                      <span class="fs-2x fw-bold text-dark d-block mb-3">₹ <?= number_format($earningsStats['this_month']['total'] ?? 0) ?></span>
                      <div class="d-flex justify-content-between">
                        <span class="text-gray-500 fs-7">Paid:</span>
                        <span class="fw-bold text-success">₹ <?= number_format($earningsStats['this_month']['paid'] ?? 0) ?></span>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span class="text-gray-500 fs-7">Unpaid:</span>
                        <span class="fw-bold text-danger">₹ <?= number_format($earningsStats['this_month']['unpaid'] ?? 0) ?></span>
                      </div>

                    </div>
                  </div>
                </div>

                <!-- Last Month -->
                <div class="col-md-6 col-xl-3">
                  <div class="card card-flush">
                    <div class="card-body p-6">
                      <div class="d-flex align-items-center mb-3">
                        <i class="ki-duotone ki-chart-line-down fs-2 text-warning me-2"></i>
                        <span class="text-gray-500 fw-semibold">Last Month</span>
                      </div>
                      <span class="fs-2x fw-bold text-dark d-block mb-3">₹ <?= number_format($earningsStats['last_month']['total'] ?? 0) ?></span>
                      <div class="d-flex justify-content-between">
                        <span class="text-gray-500 fs-7">Paid:</span>
                        <span class="fw-bold text-success">₹ <?= number_format($earningsStats['last_month']['paid'] ?? 0) ?></span>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span class="text-gray-500 fs-7">Unpaid:</span>
                        <span class="fw-bold text-danger">₹ <?= number_format($earningsStats['last_month']['unpaid'] ?? 0) ?></span>
                      </div>

                    </div>
                  </div>
                </div>
              </div>


            </div>
            <!--end::Card body-->
          </div>
          <!--end::Card-->
        </div>
      </div>
    </div>
  </div>
  <!--end::Content-->
</div>
<!--end::Content wrapper-->

<style>
  .card {
    transition: all 0.2s ease;
    border: 1px solid var(--bs-gray-200);
    box-shadow: 0 0.1rem 0.5rem rgba(0, 0, 0, 0.05);
    cursor: default;
  }

  .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
    border-color: var(--bs-primary);
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .fs-2hx {
      font-size: 1.75rem !important;
    }

    .col-md-6 {
      margin-bottom: 1rem;
    }
  }

  /* Custom badge for active/inactive */
  .stat-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    background-color: var(--bs-light);
    color: var(--bs-gray-700);
  }
</style>

<!--include:Footer-->
<?php renderTemplate('footer'); ?>
<!--end:Footer-->