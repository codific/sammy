$(document).ready(() => {
    let validationText = $('#validation_remarks');
    let isSafari = /^((?!chrome|android|crios|fxios).)*safari/i.test(navigator.userAgent);
    let shouldTriggerOnBlur = false;

    $('#validation_ACCEPTED, #validation_REJECTED, #validation_SAVE').on('click', function(event) {
        shouldTriggerOnBlur = false;
        event.stopPropagation();
    });

    $(document).on('click', function(event) {
        shouldTriggerOnBlur = !(event.target.id === 'validation_ACCEPTED' || event.target.id === 'validation_REJECTED' || event.target.id === 'validation_SAVE');

        if (isSafari) {
            if (!$('#formParent').is(event.target) && !$('#formParent').has(event.target).length) {
                if (shouldTriggerOnBlur) {
                    saveValidationRemark();
                    shouldTriggerOnBlur = false;
                }
            }
        }
    });

    validationText.on('blur', function (){
        if (shouldTriggerOnBlur) {
            saveValidationRemark();
            shouldTriggerOnBlur = false;
        }
    });

    setInterval(function (){
        saveValidationRemark();
    }, 30000);

    function saveValidationRemark()
    {
        let url = $('form[name="validation"]').attr('data-autosave-url');

        $.ajax({
            type: 'POST',
            url: url,
            data: { 'remarks': validationText.val().trim() },
            dataType: 'json',
            timeout: 10000,
            success: function (responseData, responseStatus) {
                if (responseStatus === 'success') {
                    $('.changes-saved').fadeIn(500, function() {
                        $('.changes-saved').delay(1500).fadeOut(500);
                    });
                }
            }
        });
    }
});
