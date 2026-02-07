$(document).ready(function () {
    console.log('Manual Billing JS loaded');

    // Initialize variables
    let plans = [];
    let users = [];
    let paymentMethods = [];
    let currencies = [];
    let defaultCurrency = 'INR';
    let pincodeDebounceTimer = null;

    // Store user data in a map
    const userDataMap = new Map();

    // Toast notification function (replaces showAlert)
    function showToast(message, type = 'success') {
        // Remove existing toasts
        $('.custom-toast').remove();

        const toastClass = {
            success: 'bg-success text-white',
            error: 'bg-danger text-white',
            warning: 'bg-warning text-white',
            info: 'bg-info text-white'
        };

        const icon = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };

        const toastHtml = `
            <div class="custom-toast toast ${toastClass[type]} show position-fixed" 
                 style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;">
                <div class="toast-header ${toastClass[type]} border-0">
                    <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body d-flex align-items-center">
                    <span class="fs-4 me-3">${icon[type]}</span>
                    <div>${message}</div>
                </div>
            </div>
        `;

        const toast = $(toastHtml);
        $('body').append(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    // Modal dialog function for success/error
    function showModal(title, message, type = 'success') {
        // Remove existing modal if any
        $('#manualBillingModal').remove();

        const modalHtml = `
            <div class="modal fade" id="manualBillingModal" tabindex="-1" aria-labelledby="manualBillingModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header ${type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'}">
                            <h5 class="modal-title" id="manualBillingModalLabel">${title}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    ${type === 'success'
                ? '<i class="ki-duotone ki-check-circle fs-2hx text-success"><span class="path1"></span><span class="path2"></span></i>'
                : '<i class="ki-duotone ki-cross-circle fs-2hx text-danger"><span class="path1"></span><span class="path2"></span></i>'
            }
                                </div>
                                <h4 class="mb-3">${message}</h4>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('manualBillingModal'));
        modal.show();

        // Remove modal from DOM after hiding
        $('#manualBillingModal').on('hidden.bs.modal', function () {
            $(this).remove();
        });
    }

    // Load data from server
    function loadData() {
        console.log('Loading data...');

        $.ajax({
            url: BASE_URL + 'ajax/manual-billing/get.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('API Response:', response);

                if (response.success) {
                    plans = response.data.plans || [];
                    users = response.data.users || [];
                    paymentMethods = response.data.payment_methods || [];
                    currencies = response.data.currencies || [];
                    defaultCurrency = response.data.default_currency || 'INR';

                    console.log(`Loaded ${plans.length} plans, ${users.length} users`);

                    populateDropdowns();
                    setupEventListeners();
                    initializeSelect2();

                    // Show brief toast when data loads
                    if (plans.length > 0 && users.length > 0) {
                        setTimeout(() => {
                            //showToast('Ready to create manual billing', 'info');
                        }, 1000);
                    }
                } else {
                    showModal('Error', 'Failed to load data: ' + response.error, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                showModal('Connection Error', 'Cannot connect to server. Please check your internet connection.', 'error');
            }
        });
    }

    // Populate dropdowns
    function populateDropdowns() {
        console.log('Populating dropdowns...');

        // Populate plans dropdown
        const $planSelect = $('select[name="plan_id"]');
        $planSelect.empty();
        $planSelect.append('<option value="">Select Plan</option>');

        plans.forEach(plan => {
            const durationMonths = Math.floor(plan.duration / 30);
            $planSelect.append(`
                <option value="${plan.id}" 
                        data-amount="${plan.amount}" 
                        data-duration-months="${durationMonths}">
                    ${plan.name} - ₹${plan.amount} for ${durationMonths} months
                </option>
            `);
        });

        // Populate users dropdown
        const $userSelect = $('#userSelect');
        $userSelect.empty();
        $userSelect.append('<option value="">Select User</option>');

        // Clear previous map
        userDataMap.clear();

        users.forEach(user => {
            // Store user data in map
            userDataMap.set(user.id.toString(), {
                id: user.id,
                name: user.name || '',
                email: user.email || '',
                mobile: user.mobile || '',
                site_name: user.site_name || '',
                country: user.country || '',
                address_1: user.address_1 || '',
                address_2: user.address_2 || '',
                state: user.state || '',
                city: user.city || '',
                pin_code: user.pin_code || '',
                last_country: user.last_country || user.country || '',
                currency: user.currency || defaultCurrency,
                currency_symbol: user.currency_symbol || '₹',
                gst_number: user.gst_number || ''
            });

            // Simple option
            $userSelect.append(`
                <option value="${user.id}">
                    ${user.id} - ${user.name} (${user.site_name || 'No Site'})
                </option>
            `);
        });

        console.log('User data map created with', userDataMap.size, 'entries');

        // Populate payment methods - without MP_ prefix in dropdown
        const $paymentSelect = $('select[name="payment_method"]');
        $paymentSelect.empty();
        $paymentSelect.append('<option value="">Select Payment Method</option>');
        paymentMethods.forEach(method => {
            // Show normal names in dropdown but store with MP_ prefix in database
            $paymentSelect.append(`<option value="${method.value}">${method.label}</option>`);
        });

        // Populate currencies
        const $currencySelect = $('select[name="currency"]');
        $currencySelect.empty();
        $currencySelect.append('<option value="">Select Currency</option>');
        currencies.forEach(currency => {
            const selected = currency.code === defaultCurrency ? 'selected' : '';
            $currencySelect.append(`
                <option value="${currency.code}" ${selected}>
                    ${currency.code} - ${currency.name}
                </option>
            `);
        });

        $('#currencyDisplay').text(defaultCurrency);
    }

    // Initialize Select2
    function initializeSelect2() {
        $('#userSelect').select2({
            placeholder: "Select a user",
            allowClear: true,
            width: '100%'
        });
    }

    // Auto-fill user details
    function autoFillUserDetails(userData) {
        console.log('Auto-filling user details:', userData);

        if (!userData) {
            console.error('No user data provided');
            return;
        }

        // Fill basic info
        $('input[name="customer_name"]').val(userData.name || '');
        $('input[name="customer_email"]').val(userData.email || '');
        $('input[name="customer_mobile"]').val(userData.mobile || '');

        // Country mapping
        const countryMap = {
            'IN': 'IN', 'India': 'IN',
            'US': 'US', 'United States': 'US',
            'GB': 'GB', 'United Kingdom': 'GB',
            'AE': 'AE', 'United Arab Emirates': 'AE',
            'SG': 'SG', 'Singapore': 'SG',
            'MY': 'MY', 'Malaysia': 'MY'
        };

        let countryValue = userData.last_country || userData.country || '';
        const countryCode = countryMap[countryValue] || 'IN';
        $('select[name="country_code"]').val(countryCode).trigger('change');

        // Fill address if available
        if (userData.address_1) {
            $('input[name="address_1"]').val(userData.address_1 || '');
            $('input[name="address_2"]').val(userData.address_2 || '');
            $('input[name="state"]').val(userData.state || '');
            $('input[name="city"]').val(userData.city || '');
            $('input[name="pincode"]').val(userData.pin_code || '');

            if (userData.currency) {
                $('select[name="currency"]').val(userData.currency).trigger('change');
                $('#currencyDisplay').text(userData.currency);
            }

            if (userData.gst_number) {
                $('#gstNumber').val(userData.gst_number);
                validateGSTNumber(userData.gst_number);
            }

            // showToast(`Loaded details for ${userData.name}`, 'info');
        } else {
            // Clear address fields if no history
            $('input[name="address_1"]').val('');
            $('input[name="address_2"]').val('');
            $('input[name="state"]').val('');
            $('input[name="city"]').val('');
            $('input[name="pincode"]').val('');
            $('#gstNumber').val('');
            clearGSTValidation();

            showToast(`No previous address found for ${userData.name}. Please enter manually.`, 'info');
        }
    }

    // GST Validation
    function validateGSTNumber(gstNumber) {
        const gstInput = $('#gstNumber');
        const validationMessage = $('#gstValidationMessage');

        gstInput.removeClass('is-valid is-invalid');
        validationMessage.removeClass('text-success text-danger');
        validationMessage.html('');

        if (!gstNumber || gstNumber.trim() === '') {
            return true;
        }

        const cleanGST = gstNumber.trim().toUpperCase();
        const gstPattern = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{1}Z[0-9A-Z]{1}$/;

        if (!gstPattern.test(cleanGST)) {
            gstInput.addClass('is-invalid');
            validationMessage.addClass('text-danger');
            validationMessage.html('<i class="fas fa-times-circle me-1"></i> Invalid GST format. Example: 33AAACP1935C1Z0');
            return false;
        }

        const stateCode = parseInt(cleanGST.substring(0, 2));
        if (stateCode < 1 || stateCode > 38) {
            gstInput.addClass('is-invalid');
            validationMessage.addClass('text-danger');
            validationMessage.html('<i class="fas fa-times-circle me-1"></i> Invalid state code in GST number');
            return false;
        }

        gstInput.addClass('is-valid');
        validationMessage.addClass('text-success');
        validationMessage.html('<i class="fas fa-check-circle me-1"></i> Valid GST number');
        return true;
    }

    // Clear GST validation
    function clearGSTValidation() {
        $('#gstNumber').removeClass('is-valid is-invalid');
        $('#gstValidationMessage').html('');
    }

    // Get address from pincode API (without alert)
    function getAddressFromPincode(pincode) {
        const $pincodeInput = $('input[name="pincode"]');
        const originalBorder = $pincodeInput.css('border-color');
        $pincodeInput.css('border-color', '#009ef7');

        $.ajax({
            url: `https://api.postalpincode.in/pincode/${pincode}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response && response[0] && response[0].Status === "Success") {
                    const postOffice = response[0].PostOffice[0];

                    // Only fill if fields are empty
                    if (!$('input[name="state"]').val()) $('input[name="state"]').val(postOffice.State);
                    if (!$('input[name="city"]').val()) $('input[name="city"]').val(postOffice.District);
                    if (!$('input[name="address_1"]').val()) $('input[name="address_1"]').val(postOffice.Name + ', ' + postOffice.Block);

                    // showToast('Address fetched from postal code', 'success');
                } else {
                    showToast('No address found for this postal code', 'warning');
                }
                $pincodeInput.css('border-color', originalBorder);
            },
            error: function () {
                showToast('Failed to fetch address. Please enter manually.', 'error');
                $pincodeInput.css('border-color', originalBorder);
            }
        });
    }

    // Setup event listeners
    function setupEventListeners() {
        // Plan selection
        $('select[name="plan_id"]').on('change', function () {
            const selected = $(this).find('option:selected');
            const amount = selected.data('amount');
            const durationMonths = selected.data('duration-months');

            if (amount) $('input[name="amount"]').val(amount);
            if (durationMonths) {
                if (durationMonths >= 12) {
                    $('input[name="duration_value"]').val(Math.floor(durationMonths / 12));
                    $('select[name="duration_type"]').val('year');
                } else {
                    $('input[name="duration_value"]').val(durationMonths);
                    $('select[name="duration_type"]').val('month');
                }
            }
        });

        // User selection
        $('#userSelect').on('change', function () {
            const userId = $(this).val();
            console.log('User selected:', userId);

            if (userId) {
                const userData = userDataMap.get(userId.toString());
                console.log('Retrieved user data:', userData);

                if (userData) {
                    autoFillUserDetails(userData);
                } else {
                    showToast('User data not found', 'error');
                }
            } else {
                clearUserFields();
            }
        });

        // GST validation
        $('#gstNumber').on('input blur', function () {
            validateGSTNumber($(this).val());
        });

        // Pincode auto-fill (silent - no confirm dialog)
        $('input[name="pincode"]').on('input', function () {
            const pincode = $(this).val().trim();
            clearTimeout(pincodeDebounceTimer);

            if (pincode.length === 6 && /^\d+$/.test(pincode)) {
                pincodeDebounceTimer = setTimeout(() => {
                    const currentState = $('input[name="state"]').val();
                    const currentCity = $('input[name="city"]').val();
                    const currentAddress = $('input[name="address_1"]').val();

                    // Only fetch if all address fields are empty
                    if (!currentState && !currentCity && !currentAddress) {
                        getAddressFromPincode(pincode);
                    }
                }, 1000);
            }
        });

        // Currency change
        $('select[name="currency"]').on('change', function () {
            $('#currencyDisplay').text($(this).val() || defaultCurrency);
        });

        // Form submission
        $('#manualBillingForm').on('submit', function (e) {
            e.preventDefault();
            submitForm();
        });
    }

    // Clear user fields
    function clearUserFields() {
        $('input[name="customer_name"]').val('');
        $('input[name="customer_email"]').val('');
        $('input[name="customer_mobile"]').val('');
        $('input[name="address_1"]').val('');
        $('input[name="address_2"]').val('');
        $('input[name="state"]').val('');
        $('input[name="city"]').val('');
        $('input[name="pincode"]').val('');
        $('select[name="country_code"]').val('IN').trigger('change');
        $('select[name="currency"]').val(defaultCurrency).trigger('change');
        $('#gstNumber').val('');
        clearGSTValidation();
    }

    // Submit form
    function submitForm() {
        // Validate GST first
        const gstNumber = $('#gstNumber').val();
        if (gstNumber && !validateGSTNumber(gstNumber)) {
            showModal('Validation Error', 'Please enter a valid GST number or remove it', 'error');
            $('#gstNumber').focus();
            return;
        }

        const formData = {
            plan_id: $('select[name="plan_id"]').val(),
            user_id: $('#userSelect').val(),
            amount: $('input[name="amount"]').val(),
            duration_value: $('input[name="duration_value"]').val(),
            duration_type: $('select[name="duration_type"]').val(),
            payment_method: $('select[name="payment_method"]').val(),
            payment_id: $('input[name="payment_id"]').val(),
            customer_name: $('input[name="customer_name"]').val(),
            customer_email: $('input[name="customer_email"]').val(),
            customer_mobile: $('input[name="customer_mobile"]').val(),
            state: $('input[name="state"]').val(),
            city: $('input[name="city"]').val(),
            pincode: $('input[name="pincode"]').val(),
            address_1: $('input[name="address_1"]').val(),
            address_2: $('input[name="address_2"]').val(),
            currency: $('select[name="currency"]').val(),
            country_code: $('select[name="country_code"]').val(),
            gst_number: gstNumber || '',
            notes: $('textarea[name="notes"]').val()
        };

        // Validation
        const required = ['plan_id', 'user_id', 'amount', 'duration_value', 'duration_type', 'payment_method',
            'customer_name', 'customer_email', 'customer_mobile', 'state',
            'city', 'pincode', 'address_1', 'currency', 'country_code'];

        for (const field of required) {
            if (!formData[field]) {
                const fieldName = field.replace(/_/g, ' ');
                showModal('Validation Error', `Please fill in: ${fieldName}`, 'error');
                $(`[name="${field}"]`).focus();
                return;
            }
        }

        if (formData.pincode.length !== 6) {
            showModal('Validation Error', 'Pincode must be 6 digits', 'error');
            $('input[name="pincode"]').focus();
            return;
        }

        const $btn = $('#submitBtn');
        $btn.prop('disabled', true);
        $btn.find('.indicator-label').hide();
        $btn.find('.indicator-progress').show();

        $.ajax({
            url: BASE_URL + 'ajax/manual-billing/add.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                $btn.prop('disabled', false);
                $btn.find('.indicator-label').show();
                $btn.find('.indicator-progress').hide();

                if (response.success) {
                    showModal('Success', response.message, 'success');
                    resetForm();
                } else {
                    showModal('Error', response.message || 'Failed to create manual billing', 'error');
                }
            },
            error: function (xhr, status, error) {
                $btn.prop('disabled', false);
                $btn.find('.indicator-label').show();
                $btn.find('.indicator-progress').hide();
                showModal('Network Error', 'Unable to connect to server. Please try again.', 'error');
            }
        });
    }

    // OCR functionality (optional)
    $('#transactionImage').on('change', async function () {
        const file = this.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            showToast('Please upload an image file (PNG/JPG)', 'error');
            return;
        }

        showToast('Processing screenshot for payment details...', 'info');

        try {
            const { data: { text } } = await Tesseract.recognize(
                file,
                'eng',
                { logger: m => console.log(m) }
            );

            const paymentId = extractPaymentId(text);

            if (paymentId) {
                $('input[name="payment_id"]').val(paymentId);
                showToast('Payment ID detected from screenshot', 'success');
            } else {
                showToast('Payment ID not found. Please enter manually.', 'warning');
            }

        } catch (error) {
            console.error(error);
            showToast('Failed to read screenshot. Please enter payment ID manually.', 'error');
        }
    });

    function extractPaymentId(text) {
        const patterns = [
            /pay_[A-Za-z0-9]+/,
            /\b[a-f0-9]{32}\b/i,
            /UTR[:\s]*([A-Z0-9]+)/i,
            /Transaction\s*ID[:\s]*([A-Z0-9]+)/i,
            /Payment\s*ID[:\s]*([A-Z0-9_]+)/i,
            /\b[A-Z0-9]{12,20}\b/
        ];

        for (let pattern of patterns) {
            const match = text.match(pattern);
            if (match) {
                return match[1] || match[0];
            }
        }

        return null;
    }

    // Reset form
   window.resetForm = function () {
    // Reset the entire form
    $('#manualBillingForm')[0].reset();
    
    // Reset select fields
    $('select[name="plan_id"]').val('').trigger('change');
    $('select[name="payment_method"]').val('').trigger('change');
    $('#userSelect').val('').trigger('change');
    $('select[name="currency"]').val(defaultCurrency).trigger('change');
    $('select[name="country_code"]').val('IN').trigger('change');
    $('select[name="duration_type"]').val('year').trigger('change');
    
    // Reset display fields
    $('#currencyDisplay').text(defaultCurrency);
    
    // Clear amount and duration fields
    $('input[name="amount"]').val('');
    $('input[name="duration_value"]').val('1');
    
    // Clear GST validation
    clearGSTValidation();
    
    // Clear user fields
    clearUserFields();
    
    showToast('Form has been reset', 'info');
}

    // Start
    loadData();
});