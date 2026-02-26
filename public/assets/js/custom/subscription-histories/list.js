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
        d.statusFilter = $("#statusFilter").val();
      }
    },
    columns: [
      { data: "checkbox" },
      { data: "invoice_number" },
      { data: "customer_info" },
      { data: "plan_name" },
      { data: "amount" },
      { data: "payment_method" },
      { data: "payment_id" },
      { data: "status" },
      { 
        data: null,
        render: function(data, type, row) {
          return '<button class="btn btn-sm btn-light btn-active-light-primary delete-subscription" data-id="' + row.id + '">Delete</button>';
        }
      }
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

  // Handle status change with SweetAlert2 confirmation
  $(document).on('click', '.status-change', function(e) {
    e.preventDefault();
    
    const id = $(this).data('id');
    const status = $(this).data('status');
    const $this = $(this);
    
    let confirmTitle = 'Change Status';
    let confirmText = `Are you sure you want to mark this as ${status}?`;
    let confirmIcon = 'question';
    
    if (status === 'refunded') {
      confirmText = 'Are you sure you want to mark this as refunded? This action can be reversed later.';
    } else if (status === 'cancelled') {
      confirmText = 'Are you sure you want to cancel this subscription?';
    }
    
    // Use SweetAlert2 if available, otherwise use confirm
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: confirmTitle,
        text: confirmText,
        icon: confirmIcon,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
      }).then((result) => {
        if (result.isConfirmed) {
          updateStatus(id, status);
        }
      });
    } else {
      if (confirm(confirmText)) {
        updateStatus(id, status);
      }
    }
  });

  // Handle delete with SweetAlert2 confirmation
  $(document).on('click', '.delete-subscription', function(e) {
    e.preventDefault();
    
    const id = $(this).data('id');
    
    // Use SweetAlert2 if available
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Delete Subscription History',
        text: 'Are you sure you want to delete this subscription history? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          deleteSubscription(id);
        }
      });
    } else {
      if (confirm('Are you sure you want to delete this subscription history? This action cannot be undone.')) {
        deleteSubscription(id);
      }
    }
  });

  // Function to update status
  function updateStatus(id, status) {
    $.ajax({
      url: `${BASE_URL}ajax/subscription-histories/update-status.php`,
      type: 'POST',
      data: {
        id: id,
        status: status
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Show success toast
          if (typeof toastr !== 'undefined') {
            toastr.success(response.message);
          } else {
            alert(response.message);
          }
          // Reload table
          table.ajax.reload();
        } else {
          // Show error toast
          if (typeof toastr !== 'undefined') {
            toastr.error(response.message);
          } else {
            alert('Error: ' + response.message);
          }
        }
      },
      error: function(xhr, status, error) {
        // Show error toast
        if (typeof toastr !== 'undefined') {
          toastr.error('An error occurred while updating status');
        } else {
          alert('An error occurred while updating status');
        }
        console.error('AJAX Error:', error);
        console.error('Response:', xhr.responseText);
      }
    });
  }

  // Function to delete subscription
  function deleteSubscription(id) {
    $.ajax({
      url: `${BASE_URL}ajax/subscription-histories/delete.php`,
      type: 'POST',
      data: {
        id: id
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Show success toast
          if (typeof toastr !== 'undefined') {
            toastr.success(response.message);
            
            // Optional: Show success with SweetAlert2 as well
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                title: 'Deleted!',
                text: response.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
              });
            }
          } else {
            alert(response.message);
          }
          // Reload table
          table.ajax.reload();
        } else {
          // Show error toast
          if (typeof toastr !== 'undefined') {
            toastr.error(response.message);
          } else {
            alert('Error: ' + response.message);
          }
        }
      },
      error: function(xhr, status, error) {
        // Show error toast
        if (typeof toastr !== 'undefined') {
          toastr.error('An error occurred while deleting');
        } else {
          alert('An error occurred while deleting');
        }
        console.error('AJAX Error:', error);
        console.error('Response:', xhr.responseText);
      }
    });
  }

  // Function to reset all filters
  function resetAllFilters() {
    $("#planFilter, #gstFilter, #paymentMethodFilter, #statusFilter").val("");
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
    const statusFilter = $("#statusFilter").val();
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
    
    // Status filter
    if (statusFilter) {
      let statusLabel = '';
      let statusIcon = 'ki-outline ki-status';
      
      switch(statusFilter) {
        case 'active':
          statusLabel = 'Status: Active';
          statusIcon = 'ki-outline ki-check-circle';
          break;
        case 'refunded':
          statusLabel = 'Status: Refunded';
          statusIcon = 'ki-outline ki-reload';
          break;
        case 'cancelled':
          statusLabel = 'Status: Cancelled';
          statusIcon = 'ki-outline ki-cross-circle';
          break;
      }
      
      filters.push({
        type: 'status',
        value: statusFilter,
        label: statusLabel,
        icon: statusIcon
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
      case 'status':
        $("#statusFilter").val("");
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
    
    // Reinitialize dropdowns
    $('.dropdown-toggle').dropdown();
  });
  
  // Update applied filters on page load
  updateAppliedFilters();
});