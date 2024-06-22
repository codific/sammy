$(() => {
    window.loadSingleDatasetCharts?.();
    const NEW_DESIRED_ANSWERS_FORM_BOX_SELECTOR = "#improvement_newDesiredAnswers";
    let ajaxTimeout = 10000; // 10 sec
    let desiredChoice = $('button.desired-choice');

    // Desired choice events
    desiredChoice.on('click', function (e) {
        if ($(e.target).hasClass('event-target') === false) {
            e.target.click();
        } else if (!$(this).is(':disabled')) {
            saveDesiredChoice($(this));
            ($(this));
        }
        return false;
    });

    let saveDesiredChoice = function (button) {
        let answer = button.data('answer');
        let question = button.data('question');
        let savedDesiredAnswers = JSON.parse($(NEW_DESIRED_ANSWERS_FORM_BOX_SELECTOR).val());
        savedDesiredAnswers[question] = answer;
        $(NEW_DESIRED_ANSWERS_FORM_BOX_SELECTOR).val(JSON.stringify(savedDesiredAnswers));
        setActiveDesiredBadge(button, question);
    }

    let setActiveDesiredBadge = function (button, questionId) {
        $('div#question-' + questionId).find('span.badge-success').addClass('d-none');
        $(button).find('span.badge-success').removeClass('d-none');
    }


    $(".btn-edit-improvement").click(function () {
        toggleVisibility($("#collapseImprovementForm"));
        toggleVisibility($("#cancel-submit-wrapper"));
        toggleVisibility($(".improvement-data"));
        if ($(this).val() === $(this).attr("data-hide-text")) {
            $(this).val($(this).attr("data-show-text"));
        } else {
            $(this).val($(this).attr("data-hide-text"));
        }
    });

    $(`#improveStreamBtn`).on('click', function () {
        let target = $(this).attr("href");
        $(target).removeClass("d-none");
    });


    let toggleVisibility = function (object) {
        if (object.hasClass("d-none")) {
            object.removeClass("d-none");
            object.addClass("show");
        } else {
            object.removeClass("show");
            object.addClass("d-none");
        }
    }
});
