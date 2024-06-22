$(document).ready(() => {
    let auditText = $('#audit_remarks');
    auditText.on('blur', function () {
        saveEvaluationRemark();
    });

    $(window).on('beforeunload', function () {
        localStorage.setItem('evaluationRemarkData', auditText.val().trim());
    });

    function saveEvaluationRemark(localStorageRemark) {
        let url = $('form[name="audit"]').attr('data-autosave-url');

        $.ajax({
            type: 'POST',
            url: url,
            data: {'remarks': (localStorageRemark !== undefined) ? localStorageRemark : auditText.val().trim()},
            dataType: 'json',
            timeout: 10000,
            success: function (responseData, responseStatus) {
                if (responseStatus === 'success') {
                    $('.changes-saved').fadeIn(500, function () {
                        $('.changes-saved').delay(1500).fadeOut(500);
                    });
                }
            }
        });
    }
});
