$(document).ready(function () {
    let sessionModal = $("#session-timeout");

    if (sessionModal.length > 0) {
        setInterval(function () {
            $.ajax({
                url: sessionModal.data("url"),
                type: "get",
                success: function (data) {
                    let loginFormIsPresent = data.includes("<form name=\"user_login\" method=\"post\" class=\"login-form\">");
                    if (!sessionModal.hasClass("show") && loginFormIsPresent) {
                        sessionModal.modal("toggle");
                    }
                },
            });
        }, 1000*60*1); //1 minute
    }

    sessionModal.on('hide.bs.modal', function (e) {
        window.location.reload();
    });
});
