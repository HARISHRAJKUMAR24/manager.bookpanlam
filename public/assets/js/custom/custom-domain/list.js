$(document).ready(function () {
    // Initialize DataTable
    var table = $("#kt_table_custom_domains").DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        ordering: false,
        ajax: {
            url: `${BASE_URL}ajax/custom-domain/list.php`,
            type: "POST",
            data: function (d) {
                d.statusFilter = $("#statusFilter").val();
                d.startDate = $("#startDateFilter").val();
                d.endDate = $("#endDateFilter").val();
            },
            dataSrc: function (json) {
                // Update statistics cards
                if (json.stats) {
                    updateStats(json.stats);
                }
                return json.data;
            }
        },
        columns: [
            { data: "user_id" },
            { data: "user_details" },
            { data: "site_name" },
            { data: "requested_domain" },
            { data: "requested_date" },
            { data: "status" },
            { data: "actions" }
        ],
        columnDefs: [
            {
                targets: -1,
                orderable: false,
                className: 'text-end'
            }
        ],
        drawCallback: function() {
            // Re-initialize tooltips after table redraw
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Function to update statistics cards
    function updateStats(stats) {
        $('.total-count').text(stats.total || 0);
        $('.pending-count').text(stats.pending || 0);
        $('.active-count').text(stats.active || 0);
        $('.inactive-count').text(stats.inactive || 0);
        $('.rejected-count').text(stats.rejected || 0);
        $('.other-count').text(stats.other || 0);
    }

    // Function to update applied filters display
    function updateAppliedFilters() {
        const $appliedFilters = $('#appliedFilters');
        $appliedFilters.empty();
        
        const filters = [];
        
        // Check each filter
        const statusFilter = $("#statusFilter").val();
        const startDate = $("#startDateFilter").val();
        const endDate = $("#endDateFilter").val();
        const searchTerm = $("#searchFilter").val();
        
        // Status filter
        if (statusFilter) {
            let statusLabel = '';
            let statusIcon = 'ki-outline ki-info';
            
            switch(statusFilter) {
                case 'pending':
                    statusLabel = 'Status: Pending';
                    statusIcon = 'ki-outline ki-clock';
                    break;
                case 'active':
                    statusLabel = 'Status: Active';
                    statusIcon = 'ki-outline ki-check-circle';
                    break;
                case 'inactive':
                    statusLabel = 'Status: Inactive';
                    statusIcon = 'ki-outline ki-cross-circle';
                    break;
                case 'rejected':
                    statusLabel = 'Status: Rejected';
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
        table.ajax.reload();
    });

    // Search filter
    $('#searchFilter').on('keyup', function() {
        updateAppliedFilters();
        table.ajax.reload();
    });

    // Apply filters button
    $('#applyFiltersBtn').on('click', function() {
        updateAppliedFilters();
        table.ajax.reload();
    });

    // Reset all filters button
    $('#resetAllFiltersBtn').on('click', function() {
        resetAllFilters();
        updateAppliedFilters();
        table.ajax.reload();
    });

    // Function to reset all filters
    function resetAllFilters() {
        $("#statusFilter").val("");
        $("#startDateFilter, #endDateFilter").val("");
        $("#searchFilter").val("");
    }

    // Status changer dropdown change
    $(document).on('change', '.status-changer', function() {
        const select = $(this);
        const newStatus = select.val();
        const currentStatus = select.data('current-status');
        const id = select.data('id');
        const userId = select.data('user-id');
        const domain = select.data('domain');
        
        // If same status, do nothing
        if (newStatus === currentStatus) {
            return;
        }
        
        // Confirm status change
        let confirmMessage = `Change domain status from ${currentStatus} to ${newStatus}?`;
        
        Swal.fire({
            text: confirmMessage,
            icon: 'question',
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: 'Yes, update!',
            cancelButtonText: 'No, cancel',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-light'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Updating...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => { Swal.showLoading(); }
                });
                
                // Send AJAX request
                $.ajax({
                    url: `${BASE_URL}ajax/custom-domain/update-status.php`,
                    type: 'POST',
                    data: {
                        id: id,
                        user_id: userId,
                        status: newStatus,
                        domain: domain
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                text: response.message,
                                icon: 'success',
                                buttonsStyling: false,
                                confirmButtonText: 'Ok',
                                customClass: { confirmButton: 'btn btn-primary' }
                            }).then(() => {
                                select.data('current-status', newStatus);
                                table.ajax.reload(null, false); // Reload to update stats
                            });
                        } else {
                            Swal.fire({
                                text: response.message,
                                icon: 'error',
                                buttonsStyling: false,
                                confirmButtonText: 'Ok',
                                customClass: { confirmButton: 'btn btn-primary' }
                            });
                            select.val(currentStatus); // Revert
                        }
                    },
                    error: function() {
                        Swal.fire({
                            text: 'Network error. Please try again.',
                            icon: 'error',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                        select.val(currentStatus); // Revert
                    }
                });
            } else {
                select.val(currentStatus); // Revert if cancelled
            }
        });
    });

    // Copy domain to clipboard - NEW FUNCTIONALITY
    $(document).on('click', '.domain-text, .copy-domain-icon', function() {
        const domain = $(this).data('domain');
        
        // Create temporary input element
        const tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(domain).select();
        
        // Copy to clipboard
        document.execCommand('copy');
        tempInput.remove();
        
        // Show toast notification
        toastr.success('Domain copied to clipboard: ' + domain);
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize applied filters on page load
    updateAppliedFilters();
});