$(document).ready(function() {
    loadMultiDatasetCharts();

    const REPORTING_TOGGLE_COOKIE_NAME = "reporting-toggle";
    const CHART_WRAPPER_SELECTOR = ".chart-wrapper-col";

    $(".fullscreen-reporting-button").click(function(e) {
        let childToggle = $(this).children(".child-toggle");
        let isToggleActive = $(childToggle).hasClass("on");
        if (isToggleActive) {
            $(childToggle).removeClass("on").addClass("off");
            setCookie(REPORTING_TOGGLE_COOKIE_NAME, false, 30);
            $(CHART_WRAPPER_SELECTOR).each(function() {
                if ($(this).find("[class^=chart-radar]").length > 0) {
                    $(this).removeClass("col-sm-8").addClass("col-sm-3");
                } else {
                    $(this).removeClass("col-sm-12").addClass("col-sm-4");
                }
            });
        } else {
            $(childToggle).removeClass("off").addClass("on");
            setCookie(REPORTING_TOGGLE_COOKIE_NAME, true, 30);
            $(CHART_WRAPPER_SELECTOR).each(function() {
                if ($(this).find("[class^=chart-radar]").length > 0) {
                    $(this).removeClass("col-sm-3").addClass("col-sm-8");
                } else {
                    $(this).removeClass("col-sm-4").addClass("col-sm-12");
                }
            });
        }
    });

    $(document).on("submit", ".ajax-generate-report-form", function (e) {
        e.preventDefault();
        $('#generate-imp-report-modal').modal('hide');
        let reportName = "Assessment Report" + " " + $("#current-project-name").val();
        saveOrOpenBlob($(this).attr("action"), reportName, "application/pdf", "POST", new FormData(this));
    });
});