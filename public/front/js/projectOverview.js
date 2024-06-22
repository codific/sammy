$(document).on('click', '.btn-overview-project', function () {
    let reloadScoreUrl = $(this).attr("data-reload-score-url");
    addOverlayBlockLoader("#overviewModal .modal-content");
    $.ajax({
        url: $(this).attr("data-url"),
        method: 'GET',
        success: function (response) {
            $("#charts-wrapper").replaceWith(response);
            $("#load-charts-data-url").attr("value", reloadScoreUrl);
        },
        complete: function () {
            loadSingleDatasetCharts();
            removeOverlayBlockLoader();
        }});
});