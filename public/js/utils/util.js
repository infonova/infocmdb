$(document).ready(function () {
    $("a.all").click(function () {
        var elem = $(this);
        var inputWrapper = elem.closest('table');
        var inputType = inputWrapper.find('input[type=checkbox], input[type=radio]').attr('type');

        if (inputType == 'checkbox') {
            var allInputs = inputWrapper.find('input[type="checkbox"]:visible:enabled:not(.disabled)');
            var checkedInputs = inputWrapper.find('input[type="checkbox"]:visible:enabled:not(.disabled):checked');
            if (allInputs.length == checkedInputs.length) {
                allInputs.prop('checked', false);
            } else {
                allInputs.prop('checked', true);
            }
        } else if (inputType == 'radio') {
            var inputValueToSet = elem.parent().index();
            inputWrapper.find('input:radio:visible:enabled:not(.disabled)[value=' + inputValueToSet + ']').prop('checked', true);
        }

    });

    if ($("#searchstring").length > 0) {
        /**
         * SEARCH FIELD
         */

        /* Search for global seachfield */
        $("#searchstring").val($("#searchstringAjax").val());

        $("#searchstring").focus(function (event) {
            if ($("#searchstring").val() == searchDefault)
                $("#searchstring").val('');
        });
        $("#searchstring").blur(function (event) {
            if ($("#searchstring").val().length == 0)
                $("#searchstring").val(searchDefault);
        });

        /* slide effect for user info */
        var infoOpen = 0;
        $("#openTab").click(function () {
            if (infoOpen == 0) {
                $("#addInfoCover").slideDown('fast');
                $("#addInfo").slideDown('slow', function () {
                    infoOpen = 1;
                    $("#openTab").addClass('opened');
                });
                $("#quickMessages").slideDown('fast');
                //$("#opener").slideDown('slow');
            } else {
                $("#addInfoCover").slideUp("slow");
                $("#addInfo").slideUp("slow", function () {
                    infoOpen = 0;
                    $("#openTab").removeClass('opened');
                });
                $("#userDataDetail").fadeOut("slow", function () {
                    userDetailOpen = 0;
                });
                $("#opener").slideUp("slow");
            }
        });

        /* user detail pop up */
        var userDetailOpen = 0;
        var position = null;
        var displayUserData = function () {
            if (userDetailOpen == 0) {
                position = $("#userDataLink").offset();
                position.left -= 45;
                position.top += position.top + 15;
                $("#userDataDetail").offset(position);
                $("#userDataDetail").fadeIn('slow', function () {
                    userDetailOpen = 1;
                });
            }
            else
                $("#userDataDetail").fadeOut("slow", function () {
                    userDetailOpen = 0;
                });
        };
        $("#userDataLink").click(displayUserData);

        var optionListMaxWidth = Math.max.apply(null, $('.optionList .counterLabel').map(function () {
            return $(this).width();
        }).get());
        $('.optionList .counterLabel').css('min-width', optionListMaxWidth);

        project_list_container = $(".small_project_list");
        project_list_container.mouseleave(function () {
            toggleProjectDropdown();
        });

        $('.project_list_element_favorite').each(function () {
            $(this).click(function () {
                var button_clicked = $(this);
                button_clicked.find('i').toggleClass('fa-star fa-star-o project_list_element_favorited');

                //                    button_clicked.css("pointer-events: none;");
                //                    button_clicked.css('cursor', 'progress');
                // disable button to prevent double click
                button_clicked.toggleClass('busy');

                var project_id = button_clicked.data('projectid').toString();

                var data = {
                    "settings": {
                        "pinned_projects": project_data.pinned_projects
                    }
                };
                var url = "/user/updateusersettings";
                // if we just pinned the project
                if (button_clicked.find('i').hasClass('project_list_element_favorited')) {
                    project_data.pinned_projects.push(project_id);
                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        data: data,
                        url: url,
                        success: function (result) {
                            button_clicked.toggleClass('busy');
                            $('#pinned_project_' + project_id).toggleClass('hidden');
                        },
                        error: function (request, status, error) {
                            console.error(request.responseText);
                            button_clicked.toggleClass('busy');
                        }
                    });
                } // if we unpin it
                else {
                    var index = project_data.pinned_projects.indexOf(project_id);
                    if (index > -1) {
                        project_data.pinned_projects.splice(index, 1);
                        if (project_data.pinned_projects.length === 0) {
                            data.settings.pinned_projects = null;
                        }
                        $.ajax({
                            type: "POST",
                            dataType: "json",
                            data: data,
                            url: url,
                            success: function (result) {
                                button_clicked.toggleClass('busy');
                                $('#pinned_project_' + project_id).toggleClass('hidden');
                            },
                            error: function (request, status, error) {
                                console.error(request.responseText);
                                button_clicked.toggleClass('busy');
                            }
                        });
                    }
                    else {
                        console.error("Tried to remove a project that has not been pinned.");
                        button_clicked.toggleClass('busy');
                    }
                }
            });
        });
        for (var i = 0; i < project_data.pinned_projects.length; i++) {
            $('#pin_project_' + project_data.pinned_projects[i] + " > i").toggleClass('fa-star fa-star-o project_list_element_favorited');
            $('#pinned_project_' + project_data.pinned_projects[i]).toggleClass('hidden');
        }
    }
}); // document ready

