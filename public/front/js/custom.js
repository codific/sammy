$(document).ready(function() {
    (function setUserTimeZoneIfNeeded() {
        let input = $("input[id='userTimezone']");
        if (input.length > 0 && $(input).attr("data-has-timezone") === "false") {
            $.ajax({
                method: "POST",
                url: $(input).attr("data-url"),
                data: {
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                }
            })
        }
    })();

    const FEEDBACK_MODAL_SELECTOR = "#feedback_modal";
    const FEEDBACK_LATER_BUTTON_SELECTOR = "#feedback-later";
    const FEEDBACK_SUBMIT_BUTTON_SELECTOR = "#feedback-submit";
    const UNVALIDATED_SCORE_TOGGLE_COOKIE_NAME = "unvalidated-score-toggle";
    const AUDIT_VIEW_TOGGLE_COOKIE_NAME = "audit-view-toggle";

    $(".change-project-form, form[name='import_users'], form[name='toolbox']").submit(function () {
        addOverlayBlockLoader();
    });

    if ($(FEEDBACK_MODAL_SELECTOR).attr("data-auto-show") === "true") {
        $(FEEDBACK_MODAL_SELECTOR).modal("toggle");
    }

    $(FEEDBACK_LATER_BUTTON_SELECTOR).click(function() {
        let form = $("form[name='feedback']");
        $("#feedback_later").val("true");
        submitFeedbackForm($(form).get(0));
    });

    $("form[name='feedback']").submit(function (e) {
        e.preventDefault();
        submitFeedbackForm($(this).get(0));
    })

    function submitFeedbackForm(form) {
        $(FEEDBACK_LATER_BUTTON_SELECTOR).attr("disabled", true);
        $(FEEDBACK_SUBMIT_BUTTON_SELECTOR).attr("disabled", true);
        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            processData: false,
            contentType: false,
            data: new FormData(form),
            success: function (msg) {
                addFlash("success", msg);
            },
            error: function (response) {
                addFlash("error", response.responseText);
            },
            complete: function() {
                $(FEEDBACK_MODAL_SELECTOR).modal("hide");
                $(FEEDBACK_LATER_BUTTON_SELECTOR).attr("disabled", false);
                $(FEEDBACK_SUBMIT_BUTTON_SELECTOR).attr("disabled", false);
            }
        });
    }

    $("[data-hidden-content-target]").click(function() {
        let target = $(this).attr("data-hidden-content-target");
        $(target).toggleClass("d-none");
    });

    $(".assignments-navbar").click(function(e) {
        e.preventDefault();
        setToggleTableCookies();
        setAssignedCookieOn();
        window.location.href = $(this).attr("href");
    });

    $(document).on("click", ".unvalidated-score-button", function () {
        let childToggle = $(this).children(".child-toggle");
        let isToggleActive = $(childToggle).hasClass("on");
        if (isToggleActive) {
            $(childToggle).removeClass("on").addClass("off");
            setCookie(UNVALIDATED_SCORE_TOGGLE_COOKIE_NAME, false, 30);
        } else {
            $(childToggle).removeClass("off").addClass("on");
            setCookie(UNVALIDATED_SCORE_TOGGLE_COOKIE_NAME, true, 30);
        }
        if (!$(this).hasClass("ajax-loaded-score")) {
            location.reload();
        }
    });

    $(".btn-overview").on('click',  function () {
        let url = $("#load-charts-data-url").val();
        if (url !== undefined) {
            loadScoresOverviewChart(url);
        }
    });

    $(document).on("click", ".ajax-loaded-score", function() {
        let url = $("#load-charts-data-url").val();
        loadScoresOverviewChart(url);
    });

    function loadScoresOverviewChart(url) {
        addOverlayBlockLoader("#overviewModal .modal-content");
        $.ajax(url, {
            method: "GET",
            success: function(data) {
                $("#charts-wrapper").replaceWith(data);
                loadSingleDatasetCharts();
                removeOverlayBlockLoader();
            },
            error: function() {
                removeOverlayBlockLoader();
            }
        })
    }

    // sets the hidden field value if either of the buttons is clicked, so that we can check in the controller
    $('.button-generate-report, .button-save-report').on('click',function() {
        let buttonName = $(this).attr('name');
        $('#clickedButtonReport').val(buttonName);
    });

    $(".audit-view-button").click(function(e) {
        let childToggle = $(this).children(".child-toggle");
        let isToggleActive = $(childToggle).hasClass("on");
        if (isToggleActive) {
            $(childToggle).removeClass("on").addClass("off");
            setCookie(AUDIT_VIEW_TOGGLE_COOKIE_NAME, false, 30);
        } else {
            $(childToggle).removeClass("off").addClass("on");
            setCookie(AUDIT_VIEW_TOGGLE_COOKIE_NAME, true, 30);
        }
        location.reload();
    });
});

function addOverlayBlockLoader(target = "body") {
    $(target).block({
        message: $("#loading-identicator-wrapper > div").clone(),
    });
}

function removeOverlayBlockLoader() {
    $(".blockUI").remove();
}

function saveOrOpenBlob(url, blobName, type = "application/pdf", method = "GET", params = null) {
    var blob;
    var xmlHTTP = new XMLHttpRequest();
    xmlHTTP.open(method, url, true);

    xmlHTTP.responseType = 'arraybuffer';
    addOverlayBlockLoader();
    xmlHTTP.onload = function(e) {
        blob = new Blob([this.response], {type: type});
    };
    xmlHTTP.onprogress = function(pr) {
        //pr.loaded - current state
        //pr.total  - max
    };
    xmlHTTP.onloadend = function(e) {
        removeOverlayBlockLoader();

        if (this.status === 200) {
            var fileName = blobName;
            var tempEl = document.createElement("a");
            document.body.appendChild(tempEl);
            tempEl.style = "display: none";
            url = window.URL.createObjectURL(blob);
            tempEl.href = url;
            tempEl.download = fileName;
            tempEl.click();
            window.URL.revokeObjectURL(url);
        } else if (this.status === 201) {
            addFlash("success", "Changes successfully saved.");
        }
        else {
            addFlash("error", "Something went wrong, please try again.")
        }
    }
    xmlHTTP.send(params);
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

function setAssignedCookieOn() {
    setCookie('assigned', true, 30);
}

function setToggleTableCookies() {
    let streamTables = ['streamsWithoutAnAnswer', 'streamsWithoutVerification', 'streamsInOrForImprovement'];
    for (let streamTable of streamTables) {
        setCookie(streamTable, true, 30);
    }
    setCookie('completedStreams', false, 30);
}
