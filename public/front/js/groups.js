$(document).ready(function () {
    const WAIT_FOR_USER_TO_STOP_TYPING_MILLISECONDS = 750;
    let timer = null;
    let searchGroupsInput = $("#search-groups-input");

    searchGroupsInput.on('input', function (e) {
        if (!$(this).is(':disabled')) {
            let input = $(this);
            let url = $(input).data("url");
            let value = $(input).val().toLowerCase();
            clearTimeout(timer);
            timer = setTimeout(() => {
                searchGroups(value, url)
            }, WAIT_FOR_USER_TO_STOP_TYPING_MILLISECONDS);

        }
        e.stopPropagation();
    });

    let searchGroups = function (value, url) {
        if (value.length === 0 || value.trim().length >= 2) {
            $.ajax({
                type: 'GET',
                data: {"searchTerm": value},
                url: url,
                timeout: 10000,
                success: function (data) {
                    $('.table-paginator-container').html(data);
                    collectModals();
                    reloadSelect2Editable();
                    reloadEditable();
                }
            });
        }
    };

    $(document).on("click", ".btn-show-subgroup", function() {
        let groupId = $(this).attr("data-group");
        let childRows =  $(`tr[data-parent-id=${groupId}]`);
        $(childRows).removeClass("d-none");
        $(`.btn-show-subgroup[data-group=${groupId}]`).addClass("d-none");
        $(`.btn-hide-subgroup[data-group=${groupId}]`).removeClass("d-none");
    });

    $(document).on("click", ".btn-hide-subgroup", function() {
        let groupId = $(this).attr("data-group");
        let childRows =  $(`tr[data-parent-id=${groupId}]`);
        $(childRows).addClass("d-none");
        $(`.btn-show-subgroup[data-group=${groupId}]`).removeClass("d-none");
        $(`.btn-hide-subgroup[data-group=${groupId}]`).addClass("d-none");
        let childHideButton = $(`.btn-hide-subgroup[data-parent-id=${groupId}]`);
        if ($(childHideButton).length > 0) {
            $(childHideButton).trigger("click");
        }
    });
});