project_toggle_active = false;

function toggleProjectDropdown() {
    project_toggle_active = !project_toggle_active;
    var tab_list_selector = $('#project_dropdown_trigger');
    project_list_container.toggleClass('hidden', !project_toggle_active);

    //                var indicator = $('#project_toggle_indicator');
    //                // if current state is active, hide caret right and show caret down
    //                indicator.toggleClass( 'fa-caret-right', !project_toggle_active );
    //                indicator.toggleClass( 'fa-caret-down', project_toggle_active );
}


/**
 * NUMBERS ONLY
 *
 * @param myfield
 * @param e
 * @param dec
 * @returns {Boolean}
 */
function numbersonly(myfield, e, dec) {
    var key;
    var keychar;

    if (window.event)
        key = window.event.keyCode;
    else if (e)
        key = e.which;
    else
        return true;
    keychar = String.fromCharCode(key);

    // control keys
    if ((key == null) || (key == 0) || (key == 8) ||
        (key == 9) || (key == 13) || (key == 27))
        return true;

    // numbers
    else if ((("0123456789").indexOf(keychar) > -1))
        return true;

    // decimal point jump
    else if (dec && (keychar == ".")) {
        myfield.form.elements[dec].focus();
        return false;
    }
    else
        return false;
}

function initCiPrint() {
    // overload this function in custom.js for customizing print view
}

// Menutree
navigation_fancytree = Object(null);

function collapseAll() {
    navigation_fancytree.getRootNode().children[0].visit(function (node) {
        node.setExpanded(false);
    });
}

function expandAll() {
    navigation_fancytree.getRootNode().children[0].visit(function (node) {
        node.setExpanded(true);
    });
}

function returnFaClass(e) {
    var a;
    switch (e) {
        case "pdf":
            a = "fa-file-pdf-o";
            break;
        case "zip":
        case "rar":
            a = "fa-file-zip-o";
            break;
        case "doc":
        case "docx":
            a = "fa-file-word-o";
            break;
        case "xls":
        case "xlsx":
            a = "fa-file-excel-o";
            break;
        case "ppt":
        case "pptx":
            a = "fa-file-powerpoint-o";
            break;
        case "png":
        case "jpeg":
        case "jpg":
        case "exif":
        case "tiff":
        case "gif":
        case "bmp":
            a = "fa-file-image-o";
            break;
        case "txt":
        case "csv":
            a = "fa-file-text-o";
            break;
        default:
            a = "fa-file"
    }
    return a
}

function activateDatetimePickers() {
    $('.datetime-picker').each(function (index, element) {
        var current_element = $(this);
        var options = parseDatetimeOptions(current_element);
        if (current_element.val() === '0000-00-00' || current_element.val() === '0000-00-00 00:00:00') {
            current_element.val("");
        }
        current_element.flatpickr(options);
    });
}

