// Payment Settings Form Submission
$(document).ready(function () {
    $("#paymentForm").on("submit", function (e) {
        e.preventDefault();

        const element = this;
        const submitBtn = $(element).find('button[type="submit"]');
        const indicatorLabel = submitBtn.find('.indicator-label');
        const indicatorProgress = submitBtn.find('.indicator-progress');

        // Show loading state
        indicatorLabel.addClass('d-none');
        indicatorProgress.removeClass('d-none');
        submitBtn.prop('disabled', true);

        const formData = new FormData(this);

        $.ajax({
            url: 'ajax/settings/payments-settings.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                try {
                    const response = JSON.parse(data);

                    // Show toastr notification
                    if (response.type === 'success') {
                        toastr.success(response.msg);

                        // Refresh page after 2 seconds to show updated data
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);

                    } else {
                        toastr.error(response.msg);
                    }

                } catch (e) {
                    toastr.error('Invalid response from server');
                }
            },
            error: function (xhr, status, error) {
                toastr.error('An error occurred: ' + error);
            },
            complete: function () {
                // Reset button state
                indicatorLabel.removeClass('d-none');
                indicatorProgress.addClass('d-none');
                submitBtn.prop('disabled', false);
            }
        });
    });
});