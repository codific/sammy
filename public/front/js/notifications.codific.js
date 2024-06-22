// displays all alerts, added by the server-side rendering
let showAlerts = function showAlerts() {
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": false,
        "positionClass": "toast-top-right mt-5",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "300",
        "timeOut": "10000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut",
        "escapeHTML": true
    }

    $("#notifications-container input").each(function (index, input) {
        let $input = $(input),
            type = $input.data('type'),
            message = $input.val();
        addFlash(type, message);
    });
};

// Dynamically adds a flash message on the page
let addFlash = function addFlash(type, message, escapeHtml = false) {
    if (type === 'error') {
        toastr.error(message, '', { escapeHtml: escapeHtml });
    } else if (type === 'warning') {
        toastr.warning(message, '', { escapeHtml: escapeHtml });
    } else if (type === 'success') {
        toastr.success(message, '', { escapeHtml: escapeHtml });
    } else {
        toastr.info(message, '', { escapeHtml: escapeHtml });
    }
}

$(document).ready(function () {
    showAlerts();
});