$(document).ready(function() {
    const WAIT_FOR_USER_TO_STOP_TYPING_MILLISECONDS = 750;
    let timer = null;
    let searchUnitsInput = $("#search-units-input");

    searchUnitsInput.on('input', function (e) {
        if (!$(this).is(':disabled')) {
            let input = $(this);
            let url = $(input).data("url");
            let value = $(input).val().toLowerCase();
            let archived = $(input).data('archived');
            clearTimeout(timer);
            timer = setTimeout(() => {
                searchUnits(value, url, archived)
            }, WAIT_FOR_USER_TO_STOP_TYPING_MILLISECONDS);
        }
        e.stopPropagation();
    });

    let searchUnits = function (value, url, archived) {
        if (value.length === 0 || value.trim().length >= 2) {
            $.ajax({
                type: 'GET',
                data: {"searchTerm": value, "archived": archived},
                url: url,
                timeout: 10000,
                success: function (data) {
                    $('.table-paginator-container').html(data);
                    collectModals();
                }
            });
        }
    };

    $("form[name='unit']").on("submit", function() {
        addOverlayBlockLoader();
    });
});

