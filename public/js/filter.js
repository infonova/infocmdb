$(document).ready(function () {
    /* Search for global seachfield */
    var filterDefault = 'Filter';

    if ($("#search").val().length == 0)
        $("#search").val(filterDefault);

    $("#search").focus(function (event) {
        if ($("#search").val() == filterDefault)
            $("#search").val('');
    });
    $("#search").blur(function (event) {
        if ($("#search").val().length == 0)
            $("#search").val(filterDefault);
    });
}); // document ready
