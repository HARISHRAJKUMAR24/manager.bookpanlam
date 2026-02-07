$(document).ready(function () {
  var table = $("#kt_table_subscription_histories").DataTable({
    processing: true,
    serverSide: true,
    pageLength: 10,
    ordering: false,
    ajax: {
      url: `${BASE_URL}ajax/subscription-histories/list.php`,
      type: "POST",
      data: function (d) {
        // Add your custom filters
        d.planFilter = $("#planFilter").val();
        d.gstFilter = $("#gstFilter").val();
        d.paymentMethodFilter = $("#paymentMethodFilter").val();
        d.startDateFilter = $("#startDateFilter").val();
        d.endDateFilter = $("#endDateFilter").val();
      }
    },
    columns: [
      { data: "checkbox" },
      { data: "invoice_number" },
      { data: "customer_info" },
      { data: "plan_name" },
      { data: "amount" },
      { data: "payment_method" },
      { data: "payment_id" }
    ],
    createdRow: function (row, data, dataIndex) {
      // Initialize tooltips
      $('[data-bs-toggle="tooltip"]', row).tooltip();
    }
  });

  // Search filter (invoice, payment ID, customer, plan)
  $('#searchFilter').on('keyup', function() {
    table.search(this.value).draw();
  });

  // Apply filters button
  $('#applyFiltersBtn').on('click', function() {
    updateAppliedFilters();
    table.draw();
  });

  // Reset all filters button
  $('#resetAllFiltersBtn').on('click', function() {
    resetAllFilters();
    updateAppliedFilters();
    table.draw();
  });

  // Function to reset all filters
  function resetAllFilters() {
    $("#planFilter, #gstFilter, #paymentMethodFilter").val("");
    $("#startDateFilter, #endDateFilter").val("");
    $("#searchFilter").val("");
  }

  // Function to update applied filters display
  function updateAppliedFilters() {
    const $appliedFilters = $('#appliedFilters');
    $appliedFilters.empty();
    
    const filters = [];
    
    // Check each filter
    const planFilter = $("#planFilter").val();
    const gstFilter = $("#gstFilter").val();
    const paymentMethodFilter = $("#paymentMethodFilter").val();
    const startDate = $("#startDateFilter").val();
    const endDate = $("#endDateFilter").val();
    const searchTerm = $("#searchFilter").val();
    
    // Get plan name if selected
    if (planFilter) {
      const planName = $("#planFilter option:selected").text();
      filters.push({
        type: 'plan',
        value: planFilter,
        label: `Plan: ${planName}`,
        icon: 'ki-outline ki-basket'
      });
    }
    
    // GST filter
    if (gstFilter === 'yes') {
      filters.push({
        type: 'gst',
        value: gstFilter,
        label: 'GST: Yes',
        icon: 'ki-outline ki-verify'
      });
    } else if (gstFilter === 'no') {
      filters.push({
        type: 'gst',
        value: gstFilter,
        label: 'GST: No',
        icon: 'ki-outline ki-cross'
      });
    }
    
    // Payment method filter
    if (paymentMethodFilter) {
      let paymentLabel = '';
      let paymentIcon = 'ki-outline ki-credit-cart';
      
      switch(paymentMethodFilter) {
        case 'manual':
          paymentLabel = 'Payment: Manual Payments';
          paymentIcon = 'ki-outline ki-user-tick';
          break;
        case 'razorpay':
          paymentLabel = 'Payment: Razorpay';
          break;
        case 'phone pay':
          paymentLabel = 'Payment: Phone Pay';
          break;
        case 'payu':
          paymentLabel = 'Payment: PayU';
          break;
        default:
          paymentLabel = `Payment: ${paymentMethodFilter.charAt(0).toUpperCase() + paymentMethodFilter.slice(1)}`;
      }
      
      filters.push({
        type: 'paymentMethod',
        value: paymentMethodFilter,
        label: paymentLabel,
        icon: paymentIcon
      });
    }
    
    // Date range filter
    if (startDate || endDate) {
      let dateLabel = 'Date: ';
      if (startDate && endDate) {
        dateLabel += `${formatDate(startDate)} to ${formatDate(endDate)}`;
      } else if (startDate) {
        dateLabel += `From ${formatDate(startDate)}`;
      } else if (endDate) {
        dateLabel += `Until ${formatDate(endDate)}`;
      }
      filters.push({
        type: 'dateRange',
        value: { start: startDate, end: endDate },
        label: dateLabel,
        icon: 'ki-outline ki-calendar'
      });
    }
    
    // Search term filter
    if (searchTerm) {
      filters.push({
        type: 'search',
        value: searchTerm,
        label: `Search: "${searchTerm}"`,
        icon: 'ki-outline ki-magnifier'
      });
    }
    
    // Display applied filters
    filters.forEach(filter => {
      const $badge = $(`
        <div class="applied-filter-badge">
          <i class="${filter.icon} fs-4 text-gray-600 me-1"></i>
          <span>${filter.label}</span>
          <span class="remove-filter" data-type="${filter.type}">×</span>
        </div>
      `);
      
      $appliedFilters.append($badge);
    });
  }
  
  // Function to format date
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }
  
  // Remove individual filter
  $(document).on('click', '.remove-filter', function() {
    const filterType = $(this).data('type');
    
    switch(filterType) {
      case 'plan':
        $("#planFilter").val("");
        break;
      case 'gst':
        $("#gstFilter").val("");
        break;
      case 'paymentMethod':
        $("#paymentMethodFilter").val("");
        break;
      case 'dateRange':
        $("#startDateFilter").val("");
        $("#endDateFilter").val("");
        break;
      case 'search':
        $("#searchFilter").val("");
        break;
    }
    
    updateAppliedFilters();
    table.draw();
  });
  
  // Initialize tooltips on table redraw
  table.on('draw', function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
  });
  
  // Update applied filters on page load
  updateAppliedFilters();
});