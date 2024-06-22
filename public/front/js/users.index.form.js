$(document).ready(function() {
    $('.groupFilterForm select').change(function(){
        window.location.href = $(this).closest("form").attr('action')+'/'+$(this).val();
    });
});