function parseDatetimeOptions(element) {
    var options = {};
    var data_options = element.data();
    if ('enabletime' in data_options) {
        options.enableTime = data_options.enabletime;
        options.plugins = [new confirmDatePlugin({})];
    }
    // add default options
    options.allowInput = true;
    options.time_24hr = true;
    options.enableSeconds = true;
    // globally set in default and admin.phtml
    options.locale = language;
    return options;
}

$(document).ready(function () {

    if (typeof navigation_tree_json !== 'undefined') {

        var fancytree_selector = $('#fancytree');
        // persistence extension docu
        // https://github.com/mar10/fancytree/wiki/ExtPersist
        fancytree_selector.fancytree({
            extensions: ["persist", "filter"],
            source: navigation_tree_json,
            persist: {
                store: "local"
            },
            filter: {  // override default settings
                counter: false, // No counter badges
                mode: "hide",
                autoExpand: true,
                highlight: false
            },
            toggleEffect: false,
            click: function (event, data) {
                // if title was clicked with left mouse button
                if (data.targetType === "title" && event.originalEvent.which === 1) {
                    if (data.node.data.href === "none") {
                        data.node.toggleExpanded();
                        return false;
                    }
                }
            },
            clickFolderMode: 1
        });
        // remove the control to collapse whole menu
        fancytree_selector.find('.root .fancytree-expander').addClass("hide");
        // remove the home title (has annoying background effect)
        fancytree_selector.find('.root .fancytree-title').detach();
        navigation_fancytree = fancytree_selector.fancytree("getTree");
        // expand root node if not yet expanded to show menu
        var root_node = navigation_fancytree.getRootNode().children[0];
        if (!root_node.isExpanded()) {
            root_node.setExpanded(true);
        }
        // parse current url to check if ci is viewed
        // if yes the path in the menu is expanded to show the ci
        var current_url = window.location.href;
        var type_id_position = current_url.indexOf("typeid");
        if (type_id_position !== -1) {
            type_id_position += 7;
            var type_id = current_url.substr(type_id_position, current_url.length);
            var type_id_delimiter_position = type_id.indexOf("/");
            if (type_id_delimiter_position > 0) {
                type_id = type_id.substr(0, type_id_delimiter_position);
                var node = navigation_fancytree.getNodeByKey(type_id);
                node.makeVisible({scrollIntoView: false});
                node.setActive(true);
                node.setFocus(true);
            }
        }

//                    var search_selector = $('#navigation-search');
//                    search_selector.on('input', function() {
//                        var current_input = search_selector.val();
//                        if (current_input !== "" && current_input.length > 1) {
//                            navigation_fancytree.filterNodes(current_input);
//                        }
//                    });
        window.scrollTo(0, 0);
    }
    /* activate datetime pickers
		* Datetime picker usage:
			Create an input field and give it the class .datetime-picker.
			You can use the data tag "data-enabletime="true"" to enable date time picking. Without
			this option it will only be a date picker.
		* Example:
		 	<input class="datetime-picker" name="date" data-enabletime="true">
	*/
    // moved into extra function so it can be triggered after document is ready
    // (maybe html is loaded with ajax request and so on
    activateDatetimePickers();

    $('.slidebox').each(function(index, elem) {
        initSlidebox(elem);

    });
});

