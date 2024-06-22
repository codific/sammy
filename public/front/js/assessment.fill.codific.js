$(() => {
    window.loadSingleDatasetCharts?.();
    const ACTIVE_BUTTON_CLASSES = "btn-primary text-white";
    const INACTIVE_BUTTON_CLASSES = "btn-light";
    let maxMaturityLevels = $("#max-maturity-levels").val();
    const MAX_MATURITY_LEVEL = parseInt(maxMaturityLevels);
    const DOCUMENTATION_HIDDEN_REMARK_ID_SELECTOR = "#documentation_remarkId";
    const DOCUMENTATION_HIDDEN_REMARK_TYPE_SELECTOR = "#documentation_remarkType";
    const DOCUMENTATION_ATTACHMENT_TITLE_SELECTOR = "#documentation_attachmentTitle";
    const DOCUMENTATION_ATTACHMENT_FILE_SELECTOR = "#documentation_attachmentFile";
    const DOCUMENTATION_FORM_SELECTOR = ".ajax-documentation-form";
    const ROW_IN_EDIT_CLASSES = "bg-info";
    const VALIDATION_REMARKS_ROW_SELECTOR = ".validation-remarks-row";
    const VALIDATION_REMARKS_SELECTOR = "#validation-remarks-value";
    const REMARK_MATURITY_LEVEL_COL_ELEMENT_CLASSES = "remark-level";
    const FORM_MATURITY_LEVEL_CHECKBOX_SELECTOR = "input[type='checkbox'][id^='documentation_maturityLevel']";
    const DOCUMENTATION_CONTAINER_CLASS = "documentation-container";
    const DOCUMENTATION_CONTAINER_SELECTOR = "." + DOCUMENTATION_CONTAINER_CLASS;
    const DOCUMENTATION_TAB_BUTTON_CLASS = "documentation-tab-button";
    const TIMELINE_TAB_BUTTON_CLASS = "timeline-tab-button";
    const DOCUMENTATION_TABS_SELECTOR = ".documentation-tabs";
    let ajaxTimeout = 10000; // 10 sec
    let multipleChoice = $('button.multiple-choice');
    let checkboxChoice = $("input[class^='checkbox-choice']");
    let btnNext = $('.btn-next');

    let yellowDotWithModal = $('.reference-modal-pill');

    // Multiple choice events
    multipleChoice.on('click', async function (e) {
        if ($(e.target).hasClass('event-target') === false) {
            e.target.click();
        } else if (!$(this).is(':disabled')) {
            await saveMultipleChoice($(this));
            ($(this));
        }
        return false;
    });
    // Checkbox choice events
    checkboxChoice.on('click', function (e) {
        if (!$(this).is(':disabled')) {
            saveCheckboxChoice($(this));
        }
        e.stopPropagation();
    });

    // Yellow dot modal events
    yellowDotWithModal.click(function (e) {
        $($(this).data('target')).modal('show');
        e.stopPropagation();
    });

    let enableSubmitIfNeeded = function (button) {
        let streamId = $(button).attr("data-stream-id");
        let answered = true;
        for (let i = 1; i <= MAX_MATURITY_LEVEL; i++) {
            if (!$(`.multiple-choice[data-stream-id=${streamId}][data-maturity-level=${i}]`).hasClass(ACTIVE_BUTTON_CLASSES)) {
                answered = false;
                break;
            }
        }
        if (answered === true) {
            $(`.submitStreamBtn`).prop("disabled", false);
            $(`.nav-item > a.active.streamHeader > h4 > span > i`).removeClass("practice-icon-incomplete");
            enableAuditSubmit();
        }
    }

    let enableAuditSubmit = function () {
        $(`.audit-accept`).prop("disabled", false);
    }

    let saveMultipleChoice = async function (button) {
        let url = button.data('url');
        let answer = button.data('answer');
        let answerValue = button.data('answer-value');
        let currentCheckboxClassName = $(".checkbox-choice-" + button.data('question'));


        let question = button.data('question');
        $.ajax({
            type: 'POST',
            url: url,
            data: {answerId: answer, questionId: question},
            dataType: 'json',
            timeout: ajaxTimeout,
            success: function (data) {
                setActiveMultipleChoiceAnswer(button, question);
                enableSubmitIfNeeded($(button));
                moveProgressBar(data.progress);
                if (answerValue <= 0) {
                    currentCheckboxClassName.prop('checked', false);
                    currentCheckboxClassName.attr("disabled", false);
                }else{
                    currentCheckboxClassName.prop("checked", true);
                    currentCheckboxClassName.prop("disabled", true);
                }
                saveCheckboxChoice(currentCheckboxClassName);

            }
        });
    }

    let saveCheckboxChoice = function (clickedCheckbox) {
        let url = clickedCheckbox.data('url');
        if (url == null) return;

        let checkboxesForQuestion = $(".qualities-checkboxes-list > .quality-" + clickedCheckbox.data('question') + " input");

        let checkboxesData = [];
        for (let i = 0; i < checkboxesForQuestion.length; i++) {
            let key = i;
            let isChecked = checkboxesForQuestion[i].checked;
            let questionId = checkboxesForQuestion[i].dataset.question;

            checkboxesData.push({key: key, isChecked: isChecked, questionId: questionId});
        }

        $.ajax({
            type: 'POST',
            url: url,
            data: {checkboxesData: JSON.stringify(checkboxesData)},
            dataType: 'json',
            timeout: ajaxTimeout
        });
    }


    let setActiveMultipleChoiceAnswer = function (button, questionId) {
        $('div#question-' + questionId + ' > button').removeClass(ACTIVE_BUTTON_CLASSES).addClass(INACTIVE_BUTTON_CLASSES);
        if ($(button).hasClass(INACTIVE_BUTTON_CLASSES)) {
            $(button).removeClass(INACTIVE_BUTTON_CLASSES).addClass(ACTIVE_BUTTON_CLASSES);
        }
    }

    let moveProgressBar = async function (progress) {
        let progressBarSelector = $(".progress-bar");
        progressBarSelector.css("width", `${progress}%`).attr("aria-valuenow", progress);
        progressBarSelector.find("#progressPct").text(Math.round(progress));
    }

    $(document).on("submit", DOCUMENTATION_FORM_SELECTOR, function (e) {
        e.preventDefault();
        let thereIsRemarkUnderEdit = $(DOCUMENTATION_HIDDEN_REMARK_ID_SELECTOR).val() !== null && $(DOCUMENTATION_HIDDEN_REMARK_ID_SELECTOR).val() !== "";
        if ($("#documentation_text").val() === "" && $(DOCUMENTATION_ATTACHMENT_FILE_SELECTOR).val() === "" && !thereIsRemarkUnderEdit) {
            return;
        }
        addOverlayBlockLoader();
        let $form = $(this);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            processData: false,
            contentType: false,
            data: new FormData(this),
            success: function (result) {
                addFlash("success", result.msg);
                removeOverlayBlockLoader();
                $(".documentation-tab").html(result.data);
                reAttachDeleteRemarkModal();
            },
            error: function (response) {
                ajaxErrorCallback(response.responseJSON);
            }
        });
    });

    function reAttachDeleteRemarkModal()
    {
        $modal = $(".remark-delete-modal").detach();
        $("#modalsContainer").append($modal);
    }

    $(document).on("click", ".delete-remark", function () {
        let url = $(this).attr("data-url");
        let attachmentId = $(this).attr("data-remark-id");
        let remarkType = $(this).attr("data-remark-type");
        let row = $(`.remark-row[data-remark-id='${attachmentId}'][data-remark-type=${remarkType}]`);
        $.ajax({
            url: url,
            type: 'DELETE',
            success: function (msg) {
                addFlash("success", msg);
                removeOverlayBlockLoader();
                $(row).remove();
            },
            error: function (response) {
                ajaxErrorCallback(response.responseJSON);
            },
            complete: function () {
                $(".modal[id^=delete-]").modal("hide");
            }
        });
    });

    $(`.ajax-form`).on('submit', function (e) {
        let hasNoReloadClass = $(this).hasClass("ajax-form-no-reload");
        let shouldReloadPage = !hasNoReloadClass;
        let nextStepAttribute = $(this).attr("data-next-step");
        let alternativeNextStepAttribute = $(this).attr("data-alternative-next-step");
        let submitUrl = $(this).attr("action");

        let buttonClicked = e.originalEvent.submitter;
        if ($(buttonClicked).attr("data-prevent-reload") === "true") {
            shouldReloadPage = false;
        }
        if ($(buttonClicked).attr("data-submit-url") !== undefined) {
            submitUrl = $(buttonClicked).attr("data-submit-url");
        }

        let isEditValidationForm = $(this).hasClass("edit-validation-form");
        let isSubmitEvaluationForm = $(this).hasClass("submit-evaluation-form");

        e.preventDefault();
        addOverlayBlockLoader();
        $.ajax({
            url: submitUrl,
            type: 'POST',
            processData: false,
            contentType: false,
            data: new FormData(this),
            success: function (data) {
                if (shouldReloadPage) {
                    if (nextStepAttribute !== undefined && nextStepAttribute !== "") {
                        window.location.hash = nextStepAttribute;
                    }
                    if (isSubmitEvaluationForm && data.validationStatus === parseFloat($("#autoValidationStatus").val()) && alternativeNextStepAttribute !== undefined && alternativeNextStepAttribute !== "") {
                        window.location.hash = alternativeNextStepAttribute;
                    }
                    window.location.reload();
                } else {
                    addFlash("success", data.msg);
                    removeOverlayBlockLoader();
                    if (isEditValidationForm) {
                        if (data.value !== "" && data.value !== null && data.value !== undefined) {
                            $(VALIDATION_REMARKS_SELECTOR).closest(VALIDATION_REMARKS_ROW_SELECTOR).removeClass("d-none");
                        } else {
                            $(VALIDATION_REMARKS_SELECTOR).closest(VALIDATION_REMARKS_ROW_SELECTOR).addClass("d-none");
                        }
                        $(VALIDATION_REMARKS_SELECTOR).html(data.value);
                    }
                }
            },
            error: function (response) {
                ajaxErrorCallback(response.responseJSON);
            }
        });
    });

    $("#finish-improve, #archive-stream, #reactivate-stream").click(function () {
        let url = $(this).attr("data-url");
        let nextStep = $(this).attr("data-next-step");
        addOverlayBlockLoader();
        $.ajax({
            url: url,
            type: 'POST',
            success: function () {
                if (nextStep !== undefined && nextStep !== "") {
                    window.location.hash = nextStep;
                }
                window.location.reload();
            },
            error: function (response) {
                ajaxErrorCallback(response.responseText);
            }
        });
    });

    function ajaxErrorCallback(msg) {
        removeOverlayBlockLoader();
        addFlash('error', msg, 'div.subheader', true);
    }

    // Next context
    btnNext.on('click', function () {
        location.href = btnNext.data('url');
    });

    $("form[class='form-overlay-blocker']").submit(function () {
        addOverlayBlockLoader();
    });

    function addOverlayBlockLoader() {
        $("body").block({
            message: $("#loading-identicator-wrapper > div").clone(),
        });
    }

    function removeOverlayBlockLoader() {
        $(".blockUI").remove();
    }

    let toggleVisibility = function (object) {
        if (object.hasClass("d-none")) {
            object.removeClass("d-none");
            object.addClass("show");
        } else {
            object.removeClass("show");
            object.addClass("d-none");
        }
    }

    $(".btn-edit-validation").click(function () {
        let currentRemarks = $(VALIDATION_REMARKS_SELECTOR).text().trim();

        $("#edit_validation_remarks").val(currentRemarks);

        $(".edit-validation-form").toggleClass("d-none").find();

        if ($(this).text() === $(this).attr("data-hide-text")) {
            $(this).text($(this).attr("data-show-text"));
            $(this).addClass('btn-primary').removeClass('btn-danger')
        } else {
            $(this).text($(this).attr("data-hide-text"));
            $(this).addClass('btn-danger').removeClass('btn-primary')
        }
    });

    $(document).on("click", ".btn-edit-remark", function () {
        let row = $(this).closest("tr");

        clearEditedRemarkFields();
        $(DOCUMENTATION_ATTACHMENT_TITLE_SELECTOR).val($(row).find(".attachment-title").text().trim());
        let text = $(row).find(".remark-text").text().trim();
        $('#documentation_text').val(text);

        if ($(row).attr("data-remark-type") === $("#remark-type-validation").attr("value")) {
            disableFieldsNotNeededForValidationRemark();
        } else {
            enableFieldsNotNeededForValidationRemark();
        }

        $(row).find(`.${REMARK_MATURITY_LEVEL_COL_ELEMENT_CLASSES}`).each(function () {
            let levelValue = $(this).attr("data-level-value");
            $(`${FORM_MATURITY_LEVEL_CHECKBOX_SELECTOR}[value=${levelValue}]`).attr("checked", "checked");
        });

        setHiddenRemarkEditFormFields($(row).attr("data-remark-id"), $(row).attr("data-remark-type"));
        $("tr").removeClass(ROW_IN_EDIT_CLASSES);
        $(row).addClass(ROW_IN_EDIT_CLASSES);
    });

    $(document).click(function (e) {
        let clickedIsOutsideTheTargetZone = $(DOCUMENTATION_HIDDEN_REMARK_ID_SELECTOR).val() !== null &&
            $(DOCUMENTATION_HIDDEN_REMARK_ID_SELECTOR).val() !== "" &&
            $(e.target).parents(DOCUMENTATION_FORM_SELECTOR).length === 0 &&
            !$(e.target).hasClass("btn-edit-remark") &&
            $(e.target).parents(".btn-edit-remark").length === 0;

        if (clickedIsOutsideTheTargetZone && isDocumentationLoaded()) {
            clearEditedRemarkFields();
            $('#documentation_text').val('');
            enableFieldsNotNeededForValidationRemark();
            $("tr").removeClass(ROW_IN_EDIT_CLASSES);
        }
    });

    $(document).on("click", `.${DOCUMENTATION_TAB_BUTTON_CLASS}, .${TIMELINE_TAB_BUTTON_CLASS}`,function (e) {
        if (!isDocumentationLoaded()) {
            let clickedTab = $(this).hasClass(DOCUMENTATION_TAB_BUTTON_CLASS) ? `.${DOCUMENTATION_TAB_BUTTON_CLASS}` : `.${TIMELINE_TAB_BUTTON_CLASS}`
            addOverlayBlockLoader();
            let url = $(this).attr("data-url");
            $.ajax(url, {
                method: "GET",
                success: function (data) {
                    $(DOCUMENTATION_CONTAINER_SELECTOR).replaceWith(data);
                    reloadModalTooltips();
                    $(clickedTab).trigger("click");
                    reAttachDeleteRemarkModal();
                },
                complete: function () {
                    removeOverlayBlockLoader();
                }
            });
        } else {
            $(DOCUMENTATION_TABS_SELECTOR).removeClass("d-none");
        }
    });

    $(document).on("click", ".documentation-show-hide", function() {
        $(DOCUMENTATION_TABS_SELECTOR).toggleClass("d-none");
    });

    function isDocumentationLoaded() {
        return $(".documentation-tab").length > 0;
    }

    function reloadModalTooltips() {
        $(`${DOCUMENTATION_CONTAINER_SELECTOR} [data-toggle="tooltip"]`).tooltip();
    }

    $(document).on("click", ".documentation-modal-popup", function () {
        window.open($("#documentation-page-url").val(), "newWindow", "width=1000,height=650");
    });

    function clearEditedRemarkFields() {
        $(FORM_MATURITY_LEVEL_CHECKBOX_SELECTOR).removeAttr("checked");
        $(DOCUMENTATION_FORM_SELECTOR).trigger("reset");
        $(DOCUMENTATION_HIDDEN_REMARK_ID_SELECTOR).val(null);
        $(DOCUMENTATION_HIDDEN_REMARK_TYPE_SELECTOR).val(null);
        $('.custom-file-label').prop('textContent', null);
    }

    function setHiddenRemarkEditFormFields(remarkId, remarkType) {
        $(DOCUMENTATION_HIDDEN_REMARK_ID_SELECTOR).val(remarkId);
        $(DOCUMENTATION_HIDDEN_REMARK_TYPE_SELECTOR).val(remarkType);
    }

    function disableFieldsNotNeededForValidationRemark() {
        $(FORM_MATURITY_LEVEL_CHECKBOX_SELECTOR).attr("disabled", "disabled");
        $(DOCUMENTATION_ATTACHMENT_TITLE_SELECTOR).attr("disabled", "disabled");
        $(DOCUMENTATION_ATTACHMENT_FILE_SELECTOR).attr("disabled", "disabled");
    }

    function enableFieldsNotNeededForValidationRemark() {
        $(FORM_MATURITY_LEVEL_CHECKBOX_SELECTOR).removeAttr("disabled");
        $(DOCUMENTATION_ATTACHMENT_TITLE_SELECTOR).removeAttr("disabled");
        $(DOCUMENTATION_ATTACHMENT_FILE_SELECTOR).removeAttr("disabled");
    }

    function setCookie(cName, cValue, expDays) {
        let date = new Date();
        date.setTime(date.getTime() + (expDays * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = cName + "=" + cValue + "; " + expires + "; path=/";
    }

    function getCookie(cName) {
        const name = cName + "=";
        const cDecoded = decodeURIComponent(document.cookie); //to be careful
        const cArr = cDecoded.split('; ');
        let res;
        cArr.forEach(val => {
            if (val.indexOf(name) === 0) res = val.substring(name.length);
        })
        return res;
    }

    $("form[name='retract_submission'], form[name='validation'], form[name='evaluation-form']").submit(function () {
        addOverlayBlockLoader();
    });
});
