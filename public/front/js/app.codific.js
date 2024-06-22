function reloadSelect2Editable() {
    let select2EditableSelector = ".editable2[data-type='select2']";

    $(select2EditableSelector).each((index, element) => {
        if ($(element).html().trim().length === 0) {
            $(element).html("<p>____</p>");
        }
    })
}

$(document).ready(function () {
    let practiceListContainers = $('.practice-scrollable-container');
    let activeModal = null;
    let activeModalInputs = {};

    setScrollPositionAtActivePractice();
    attachTooltipsToLongPractices();


    $(document).on('click', '.toggle-integration', function (e) {
        if (!$(this).is(':disabled')) {
            toggleIntegration($(this));
        }
        e.stopPropagation();
    });

    /** Show changelog modal */
    if ($("#changelog_modal").attr("data-auto-show") === "true") {
        $("#changelog_modal").modal("toggle");
    }

    /**
     * Check if the ajax request is safe
     */
    function csrfSafeMethod(method) {
        try {
            let methods = JSON.parse(csrfSafeMethods);
            return methods.indexOf(method) >= 0;
        } catch (e) {
            return false;
        }
    }


    /**
     * Add the csrf token to every ajax request
     */
    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            if (!csrfSafeMethod(settings.type) && !this.crossDomain) {
                xhr.setRequestHeader(csrfHeader, csrfToken);
            }
        }
    });

    /**
     * Focus search
     */
    $('form[id="advanced-search-form"] input[name="search"]').focus();

    /**
     * Clears the search box
     */
    $('.clear-table-search-container').on('click', function () {
        let $this = $(this),
            inputValue = $this.siblings('input').val();
        if (inputValue.length > 0) {
            let currentLocation = window.location;
            window.location = currentLocation.protocol + '//' + currentLocation.host + currentLocation.pathname + currentLocation.search;
        }
    });


    /**
     * Clears the js search box
     */
    $('.clear-table-js-search-container').on('click', function () {
        let $this = $(this);
        $this.parent().find(".table-js-search-input").val("").trigger("keyup");
    });

    $(document).on('click', '.copy-link-button', function (e) {
        e.preventDefault();
        let $this = $(this),
            url = $this.data('url'),
            temp = $("<input>");
        $("body").append(temp);
        temp.val(url).select();
        document.execCommand("copy");
        temp.remove();
    });

    /**
     * Toggle alert modal if exist
     */
    if ($('.modal.alert').length > 0) {
        $('.modal.alert').modal('show');
    }

    /** Start refresh on modal close */
    $(document).on('hidden.bs.modal', ".modal", (e) => {
        if (Object.keys(activeModalInputs).length > 0) {
            let modal = $(e.target);
            let inputs = {};
            modal.find(".editable").each((index, input) => {
                inputs[$(input).attr('id')] = $(input).editable('getValue', true);
            });
            if (Object.keys(inputs).length > 0 && modal.attr("id") === activeModal.attr("id") && !compareObjects(inputs, activeModalInputs)) {
                e.preventDefault();
                window.location.reload();
            }
        }
    });

    $(document).on('shown.bs.modal', ".modal", (e) => {
        activeModalInputs = {};
        activeModal = $(e.target);
        activeModal.find(".editable").each((index, input) => {
            activeModalInputs[$(input).attr('id')] = $(input).editable('getValue', true);
        });
    });

    function searchTableRow(searchValue, row) {
        let name = row.find('td:first-child').text().toLowerCase().trim();
        if (name.includes(searchValue)) {
            row.show();
        } else {
            row.hide();
        }
    }

    function compareObjects(firstObject, secondObject) {
        let firstObjectKeys = Object.keys(firstObject);
        let secondObjectKeys = Object.keys(secondObject);
        if (firstObjectKeys.length !== secondObjectKeys.length) {
            return false;
        }
        for (let key of firstObjectKeys) {
            if (firstObject[key] !== secondObject[key]) {
                return false;
            }
        }
        return true;
    }

    /** End refresh on modal close */


    /** Start Select2 Editable */

    reloadSelect2Editable();
    let select2EditableSelector = ".editable2[data-type='select2']";
    $(document).on("click", select2EditableSelector, function() {
        let errorDiv = '<div class="editable-error-block help-block"></div>';
        let errorDivSelector = '.editable-error-block';
        $(this).select2({
            ajax: {
                url: $(this).attr('data-source'),
                dataType: 'json',
                type: "GET",
                quietMillis: 50,
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                text: item.text,
                                id: item.value,
                                item: item
                            }
                        })
                    };
                },
            }
        });
        $(this).select2("open");
        $(this).on("select2:select", (e) => {
            let $this = $(this);
            $.ajax({
                url: $this.data('url'),
                type: 'POST',
                data: {
                    pk: $this.attr('data-pk'),
                    name: $this.attr('id'),
                    value: $this.val()
                },
                success: function (results) {
                    if ($this.children() && $this.children().length !== 0) {
                        let lastChild = $this.children().get($this.children().length - 1);
                        $(lastChild).detach();
                        $this.empty();

                        let selectedValueSpan = document.createElement('span')
                        selectedValueSpan.innerText = lastChild.innerText;

                        $this.append(selectedValueSpan);
                    }
                    let $siblings = $this.siblings("a");
                    if ($siblings.length === 1) {
                        let $targetElement = $($siblings[0]);
                        if (parseInt($this.val())) {
                            $targetElement.show();
                            let href = $targetElement.attr('href');
                            $targetElement.attr('href', href.replace(/\/[^\/]*$/, '/' + parseInt($this.val())));
                        } else {
                            $targetElement.hide();
                        }
                    }
                    if ($this.parent().find(errorDivSelector).length > 0) {
                        $this.parent().find(errorDivSelector).remove();
                    }

                    if ($this.attr('id') === 'mainAssessor' || $this.attr('id') === 'secondaryAssessor') {
                        $this.trigger('assessorChange');
                    }
                },
                error: function (result) {
                    let msg = "";
                    let lastChild = $this.children().get($this.children().length - 1);
                    $(lastChild).remove();
                    if ($this.parent().find(errorDivSelector).length === 0) {
                        $this.after(errorDiv);
                    }
                    try {
                        msg = JSON.parse(result.responseText).msg;
                        if (typeof msg == 'undefined') {
                            msg = trans('admin.general.exception_message', {message: ''});
                        }
                    } catch (exception) {
                        msg = trans('admin.general.exception_message', {message: ''});
                    }
                    $this.parent().find(errorDivSelector).html(msg);
                }
            });
            $this.off("select2:select");
            $this.select2().select2("destroy");
        });
        $(this).on('select2:clear', function () {
            let $this = $(this);
            $.ajax({
                url: $this.attr('data-url'),
                type: 'POST',
                data: {
                    pk: $this.attr('data-pk'),
                    name: $this.attr('id'),
                    value: null
                }
            });
        });
        $(this).on('select2:close', function () {
            $(this).select2().select2("destroy");
        });
    });

    /** End Select2 Editable */

    // sets the scroll position at the active practice
    function setScrollPositionAtActivePractice() {
        practiceListContainers.each(function () {
            let list = $(this);

            let activeLi = list.find(".mm-active").eq(0);
            if (activeLi.length > 0) {
                let parentOffset = activeLi.offsetParent().offset();
                let activeLiOffset = activeLi.offset();
                let offsetHeightBetween = activeLiOffset.top - parentOffset.top - activeLi.height() / 4;
                $(list).scrollTop(offsetHeightBetween);
            }
        });
    }

    // adds an attribute 'data-toggle=tooltip' to the div text element if the practice text is longer than 24 characters
    // otherwise removes the attribute 'title', since the text can fit and does not need a title/tooltip attribute
    function attachTooltipsToLongPractices() {
        practiceListContainers.each(function () {
            let list = $(this);
            list.find('li').each(function () {
                let li = $(this);

                let textDiv = li.find('.practice-name-div').eq(0);
                if (textDiv.text().trim().length > 24) {
                    textDiv.attr('data-toggle', 'tooltip');
                } else {
                    textDiv.removeAttr('title');
                }
            });
        });
    }

    function toggleIntegration(button) {
        let url = button.data('url');

        $.ajax({
            type: 'POST',
            url: url,
            timeout: 10000,
            success: function (result) {
                addFlash('success', result.message + " " + result.newStatus);
                button.toggleClass('on', result.newStatus === 'on');
                button.toggleClass('off', result.newStatus !== 'on');
            }
        });
    }

    $(document).on("click", function (event) {
        if ($(event.target).is($(".dots-button").first())) {
            return;
        }
        if ($(".header-mobile-open").first().length > 0 && !$(event.target).is($(".header-mobile-open").first())) {
            $(".app-header .app-header__content").removeClass("header-mobile-open");
        }
    });

    $('.prevent-double-click-improvement').on("click", function (event) {
        let buttons = [$("#complete-stream"), $("#improveStreamBtn")];
        $.each(buttons, function (index, buttonElement) {
            preventDoubleClickTransparentDiv(buttonElement);
        });
    });

    $('.prevent-double-click').on("click", function (event) {
        preventDoubleClickTransparentDiv($(this));
    });

    // draws a transparent div on top of the button slightly larger, then removes it after some time, also makes the button grey
    function preventDoubleClickTransparentDiv(clickedButton) {
        let currentButton = clickedButton;
        currentButton.css({
            backgroundColor: '#bababa',
            borderColor: '#bababa',
        });

        let overlay = $("<div id='overlay-prevent-click'></div>");
        let buttonOffset = currentButton.offset();
        let buttonWidth = currentButton.outerWidth() + 50;
        let buttonHeight = currentButton.outerHeight() + 50;
        overlay.css({
            top: buttonOffset.top - 25,
            left: buttonOffset.left - 25,
            width: buttonWidth,
            height: buttonHeight,
            display: "block",
            backgroundColor: "transparent",
            zIndex: 99999,
            position: "absolute",
        });
        $("body").append(overlay);

        setTimeout(function () {
            currentButton.css({
                backgroundColor: '',
                borderColor: '',
            });
            overlay.remove();
        }, 1000);
    }
});