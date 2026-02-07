$(document).ready(function () {
    
    // Login Button
    $('#loginBtn').on('click', function() {
        alert('Login functionality will be added later');
    });
    
    // Function to show loading state
    function showLoading(button, text) {
        button.prop('disabled', true);
        button.find('.indicator-label').text(text);
        button.find('.indicator-progress').show();
    }
    
    // Function to hide loading state
    function hideLoading(button, originalText) {
        button.prop('disabled', false);
        button.find('.indicator-label').text(originalText);
        button.find('.indicator-progress').hide();
    }
    
    // Suspend Form (only shows when user is NOT suspended)
    $('#suspendForm').on('submit', function (e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#suspendBtn');
        const originalText = submitBtn.find('.indicator-label').text();
        
        // Show loading state
        showLoading(submitBtn, 'Suspending...');
        
        // Get form data
        const formData = new FormData(this);
        
        // Make AJAX request
        $.ajax({
            url: BASE_URL + 'ajax/users/suspend.php',
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log('Response:', response);
                try {
                    const data = JSON.parse(response);
                    toastr[data.type](data.msg);
                    
                    if (data.type === 'success') {
                        // Reload page immediately to reflect changes
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    // If response is not JSON, assume success
                    toastr.success('Account suspended successfully!');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                toastr.error('Network error occurred');
            },
            complete: function () {
                // Reset button state after 1.5 seconds
                setTimeout(() => {
                    hideLoading(submitBtn, originalText);
                }, 1500);
            }
        });
    });
    
    // Unsuspend Form (only shows when user IS suspended)
    $('#unsuspendForm').on('submit', function (e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#unsuspendBtn');
        const originalText = submitBtn.find('.indicator-label').text();
        
        // Show loading state
        showLoading(submitBtn, 'Unsuspending...');
        
        // Get form data
        const formData = new FormData(this);
        
        // Make AJAX request
        $.ajax({
            url: BASE_URL + 'ajax/users/suspend.php',
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log('Response:', response);
                try {
                    const data = JSON.parse(response);
                    toastr[data.type](data.msg);
                    
                    if (data.type === 'success') {
                        // Reload page immediately to reflect changes
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    // If response is not JSON, assume success
                    toastr.success('Account unsuspended successfully!');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                toastr.error('Network error occurred');
            },
            complete: function () {
                // Reset button state after 1.5 seconds
                setTimeout(() => {
                    hideLoading(submitBtn, originalText);
                }, 1500);
            }
        });
    });
    
    // Delete confirmation text
    $('#confirmText').on('keyup', function () {
        const deleteBtn = $('#deleteBtn');
        if ($(this).val().toUpperCase() === 'DELETE') {
            deleteBtn.prop('disabled', false);
        } else {
            deleteBtn.prop('disabled', true);
        }
    });
    
    // Delete button click
    $('#deleteBtn').on('click', function () {
        $('#deleteConfirmationModal').modal('show');
    });
    
    // Confirm delete button
    $('#confirmDeleteBtn').on('click', function () {
        const deleteBtn = $(this);
        const originalText = deleteBtn.find('.indicator-label').text();
        
        // Get user_id from hidden input
        const user_id = $('#user_id_hidden').val();
        
        // Show loading state
        showLoading(deleteBtn, 'Deleting...');
        
        // Make AJAX request
        $.ajax({
            url: BASE_URL + 'ajax/users/delete.php',
            type: "POST",
            data: { 
                user_id: user_id
            },
            success: function (response) {
                console.log('Delete response:', response);
                try {
                    const data = JSON.parse(response);
                    toastr[data.type](data.msg);
                    
                    if (data.type === 'success') {
                        $('#deleteConfirmationModal').modal('hide');
                        // Redirect to users list after 1.5 seconds
                        setTimeout(() => {
                            window.location.href = BASE_URL + 'users';
                        }, 1500);
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    // If response is not JSON, assume success
                    toastr.success('User deleted successfully!');
                    $('#deleteConfirmationModal').modal('hide');
                    setTimeout(() => {
                        window.location.href = BASE_URL + 'users';
                    }, 1500);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                toastr.error('Network error occurred');
            },
            complete: function () {
                // Reset button state after 1.5 seconds
                setTimeout(() => {
                    hideLoading(deleteBtn, originalText);
                }, 1500);
            }
        });
    });
});