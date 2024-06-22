$(document).ready(function () {
    let streamTables = $(`.hide-table-button[data-table-name]`);
    let streamTableNames = streamTables.map(function () {
        return $(this).data('table-name');
    }).get();
    let streamTableCookies = [];
    streamTableNames.forEach(function (element) {
        streamTableCookies[element] = (getCookie(element) != null) ? (getCookie(element) === 'true') : true
    })

    $(".button-view-all").click(function () {
        let table = $(this).attr("data-table");
        let areRowsCurrentlyHidden = $(this).attr("data-rows-hidden") === "true";
        let assessmentId = $(this).attr("data-assessment-id");
        let buttonText = $(`.button-view-all-text[data-assessment-id=${assessmentId}][data-table=${table}]`);
        let buttonIcon = $(`.button-view-all-icon[data-assessment-id=${assessmentId}][data-table=${table}]`);
        if (areRowsCurrentlyHidden) {
            getAdditionalTableRows(assessmentId, table).prop("hidden", false);
            $(this).attr("data-rows-hidden", "false");
            $(buttonText).text($(buttonText).attr("data-hide-text"));
            $(buttonIcon).removeClass($(buttonIcon).attr("data-show-icon")).addClass($(buttonIcon).attr("data-hide-icon"));
        } else {
            getAdditionalTableRows(assessmentId, table).prop("hidden", true);
            $(this).attr("data-rows-hidden", "true");
            $(buttonText).text($(buttonText).attr("data-show-text"));
            $(buttonIcon).removeClass($(buttonIcon).attr("data-hide-icon")).addClass($(buttonIcon).attr("data-show-icon"));
        }
    });

    function getAdditionalTableRows(assessmentId, table) {
        return $(`tr[data-assessment-id=${assessmentId}][data-table=${table}]`).filter((index, element) => {
            return parseInt($(element).attr("data-row-index")) > parseInt($(element).attr("data-hidden-row-min-index"));
        });
    }

    $(".hide-table-button").click(function (e) {
        let assessmentId = $(this).attr("data-assessment-id");
        let tableName = $(this).attr("data-table-name");
        let childToggle = $(this).children(".child-toggle");
        let isToggleActive = $(childToggle).hasClass("on");
        if (isToggleActive) {
            $(childToggle).removeClass("on").addClass("off");
            setCookie(tableName, false, 30);
            $(`.row[data-assessment-id=${assessmentId}][data-table-name=${tableName}]`).prop("hidden", true);
        } else {
            $(childToggle).removeClass("off").addClass("on");
            setCookie(tableName, true, 30);
            $(`.row[data-assessment-id=${assessmentId}][data-table-name=${tableName}]`).prop("hidden", false);
        }
    });

    $(".show-assigned-button").click(function (e) {
        let assessmentId = $(this).attr("data-assessment-id");
        let childToggle = $(this).children(".child-toggle");
        let isToggleActive = $(childToggle).hasClass("on");
        if (isToggleActive) {
            $(childToggle).removeClass("on").addClass("off");
            setCookie('assigned', false, 30);
        } else {
            $(childToggle).removeClass("off").addClass("on");
            setCookie('assigned', true, 30);
        }
        window.location.reload();
    });
});

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