function initSlidebox(elem) {
    var slideBox = $(elem);
    var slideHeader = slideBox.find('.slidebox-header');
    var slideContent = slideBox.find('.slidebox-content');

    if(slideBox.hasClass('slidebox-open')) {
        slideContent.show();
    } else {
        slideBox.addClass('slidebox-closed');
    }

    // add icon
    if(slideHeader.find('.slidebox-buttons').length === 0) {
        var iconClass = 'fa-arrow-up';
        if (slideContent.is(':hidden')) {
            iconClass = 'fa-arrow-down';
        }

        slideHeader.prepend(
            '<span class="slidebox-buttons">' +
            '    <i class="fa ' + iconClass + '"></i>' +
            '</span>'
        );
    }

    // add click event
    slideBox.find('.slidebox-header').click(function () {
        var icon = slideBox.find('.slidebox-buttons i');

        slideContent.slideToggle({
            complete: function () {
                if (slideContent.is(':hidden')) {
                    icon.addClass('fa-arrow-down');
                    icon.removeClass('fa-arrow-up');

                    slideBox.removeClass('slidebox-open');
                    slideBox.addClass('slidebox-closed');
                } else {
                    icon.removeClass('fa-arrow-down');
                    icon.addClass('fa-arrow-up');

                    slideBox.addClass('slidebox-open');
                    slideBox.removeClass('slidebox-closed');
                }
            }
        });

    });
}

//title	string,	content	string,	settings object
function showInfocmdbDialog(title, content, settings) {

    if (settings === undefined) {
        settings = {};
    }

    var defaultSettings = {
        autoOpen: true,
        modal: true
    };

    $.each(defaultSettings, function (key, value) {
        if (!(settings.hasOwnProperty(key))) {
            settings[key] = value;
        }
    });

    var modal = '';
    modal += '<div class="infocmdb-dialog" title="' + title + '" id="infocmdb-dialog">';
    modal += content;
    modal += '</div>';
    $("#infocmdb-modal-container").append(modal);
    $('#infocmdb-dialog').dialog(settings);
    $('#infocmdb-dialog').on('dialogclose', function (event) {
        $(this).detach();
    });
}


var invalidLocks = {};

function refreshLock(lock_id, refreshRate, messages) {
    if (lock_id in invalidLocks) {
        return false;
    }

    if (lock_id === '') {
        return true;
    }

    var result = false;

    $.ajax({
        url: "/ci/refreshlock/lock_id/" + lock_id,
        dataType: "json",
        async: false,
        success: function (data) {
            if (data.success === true) {
                setTimeout(function () {
                    refreshLock(lock_id, refreshRate, messages);
                }, refreshRate);
                result = true;
            }
            else if (data.success === false && !(lock_id in invalidLocks)) {
                invalidLocks[lock_id] = lock_id;
                alert(messages.expired);
            }
        },
        error: function (request, status, error) {
            // do not show message to user --> changing page during ajax request will show expired alert
            // lock will be checked again before submit and expired alert popup will be shown by this function
            console.log(messages.error);
        }
    });

    if (result === false) {
        $('.standard_button[id^=create]').prop('disabled', true);
    }

    return result;
}

function initAceEditor(textAreaSelector, mode) {
    var textarea = $(textAreaSelector);

    if (textarea.length) {
        textarea.hide();

        var scriptEditorDiv = $("<div class='ace-editor-wrapper'>");
        scriptEditorDiv.uniqueId();
        var scriptEditorId = scriptEditorDiv.attr('id');
        textarea.parent().prepend(scriptEditorDiv);

        var editor = ace.edit(scriptEditorId);
        scriptEditorDiv.resizable({
            //resize editor too
            resize: function (event, ui) {
                editor.resize();
            }
        });
        editor.getSession().setMode("ace/mode/" + mode);
        editor.getSession().setValue(textarea.val());
        editor.getSession().on('change', function () {
            textarea.val(editor.getSession().getValue());
        });

        return editor;
    }
}

function changeCiIcon(input) {
    var elem = $(input);
    var iconDiv = $(elem).closest('.pillar_icon');

    if (input.files && input.files[0]) {
        let file = input.files[0];
        iconDiv.find('img').attr('src', URL.createObjectURL(file));
        iconDiv.find('#ciicon_delete').val(0);
        iconDiv.find('#ciicon_delete_link').show();
    }
}

function deleteCiIcon(input) {
    var elem = $(input);
    var iconDiv = $(elem).closest('.pillar_icon');

    iconDiv.find('img').attr('src', '/images/ci.png');
    iconDiv.find('#ciicon').val('');
    iconDiv.find('#ciicon_delete').val(1);
    iconDiv.find('#ciicon_delete_link').hide();
}