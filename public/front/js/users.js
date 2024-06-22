$(document).ready(function () {
    let searchUsersInput = $("#search-users-input");

    searchUsersInput.on('input', function (e) {
        if (!$(this).is(':disabled')) {
            searchUsers(this);
        }
        e.stopPropagation();
    });

    let searchUsers = function (input) {
        let searchTerm = $(input).val().toLowerCase();
        let url = $(input).data('url');
        let value = input.value.toLowerCase();

        if (value.length === 0 || value.trim().length >= 2) {
            $.ajax({
                type: 'GET',
                data: {"searchTerm": searchTerm},
                url: url,
                timeout: 10000, // 10 sec
                success: function (data) {
                    $('.table-paginator-container').html(data);
                    collectModals();
                }
            });
        }
    };
});

