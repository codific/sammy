$(() => {
    $(".activity-extra-info-modal-toggle").on('click', function(e) {
        e.preventDefault();
        var tab = e.currentTarget.attributes['data-modal-tab'].value;
        $("a[data-toggle='tab'][href='#"+tab+"']").trigger('click')
    })
});
