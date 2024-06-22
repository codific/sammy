$(document).ready(function () {

    // loads all groups and their users into the modal
    $("#open-import-modal-span").on('click', function () {
        let $this = $(this),
            url = $this.data('url');

        $("#import-users-modal-button").html(`<i class='fa fa-cog fa-spin'></i>`);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.status !== 'error') {

                    $("#import-users-from-gitlab-modal").modal('toggle');

                    // places the fetched content into the container
                    $("#gitlab-users-table-container").html(response.data);
                    return;
                }
                addFlash(response.status, response.msg);
            },
            complete: function () {
                $("#import-users-modal-button").html(`<i class='fas fa-file-import'></i>`);
            }
        });
    });

    $(document).on('click', '.ms-check-all-users-from-group', function () {
        let checkedValue = $(this).prop('checked');
        let groupId = $(this).attr('data-group');

        $(`.checkbox-microsoft-user[data-group='${groupId}']`).prop('checked', checkedValue);
    });

    // check/uncheck all users from the group
    $(document).on('click', '.check-all-users-from-group', function () {
        let $this = $(this),
            groupId = $this.data('group'),
            checkedValue = $this.prop('checked');

        // check/uncheck all child users
        $(`input[data-group-child='${groupId}']`).prop('checked', checkedValue);
    });

    // checks/unchecks the parent checkbox
    $(document).on('click', '.checkbox-gitlab-user', function () {
        let $this = $(this),
            groupId = $this.data('group-child'),
            checkedValue = $this.prop('checked');
        let allFromThatGroup = $(`input[data-group-child='${groupId}']`);
        let allWithSameCheckedStatusFromThatGroup = [];
        if (checkedValue) {
            allWithSameCheckedStatusFromThatGroup = $(`input[data-group-child='${groupId}']:checkbox:checked`);
        } else {
            allWithSameCheckedStatusFromThatGroup = $(`input[data-group-child='${groupId}']:checkbox:not(:checked)`);
        }

        if (allFromThatGroup.length === allWithSameCheckedStatusFromThatGroup.length) {
            $(`input[data-group=${groupId}]`).prop('checked', checkedValue);
        } else if (allFromThatGroup.length > allWithSameCheckedStatusFromThatGroup.length) {
            $(`input[data-group=${groupId}]`).prop('checked', false);
        }
    });

    $(document).on('click', '#import-users-from-gitlab-button', function () {
        let $this = $(this),
            $checkedInputs = $(`input.checkbox-gitlab-user:checkbox:checked`);
        if ($checkedInputs.length === 0) {
            addFlash('warning', $("#no-users-selected-translation").val(), 'div.modal-header', true, 'ml-2 mr-2');
            return;
        }

        $this.find('i').remove();
        $this.prepend(`<i class='fa fa-cog fa-spin'></i>`);

        let userIds = [];
        $checkedInputs.each(function (index, item) {
            userIds.push($(item).data('user-id'));
        });

        let url = $this.data('url');

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                'userIds': userIds
            },
            success: function (response) {
                if (response.status === 'error') {
                    addFlash(response.status, response.msg, 'div.modal-header', true, 'ml-2 mr-2');
                    $this.find('i').remove();
                    $this.prepend(`<i class="fas fa-file-import"></i>`);
                    return;
                }
                $("#import-users-from-gitlab-modal").modal('toggle');
                addFlash(response.status, response.msg);
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
                $this.find('i').remove();
                $this.prepend(`<i class="fas fa-file-import"></i>`);
            }
        });
    });

    $(document).on('click', '#import-users-from-microsoft-button', function () {
        let $this = $(this),
            $checkedInputs = $(`input.checkbox-microsoft-user:checkbox:checked`);
        if ($checkedInputs.length === 0) {
            addFlash('warning', $("#no-users-selected-translation").val(), 'div.modal-header', true, 'ml-2 mr-2');
            return;
        }

        $this.find('i').remove();
        $this.prepend(`<i class='fa fa-cog fa-spin'></i>`);

        let userIds = [];
        $checkedInputs.each(function (index, item) {
            userIds.push($(item).data('user-id'));
        });

        let url = $this.data('url');

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                'userIds': userIds
            },
            success: function (response) {
                if (response.status === 'error') {
                    addFlash(response.status, response.msg, 'div.modal-header', true, 'ml-2 mr-2');
                    $this.find('i').remove();
                    $this.prepend(`<i class="fas fa-file-import"></i>`);
                    return;
                }
                $("#import-users-from-microsoft-modal").modal('toggle');
                addFlash(response.status, response.msg);
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
                $this.find('i').remove();
                $this.prepend(`<i class="fas fa-file-import"></i>`);
            },
            error: function(data) {
                let response = data.responseJSON;
                if (response !== undefined && response.url !== undefined) {
                    window.location.href = response.url;
                }
                if (response !== undefined && response.msg !== undefined) {
                    addFlash("error", response.msg);
                }
            }
        });
    });

    $("#open-ms-import-modal-span").on('click', function () {
        let $this = $(this),
            url = $this.data('url');

        $("#ms-import-users-modal-button").html(`<i class='fa fa-cog fa-spin'></i>`);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.status !== 'error') {
                    $("#import-users-from-microsoft-modal").modal('toggle');
                    $("#microsoft-users-table-container").html(response.data);
                    return;
                }
                addFlash(response.status, response.msg);
            },
            error: function(data) {
                let response = data.responseJSON;
                if (response !== undefined && response.url !== undefined) {
                    window.location.href = response.url;
                }
                if (response !== undefined && response.msg !== undefined) {
                    addFlash("error", response.msg);
                }
            },
            complete: function () {
                $("#ms-import-users-modal-button").html(`<i class='fas fa-file-import'></i>`);
            }
        });
    });
});