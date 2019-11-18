(function ($) {
    $.widget("ui.combobox", {
        options: {
            applicationUrl: '/',
            attributeId: null,
            ciId: null,
            lang: {
                'inputClear': null,
                'inputShowAllOptions': null,
            }

        },
        _create: function () {
            var self = this;
            var select = this.element.hide();
            var selectID = select.attr('id');
            var elements = select.attr('onClick');
            var attributeID = this.options.attributeId;

            var input = $("<input>")
                .insertAfter(select)
                .autocomplete({

                    source: function (request, response) {

                        var term = encodeURIComponent(request.term);
                        var ciId = self.options.ciId;

                        var ajaxUrl = self.options.applicationUrl + "ci/autocompletecitype?attributeId=" + attributeID + "&name=" + term + "&ciId=" + ciId;

                        if (elements) {
                            // assume filter attribute
                            var array = elements.split(",");
                            ajaxUrl = findFilterAttributes(array);
                            ajaxUrl = self.options.applicationUrl + "ci/filter/attributeId/" + attributeID + "/value/" + request.term + "/" + ajaxUrl;
                        }

                        select.append($('<option></option>').val("").html(""));

                        $.ajax({
                            url: ajaxUrl,
                            dataType: "json",
                            success: function (data) {
                                response($.map(data, function (item) {
                                    if (select.find('option[value="' + item.id + '"]').size() == 0) {
                                        select.append($('<option></option>').val(item.id).html(item.value));
                                    }
                                    var text = item.value;
                                    return {
                                        id: item.id,
                                        label: text,
                                        value: text
                                    }
                                }));
                            }
                        });
                    },
                    delay: 1000,
                    select: function (e, ui) {
                        if (!ui.item) {
                            // remove invalid value, as it didn't match anything
                            $(this).val("");
                            return false;
                        }
                        $(this).focus();
                        select.val(ui.item.id);
                        self._trigger("selected", null, {
                            item: select.find("[value='" + ui.item.id + "']")
                        });


                    },


                    change: function (event, ui) {
                        if (!ui.item) {
                            var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex($(this).val()) + "$", "i"),
                                valid = false;
                            select.children("option").each(function () {
                                if (this.value.match(matcher)) {
                                    this.selected = valid = true;
                                    return false;
                                }
                            });
                            if (!valid) {
                                // remove invalid value, as it didn't match anything
                                $(this).val("");
                                select.val("");
                                return false;
                            }
                        }
                    },


                    minLength: 0
                })
                .blur(function () {
                    //only execute if dropdown menu is not visible
                    if (!$(".ui-menu").is(':visible')) {
                        //if value is empty set back old value in autocomplete
                        if (input.val() == "") {
                            input.val(select.find("[value='" + select.val() + "']").html()); //set value of autocomplete
                        }
                    }
                })
                .css('width', select.width())
                .addClass("ui-widget ui-widget-content ui-corner-left");

            var clearButton = $("<button>" + this.options.lang.inputClear + "</button>")
                .insertAfter(input)
                .button({
                    icons: {
                        primary: "ui-icon-cancel"
                    },
                    text: false
                }).removeClass("ui-corner-all")
                .position({
                    my: "left center",
                    at: "right center",
                    of: input,
                    offset: "-1 0"
                })
                .css("top", "-1px").css("left", "0px")
                .click(function () {
                    input.val('');
                    select.val('');
                    return false;
                });

            var collapseAll = $("<button>" + this.options.lang.inputShowAllOptions + "</button>")
                .insertAfter(clearButton)
                .button({
                    icons: {
                        primary: "ui-icon-triangle-1-s"
                    },
                    text: false
                }).removeClass("ui-corner-all")
                .addClass("ui-corner-right ui-button-icon")
                .position({
                    my: "left center",
                    at: "right center",
                    of: clearButton,
                    offset: "-1 0"
                })
                .css("top", "-1px").css("left", "0px")
                .click(function () {
                    // close if already visible
                    if (input.autocomplete("widget").is(":visible")) {
                        input.autocomplete("close");
                        return false;
                    }
                    // pass empty string as value to search for, displaying all results
                    input.autocomplete("search", "");
                    input.focus();
                    return false;
                });

            input.val($(select).find("option:selected").text());
        }
    });

})(jQuery);