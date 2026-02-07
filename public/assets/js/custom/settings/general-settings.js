// General Settings Form Submission
$(document).ready(function () {
    // Timezone clock functionality
    const timezoneSelect = document.getElementById('timezone');
    const clockDisplay = document.getElementById('clock-display');

    function updateClock() {
        if (!timezoneSelect || !clockDisplay) return;

        const selectedTimezone = timezoneSelect.value;
        const now = new Date();

        try {
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: selectedTimezone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            // Format the date in day/month/year format
            const parts = formatter.formatToParts(now);
            const day = parts.find(part => part.type === 'day').value;
            const month = parts.find(part => part.type === 'month').value;
            const year = parts.find(part => part.type === 'year').value;
            const hour = parts.find(part => part.type === 'hour').value;
            const minute = parts.find(part => part.type === 'minute').value;
            const second = parts.find(part => part.type === 'second').value;
            const dayPeriod = parts.find(part => part.type === 'dayPeriod').value;

            // Create the formatted string in day/month/year format
            const formattedTime = `${day}/${month}/${year} ${hour}:${minute}:${second} ${dayPeriod}`;
            clockDisplay.textContent = `${formattedTime} (${selectedTimezone})`;
        } catch (error) {
            clockDisplay.textContent = 'Invalid timezone';
        }
    }

    // Alternative simpler method using manual formatting
    function updateClockSimple() {
        if (!timezoneSelect || !clockDisplay) return;

        const selectedTimezone = timezoneSelect.value;
        const now = new Date();

        try {
            // Create formatter for the specific timezone
            const timeFormatter = new Intl.DateTimeFormat('en-US', {
                timeZone: selectedTimezone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            const dateFormatter = new Intl.DateTimeFormat('en-US', {
                timeZone: selectedTimezone,
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            // Format date and time separately
            const timeString = timeFormatter.format(now);
            const dateString = dateFormatter.format(now);

            // Convert date from MM/DD/YYYY to DD/MM/YYYY
            const [month, day, year] = dateString.split('/');
            const formattedDate = `${day}/${month}/${year}`;

            clockDisplay.textContent = `${formattedDate} ${timeString} (${selectedTimezone})`;
        } catch (error) {
            clockDisplay.textContent = 'Invalid timezone';
        }
    }

    // Update clock when timezone changes
    if (timezoneSelect) {
        timezoneSelect.addEventListener('change', updateClockSimple);
    }

    // Update clock every second
    setInterval(updateClockSimple, 1000);
    updateClockSimple(); // Initial call

    $("form").on("submit", function (e) {
        e.preventDefault();

        const element = this;
        const action = $(this).attr("action");
        const submitBtn = $(element).find('button[type="submit"]');
        const indicatorLabel = submitBtn.find('.indicator-label');
        const indicatorProgress = submitBtn.find('.indicator-progress');

        // Show loading state
        indicatorLabel.addClass('d-none');
        indicatorProgress.removeClass('d-none');
        submitBtn.prop('disabled', true);

        const formData = new FormData(this);

        $.ajax({
            url: action,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                try {
                    const response = JSON.parse(data);
                    toastr[response.type](response.msg);

                    if (response.type === "success") {
                        // Refresh page after 2 seconds to show updated data
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
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

    // Image preview functionality
    const imageInput = document.querySelector('input[name="image"]');
    const imagePreview = document.querySelector('.image-input-wrapper');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.style.backgroundImage = `url(${e.target.result})`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Handle remove button
        const removeBtn = document.querySelector('[data-kt-image-input-action="remove"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                imagePreview.style.backgroundImage = `url('${BASE_URL}assets/media/avatars/blank.png')`;
                imageInput.value = '';
            });
        }

        // Handle cancel button
        const cancelBtn = document.querySelector('[data-kt-image-input-action="cancel"]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const currentImage = document.querySelector('input[name="existing_image"]').value;
                const adminImage = currentImage ? `${UPLOADS_URL}${currentImage}` : `${BASE_URL}assets/media/avatars/blank.png`;
                imagePreview.style.backgroundImage = `url('${adminImage}')`;
                imageInput.value = '';
            });
        }
    }
});