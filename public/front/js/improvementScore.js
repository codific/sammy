$(document).ready(() => {

    $('button.desired-choice.desired-chosen').each(function () {
        setQuestionDelta($(this));
    });
    setTotalDelta();
    setTotalScore();

    let desiredChoice = $('button.desired-choice');

    $(`#improveStreamBtn`).on('click', function () {
        let currentDesiredChoices = $('button.desired-choice.desired-chosen');
        currentDesiredChoices.click();
    });

    desiredChoice.on('click', function (e) {
        if ($(e.target).hasClass('event-target')) {
            setQuestionDelta($(this));
            setTotalDelta();
            setTotalScore();
        }
    });

    function setQuestionDelta(choice) {
        let improvementScore = $(choice).data('answer-value');
        let currentScore = $(choice).parent().find('.btn-primary').data('answer-value');
        let delta = improvementScore - currentScore;

        let deltaSpan = $(`#delta-`+choice.data('question'));
        deltaSpan.data('delta', delta)
        if (delta < 0){
            deltaSpan.removeClass('text-success');
            deltaSpan.addClass('text-danger');
            deltaSpan.text(delta.toFixed(2));
        }else if (delta > 0) {
            deltaSpan.removeClass('text-danger');
            deltaSpan.addClass('text-success');
            deltaSpan.text('+ ' + delta.toFixed(2));
        }else {
            deltaSpan.html('<br>');
        }

    }

    function setTotalDelta() {
        let totalDelta = 0;
        let deltaSpans = $('.deltaSpan');
        deltaSpans.each(function (key, deltaSpan){
            let maturityLevel = $(this).attr("data-maturity-level");
            let shouldAverageOnMaturityLevel = maturityLevel !== "";
            if($(deltaSpan).data('delta')){
                if (shouldAverageOnMaturityLevel) {
                    totalDelta += $(deltaSpan).data('delta') / $(`.deltaSpan[data-maturity-level=${maturityLevel}]`).length;
                } else {
                    totalDelta += $(deltaSpan).data('delta')
                }
            }
        });
        let totalDeltaSpan = $(`#improvementDelta`);
        let totalImprovementScoreSpan = $(`#improvementScore`)
        totalDeltaSpan.data('delta', totalDelta)
        if (totalDelta < 0){
            totalDeltaSpan.removeClass('text-success');
            totalDeltaSpan.addClass('text-danger');

            totalImprovementScoreSpan.removeClass('text-success');
            totalImprovementScoreSpan.addClass('text-danger');

            totalDeltaSpan.text('- ' + Math.abs(totalDelta).toFixed(2));
            $(`#currentAndImprovementScores`).removeClass('d-none');
        } else if (totalDelta > 0) {
            totalDeltaSpan.removeClass('text-danger');
            totalDeltaSpan.addClass('text-success');

            totalImprovementScoreSpan.removeClass('text-danger');
            totalImprovementScoreSpan.addClass('text-success');

            totalDeltaSpan.text('+' + totalDelta.toFixed(2));
            $(`#currentAndImprovementScores`).removeClass('d-none');
        } else {
            $(`#currentAndImprovementScores`).addClass('d-none');
        }
    }

    function setTotalScore() {
        let score = $(`#currentScore`).data('score') + $(`#improvementDelta`).data('delta');
        $(`#improvementScore`).text(score.toFixed(2));
        $(`#improvementScore`).data('score', score);
    }
});