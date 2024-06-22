$(document).ready(() => {
    let submitFinalizeImprovement = $(".finalize-submit-improvement");
    let improvementText = $(".improvement-plan-area");

    improvementText.on('change', function (e) {
        if (!$(this).is(':disabled')) {
            checkImprovementPlanArea();
        }
    });

    let checkImprovementPlanArea = function () {
        if ((improvementText.val().trim()).length < 1) {
            submitFinalizeImprovement.attr('disabled', 'disabled');
        } else {
            submitFinalizeImprovement.removeAttr('disabled');
        }
    };

    // checks on load if the improvement plan area is empty or not
    checkImprovementPlanArea();
});