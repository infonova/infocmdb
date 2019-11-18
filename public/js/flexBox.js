/**
 *  count all divs and insert empty div if remainder is 2
 */
function divCounter() {
    var parents = $('.parentUsers, .parentPermissions');
    var remainder = $(parents).find('div.child').length % 3;
    if (remainder === 2) {
        var lastDiv = $(parents).find('div:last-child');
        var div = '<div class="child flexboxspacer"></div>';
        $(div).insertAfter(lastDiv);
    }
}

/**
 * Sorts the divs from the all
 * @param   array   allChilds
 */
function sortDivs(divs) {

    //sort all findings per lastname
    var sortedFinding = divs.sort(function (a, b) {

        // convert to lower for proper sort
        var varA = $(a).find('span').html().toLowerCase();
        var varB = $(b).find('span').html().toLowerCase();

        // if no color(= active) sort to top
        cmpA = ($(a).hasClass('disabled') ? '2-' : '1-') + varA;
        cmpB = ($(b).hasClass('disabled') ? '2-' : '1-') + varB;

        return (cmpA < cmpB ? 1 : -1); //IE expects numbers instead of true and false otherwise the sorting would not work
    });

    return sortedFinding;
}

/**
 * Clone Header Div Template and remove the id.
 * Display the header div and insert it in the header section.
 */
function insertDynamicHeader(elem) {

    if (elem == 'userTab') {
        var rowId = 'userHeaderRow';
        var divId = 'dynamicUserHeader';
    } else if (elem == 'rightsTab') {
        var rowId = 'xmoHeaderRow';
        var divId = 'dynamicXmOciTypeHeader';
    }

    var numberOfHeaders = $('#' + rowId + ' > div').length;
    var numberOfHeadersNeeded = Math.floor($(window).width() * parseFloat("0.8") / 300);
    var times = numberOfHeaders - numberOfHeadersNeeded;

    //remove header(s)
    if (numberOfHeaders > numberOfHeadersNeeded) {
        for (var i = 1; i < times; i++) {

            $('#' + rowId + ' > div:last-child').remove();
        }
        return;
    }

    //clone and insert header
    var divHeader = $('#' + divId).clone();
    $('#' + divId).css('display', 'none');
    divHeader.removeAttr('id');
    divHeader.addClass('dynamicHeader');
    divHeader.css('overflow', 'hidden');
    divHeader.css('display', 'inline-block');
    divHeader.css('whitespace', 'nowrap');
    divHeader.css('height', 'auto');
    divHeader.css('float', 'left');
    divHeader.find('a.all').click(function () {
        var elem = $(this);
        var inputWrapper = elem.closest('fieldset');
        var inputType = inputWrapper.find('input[type=checkbox], input[type=radio]').attr('type');

        if (inputType == 'checkbox') {
            var allInputs = inputWrapper.find('.child:not(.disabled) input[type="checkbox"]:visible:enabled');
            var checkedInputs = inputWrapper.find('.child:not(.disabled) input[type="checkbox"]:visible:enabled:checked');
            if (allInputs.length == checkedInputs.length) {
                allInputs.prop('checked', false);
            } else {
                allInputs.prop('checked', true);
            }
        } else if (inputType == 'radio') {
            var radioGroupIndex = elem.parent().index();
            inputWrapper.find('.child:not(.disabled) input:radio:visible:enabled[value=' + radioGroupIndex + ']').prop('checked', true);
        }
    });

    var headerRow = $('#' + rowId);
    divHeader.insertAfter($('#' + divId));

    //add header
    if (numberOfHeaders < numberOfHeadersNeeded) {
        ;
        insertDynamicHeader(elem);
    }

}