const IMPROVEMENT_SAVE_DRAFT_BUTTON_ID = "improvement_SAVE";

function processForm(e) {
    if( ! acceptBtn.data('accept') && $(e.submitter).attr("id") !== IMPROVEMENT_SAVE_DRAFT_BUTTON_ID) {
        let submitter = e.submitter;
        acceptBtn.attr('data-target', submitter.id);
        acceptBtn.data('data-target', submitter.id);
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        $("#popupModal").modal();
    }
}

let acceptBtn = $("#hiddenAcceptBtn")

var forms = $(".button-js-submit-popup-form");

for(let form of forms) {
    form.addEventListener("submit", processForm);
}


acceptBtn.on("click", function() {
    acceptBtn.attr('data-accept', true);
    acceptBtn.data('accept', true);
    let targetId = acceptBtn.data('target');
    $("#"+targetId).click();
});

$('#assignedTo.clickable.editable-click').on('click', function(event) {
    event.preventDefault();
});