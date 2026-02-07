// ======================================================
// Dashboard Messages Management - FINAL WORKING VERSION
// ======================================================

// 🔥 GLOBAL BASE PATH (IMPORTANT)
const basePath = 'ajax/dashboard-messages/dashboard-messages.php';

$(document).ready(function () {

    // --------------------------------------------------
    // LOAD MESSAGES ON PAGE LOAD
    // --------------------------------------------------
    loadMessages();

    // --------------------------------------------------
    // CREATE / UPDATE FORM SUBMIT
    // --------------------------------------------------
    $("#messageForm").on("submit", function (e) {
        e.preventDefault();

        const element = this;
        const submitBtn = $(element).find('button[type="submit"]');
        const indicatorLabel = submitBtn.find('.indicator-label');
        const indicatorProgress = submitBtn.find('.indicator-progress');

        indicatorLabel.addClass('d-none');
        indicatorProgress.removeClass('d-none');
        submitBtn.prop('disabled', true);

        const formData = new FormData(this);

        // DEFAULT REQUEST = CREATE
        if (!formData.has('request')) {
            formData.append('request', 'create');
        }

        $.ajax({
            url: basePath,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            success: function (data) {
                const response = typeof data === 'object' ? data : JSON.parse(data);

                if (response.type === 'success') {
                    toastr.success(response.msg, 'Success');

                    loadMessages();
                    element.reset();

                    $('select[name="seller_type[]"]').val(null).trigger('change');
                    $('#just_created_seller').prop('checked', false);

                    // REMOVE EDIT STATE
                    $('#messageForm input[name="id"]').remove();
                    $('#messageForm input[name="request"]').remove();
                } else {
                    toastr.error(response.msg || 'Something went wrong');
                }
            },

            error: function () {
                toastr.error('Network error');
            },

            complete: function () {
                indicatorLabel.removeClass('d-none');
                indicatorProgress.addClass('d-none');
                submitBtn.prop('disabled', false);
            }
        });
    });

    // --------------------------------------------------
    // LOAD MESSAGES
    // --------------------------------------------------
    function loadMessages() {
        $.ajax({
            url: basePath,
            type: 'POST',
            data: { request: 'display' },

            success: function (data) {
                const response = typeof data === 'object' ? data : JSON.parse(data);

                if (response.success && response.data) {
                    renderMessages(response.data);
                } else {
                    $('#messages_Table tbody').html(
                        '<tr><td colspan="6" class="text-center text-muted">No messages found</td></tr>'
                    );
                }
            }
        });
    }

    // --------------------------------------------------
    // RENDER TABLE
    // --------------------------------------------------
    function renderMessages(messages) {
        const tbody = $('#messages_Table tbody');
        tbody.empty();

        messages.forEach((message, index) => {
            const isActive = message.status.toLowerCase() === 'active';

            tbody.append(`
                <tr>
                    <td>${index + 1}</td>

                    <td>
                        <strong>${escapeHtml(message.title)}</strong>
                        ${message.target_badge || ''}
                        <div class="text-muted fs-7">${escapeHtml(message.description)}</div>
                    </td>

                    <td>
                        <span class="badge badge-light-primary">
                            ${escapeHtml(message.seller_type)}
                        </span>
                    </td>

                    <td>${message.expiry}</td>

                    <td>
                        <span class="badge ${isActive ? 'badge-light-success' : 'badge-light-danger'}">
                            ${message.status}
                        </span>
                    </td>

<td class="text-end">
    <div class="d-flex justify-content-end gap-2">

        <!-- EDIT (ALWAYS SHOW) -->
        <button
            class="btn btn-sm btn-light-primary edit-btn"
            data-id="${message.id}"
        >
            EDIT
        </button>

        <!-- DELETE (ALWAYS SHOW) -->
        <button
            class="btn btn-sm btn-danger delete-btn"
            data-id="${message.id}"
        >
            DELETE
        </button>

    </div>
</td>



                </tr>
            `);
        });
    }

    // --------------------------------------------------
    // DELETE MESSAGE
    // --------------------------------------------------
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete message?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(basePath, { request: 'delete', id }, function (res) {
                    const response = typeof res === 'object' ? res : JSON.parse(res);

                    if (response.type === 'success') {
                        toastr.success(response.msg);
                        loadMessages();
                    } else {
                        toastr.error(response.msg);
                    }
                });
            }
        });
    });

});

// --------------------------------------------------
// EDIT MESSAGE (GLOBAL HANDLER)
// --------------------------------------------------
$(document).on('click', '.edit-btn', function () {
    const id = $(this).data('id');

    $.post(basePath, { request: 'get_single', id }, function (res) {
        const response = typeof res === 'object' ? res : JSON.parse(res);

        if (!response.success) {
            toastr.error('Failed to load message');
            return;
        }

        const m = response.data;

        // CLEAR PREVIOUS EDIT STATE
        $('#messageForm input[name="id"]').remove();
        $('#messageForm input[name="request"]').remove();

        // FILL FORM
        $('input[name="title"]').val(m.title);
        $('textarea[name="description"]').val(m.description);
        $('input[name="expiry_value"]').val(m.expiry_value);
        $('select[name="expiry_type"]').val(m.expiry_type).trigger('change');

        if (m.seller_type) {
            $('select[name="seller_type[]"]').val(m.seller_type).trigger('change');
        } else {
            $('select[name="seller_type[]"]').val(['all']).trigger('change');
        }

        $('#just_created_seller').prop('checked', m.just_created_seller == 1);

        // SET EDIT MODE
        $('#messageForm').append(`<input type="hidden" name="id" value="${m.id}">`);
        $('#messageForm').append(`<input type="hidden" name="request" value="update">`);

        $('html, body').animate({
            scrollTop: $('#messageForm').offset().top - 100
        }, 300);
    });
});

// --------------------------------------------------
// UTILS
// --------------------------------------------------
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
