$(document).ready(function () {
    const USER_ROLE_TITLE = "USER";
    const SELECT2_SELECTOR = ".select2"
    initSelect2();
    disableSomeOptions();

    function initSelect2() {
        $(SELECT2_SELECTOR).select2();
        $(SELECT2_SELECTOR).on("change", function (e) {
            let selectedRoles = $(this).select2("data");
            let roleNames = [];
            for (let role of selectedRoles) {
                roleNames.push(role.id);
            }

            let url = $(this).attr("data-url");
            $.ajax({
                url: url,
                method: "POST",
                data: {
                    roles: roleNames
                },
                success: function (result) {
                    if (result.msg !== undefined) {
                        addFlash("success", result.msg);
                    }
                },
                error: function () {
                    addFlash("error", "Error");
                }
            })
        });
    }

    function disableSomeOptions() {
        $(".select2-selection__choice").each(function (index, element) {
            let title = element.title.trim();
            if (title === USER_ROLE_TITLE) {
                $(this).children(".select2-selection__choice__remove").remove();
            }
        });
    }
});