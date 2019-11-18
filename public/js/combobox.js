$(function () {

    $.widget("custom.combobox", {
        options: {
            source: '',
            inputClear: '',
            inputShowAllOptions: '',
        },

        formElemType: undefined,

        data: {},

        _create: function () {
            this.formElemType = this.element.get(0).nodeName;
            this.wrapper = $("<span>")
                .addClass("custom-combobox")
                .insertAfter(this.element);

            this.element.hide();
            this._createAutocomplete();
            this._createShowAllButton();
            this._createClearButton();
        },

        _setOption: function (key, value) {
            this._super(key, value);

            this.input.autocomplete('option', 'source', this.options.source);
        },
        _setOptions: function (options) {
            this._super(options);

            this.input.autocomplete('option', 'source', this.options.source);
        },

        _createAutocomplete: function () {
            var selected = this.element.children(":selected");
            var value = selected.val() ? selected.text() : "";
            var ele = this.element.attr("id");

            var widget = this;

            var optionSource;
            // no source --> parse select options
            if (widget.options.source === '') {
                optionSource = function (request, response) {
                    var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
                    response(widget.element.children("option").map(function () {
                        var optionElem = this;
                        var text = $(optionElem).text();
                        if (optionElem.value && (!request.term || matcher.test(text)))
                            return {
                                label: text,
                                value: text,
                                id: $(optionElem).val(),
                                option: optionElem
                            };
                    }));
                };
            } else {
                optionSource = widget.options.source;
            }

            this.input = $("<input>")
                .appendTo(this.wrapper)
                .val(value)
                .attr("title", "")
                .attr("id", ele + "-input")
                .addClass("custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left")
                .autocomplete({
                    delay: 300,
                    minLength: 0,
                    source: optionSource,
                    select: function (event, ui) {
                        return widget._ac_select(this, event, ui);
                    },
                    response: function (event, ui) {
                        return widget._ac_response(this, event, ui);
                    },
                    create: function (event, ui) {
                        return widget._ac_create(this, event, ui)
                    },
                }) // end autocomplete

                .tooltip({
                    classes: {
                        "ui-tooltip": "ui-state-highlight"
                    }
                })
            ;

            var wrapperWidth = '200px';
            if(widget.element.width() > 200) {
                wrapperWidth = widget.element.width();
            }
            this.wrapper.css('width', wrapperWidth);

            this._on(this.input, {
                autocompletechange: "_removeIfInvalid"  //blur
            });

        },

        _createClearButton: function () {
            var input = this.input;
            var widget = this;
            var ele = this.element.attr("id");

            this.clearButton = $("<a>")
                .attr("tabIndex", -1)
                .attr("title", widget.options.inputClear)
                .tooltip()
                .insertAfter(widget.input)
                .button({
                    icons: {
                        primary: "ui-icon-cancel"
                    },
                    text: false
                })
                .removeClass("ui-corner-all")
                .css("right", "17px")
                .attr("id", ele + "-clearbutton")
                .addClass("custom-combobox-toggle")
                .on("click", function () {
                    input.trigger("focus");

                    // clear input field and value in hidden field
                    widget.input.val('');
                    widget.element.val('');
                });

            inputWidth = this.wrapper.width() - this.clearButton.outerWidth(true) - this.showAllButton.outerWidth(true) - 1;
            widget.input.css("width",  inputWidth + "px");
        },

        _createShowAllButton: function () {
            var input = this.input;
            var widget = this;
            var wasOpen = false;
            var ele = this.element.attr("id");

            this.showAllButton = $("<a>")
                .attr("tabIndex", -1)
                .attr("title", widget.options.inputShowAllOptions)
                .tooltip()
                .insertAfter(widget.input)
                .button({
                    icons: {
                        primary: "ui-icon-triangle-1-s"
                    },
                    text: false
                })
                .removeClass("ui-corner-all")
                .css("right", "0px")
                .addClass("custom-combobox-toggle ui-corner-right")
                .attr("id", ele + "-showbutton")
                .on("mousedown", function () {
                    wasOpen = input.autocomplete("widget").is(":visible");
                })
                .on("click", function () {
                    input.trigger("focus");

                    // Close if already visible
                    if (wasOpen) {
                        return;
                    }

                    // Pass empty string as value to search for, displaying all results
                    input.autocomplete("search", "");
                });
        },

        _ac_create: function (acInput, event, ui) {
            var widget = this;

            if (widget.options.source !== '' && widget.element.val() !== "") {
                $.ajax({
                    method: "POST",
                    url: widget.options.source,
                    data: {type: "id", term: widget.element.val()}
                })

                    .done(function (msg) {
                        widget.input.val(msg[0].label);
                        widget.data[msg[0].id] = msg[0].label;
                    })
                    .fail(function (jqXHR, textStatus) {
                        console.log("error: couldn't set initial value", jqXHR, textStatus);
                    });
            }
        },

        _ac_select: function (acInput, event, ui) {
            var widget = this;

            // Set autocomplete element to display the label
            acInput.value = ui.item.label;

            // Store value in hidden field
            widget.element.val(ui.item.id);
            widget.element.change();

            // Prevent default behaviour
            return false;
        },

        _ac_response: function (acInput, event, ui) {
            var widget = this;

            var values = ui.content;
            if(!(ui.content instanceof Array)) {
                values = ui.content.get();
            }

            for (var savedValue in values) {
                widget.data[ui.content[savedValue].id] = ui.content[savedValue].value;
            }
        },

        //reverts autocomplete to previous state if invalid selection was made
        _removeIfInvalid: function (event, ui) {
            var savedValues = this.data;
            var elementValue = this.element.val();

            if (ui.item) {
                return;
            }

            if (this.input.val() === "") {
                this.element.val("");
                return;
            } else if (elementValue === "") {
                this.input.val("");
                this.input.autocomplete("instance").term = "";
            } else {
                this.input.val(savedValues[elementValue]);
            }
        },

        _destroy: function () {
            this.wrapper.remove();
            this.element.show();
        }
    });
});