$(document).ready(function(){ 
    Dropzone.autoDiscover = false;  //prevent DropZone from automatically attaching to .dropzone Elements

    var saveBtn = $('#create');
    var dropzones = []; // for use with multiple dropzones on the same page: store them in array
    var count = 0;      // dropzones count
    $('body').data('sendCount', 0);       // keep track of number of files being uploaded to disable submit button
    // iterating through every .dropzone element to create unique dropzones
    $('.dropzone').each(function(){
        var className = "dz-" + count; // unique classname for each dropzone instance
        $(this).addClass(className);   // add that unique classname to current element
        var parent = $(this).parent();
        var dzData = parent.children('.dzData');        // Form Input with URL value necessary for posting to server
        var file_descr = parent.children('.file_description'); // Form Input with file data necessary on server
        var file_name = parent.children('.file_name'); // Form Input with file data necessary on server

        // saveBtn & sendCount = for disabeling button during upload, className = for unique class for DZ, dzData.data(href) = POST Url for file upload, customDZTemplate = template for file DZ (lies in returnDropzone.js but can be overridden)
        dropzones[count] = returnDropzone(saveBtn, className, dzData.data('href'), customDZTemplate, file_descr, file_name);  

        count++;
    });
});
