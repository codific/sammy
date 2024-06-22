$(document).ready(function () {
    $(document).on("change", ".checkbox-all", function() {
        $(this).closest('.form-group').find(`input[type=checkbox]`).prop('checked', $(this).prop('checked'));
    });

    $(document).on("click", "input[type=checkbox]", function() {
        if (!$(this).prop("checked")) {
            $(this).closest('.form-group').find(`.checkbox-all`).prop("checked", false);
        }
    });
});