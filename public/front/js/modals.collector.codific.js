/**
 * moves modals outside app-container because that's how the theme is supposed to work
 */
$(function () {
    collectModals();
});

function collectModals(){
    $("div.modal").each(function (index, modal) {
        let $modal = $(modal).detach();
        $("#modalsContainer").append($modal);
    });
}