const CUSTOM_PHASE_DATEPICKER_SELECTOR = ".custom-js-datepicker";

$(document).ready(function () {
    $.ajax({
        type: 'GET',
        url: "/profile/user-locale",
        timeout: 10000, // 10 sec
        success: function (data) {
            $(CUSTOM_PHASE_DATEPICKER_SELECTOR).datepicker({
                format: parseDateFormat(data),
            });
        }
    });

    let parseDateFormat = function(format) {
        switch (format) {
            case "d-m-Y":
                return 'dd-MM-yy';
            case "m-d-Y":
                return 'MM-dd-yy';
            case "Y-m-d":
                return 'yy-MM-dd';
            default:
                return 'dd-MM-yyyy';
        }
    }
});