$(document).ready(function() {
    $('.groupFilterForm select').change(function(){
        var id = parseInt($(this).val());
        if (isNaN(id)) {
            window.location.href = "/user/index/";
        } else {
            window.location.href = "/user/index/" + id;
        }
    });
});