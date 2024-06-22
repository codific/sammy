$(document).ready(function() {
    window.reloadEditable = function() {
        /**
         * x-editable
         */
        $('.editable').editable({
            mode: "inline",
            emptytext: "<p>&nbsp;</p>",
            onblur: "submit",
            showbuttons: false,
            success: function (response) {
                if (response.status == 'error') {
                    return response.msg; // msg will be shown in editable form
                }
            }
        });

        $('.editableDate').editable({
            emptytext: "<p>&nbsp;</p>",
            format: 'YYYY-MM-DD HH:mm',
            template: 'HH:mm D / MM / YYYY',
            params: {
                type: "datetime"
            },
            combodate: {
                minYear: new Date().getFullYear() - 50,
                maxYear: new Date().getFullYear() + 10
            },
            success: function (response) {
                if (response.status == 'error') {
                    return response.msg; // msg will be shown in editable form
                }
            }
        });

        $('.editable').on('shown', function (e, editable) {
            if (editable) { // if you're not using popovers, this check is unnecessary
                editable.input.$input.on('keydown', function (e) {
                    if (e.which == 9) { // when tab key is pressed
                        e.preventDefault();
                        let next = nextField($(this), e.shiftKey);
                        let form = $(this).closest("td").find("form");
                        form.submit();
                        if (form.has("div.has-error").length == 0) {
                            next.editable('show');
                        }
                    }
                });
            }
        });

        // Changes the related info button link
        // of a live edit select when the selected value changes
        $("a.editable.editable-click").on('click', function () {
            let $editableLink = $(this),
                $select = $editableLink.siblings('span').find('select');
            if ($select.length == 1) {
                $select.on('change', function () {
                    let selectedValue = $(this).val(),
                        $siblings = $editableLink.siblings("a");
                    if ($siblings.length == 1) {
                        let $targetElement = $($siblings[0]);
                        if (parseInt(selectedValue)) {
                            $targetElement.show();
                            let href = $targetElement.attr('href');
                            // replaces the last part of the url with the selected value
                            // e.g. domain.com/group/175 => domain.com/group/142
                            $targetElement.attr('href', href.replace(/\/[^\/]*$/, '/' + parseInt(selectedValue)));
                        } else {
                            $targetElement.hide();
                        }
                    }
                });
            }
        });

        let nextField = function (el, usePrev) {
            if (usePrev) { // when shift + tab
                return el.parents().prevAll(":has(.editable:visible):first").find(".editable:last");
            } else { // when just tab
                return el.parents().nextAll(":has(.editable:visible):first").find(".editable:first");
            }
        };
    }

    reloadEditable();


});