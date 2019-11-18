/*
 * DROPZONE Implementation
 * requires: sendBtn, className, POST_URL, customTemplate, file_descr, file_name
 *           sendBtn = jQuery Object of Submit Button for form
 *           className = String, Name of the class for the element the dropzone is assigned to
 *           POST_URL = String, URL where the file gets posted to
 *           customTemplate = String, html template for dropzone (refer to http://www.dropzonejs.com/#layout)
 *           file_descr = jQuery Object, Input file description
 *           file_name = jQuery Object, Input file name
 *           hidden inputs filename [id: $attributeId.$key.'filename'], description [class: file_description; id: $attributeId.$key.'description']
 *           Function returnFaClass() [+ FONT-AWESOME for icons]
 *           before returnDropzone function call: $('body').data('sendCount', 0); // for keeping track of files being uploaded accross the html document
 * DROPZONE_LANG_STRINGS can be overridden in data/langauges/[lang]/ci_[lang].csv
 */

// template for displaying uploads
var customDZTemplate = '<div class="dzFileView"><i class="fa"> </i> <span data-dz-name></span> <span data-dz-errormessage></span></div>';
var DROPZONE_LANG_STRINGS = {
        dictDefaultMessage: "Ziehen Sie Dateien zum Hochladen hier her",
        dictFallbackMessage: "Ihr Browser unterstützt Drag'n'Drop nicht",
        dictFallbackText: "Klicken Sie hier, um eine Datei hochzuladen",
        dictInvalidFileType: "Dieser Dateityp wird nicht unterstützt!",
        dictFileTooBig: "Datei ist zu groß. Maximale Groesse: {{maxFilesize}}",
        dictResponseError: "Serverfehler: {{statusCode}}",
        dictCancelUpload: "Hochladen abbrechen",
        dictCancelUploadConfirmation: "Hochladen wirklich abbrechen?",
        dictRemoveFile: "Datei entfernen",
        dictMaxFilesExceeded: "Sie können keine weiteren Dateien hochladen",
        dictDeleteUploadConfirmation: "Datei wirklich entfernen?",
        dictTooManyFiles: "Maximale Datei-Anzahl überschritten! Maximale Anzahl: ",
        dictOverrideFile: "Es ist bereits eine Datei vorhanden. Möchten Sie diese überschreiben?"
};

function returnDropzone(sendBtn, className, POST_URL, customDZTemplate, file_descr, file_name) {
    // break if no elements match
    if($('.' + className).length == 0) {
        return null;
    }

    var fileError = false; // used to prevent user from dragging more than maxFiles files into the dropzone
    var body = $('body');    
    var dz =  new Dropzone('.' + className, {
        url: POST_URL, // POST Url
        maxFiles: 1,
        maxFilesize: 10240, // MB
        clickable: true,          // if true: click on dropzone will open file picker
        createImageThumbnails: false,   
        previewTemplate: customDZTemplate,    // custom Template for displaying uploaded files
        hiddenInputContainer: '.' + className,
        dictDefaultMessage                      : DROPZONE_LANG_STRINGS.dictDefaultMessage,
        dictFallbackMessage                     : DROPZONE_LANG_STRINGS.dictFallbackMessage,
        dictFallbackText 			: DROPZONE_LANG_STRINGS.dictFallbackText,
        dictInvalidFileType                     : DROPZONE_LANG_STRINGS.dictInvalidFileType,
        dictFileTooBig                          : DROPZONE_LANG_STRINGS.dictFileTooBig,
        dictResponseError 			: DROPZONE_LANG_STRINGS.dictResponseError,
        dictCancelUpload                        : DROPZONE_LANG_STRINGS.dictCancelUpload,
        dictCancelUploadConfirmation            : DROPZONE_LANG_STRINGS.dictCancelUploadConfirmation,
        dictRemoveFile                          : DROPZONE_LANG_STRINGS.dictRemoveFile,
        dictMaxFilesExceeded                    : DROPZONE_LANG_STRINGS.dictMaxFilesExceeded,
        init: function() {
            var that = this;
            var dz = '.' + className;
            
            
            this.on('success', function(xml, response){
                $('#' + response.descriptionProperty).val(response.oldFilename); // populate mandatory form inputs with filename and file description
                $('#' + response.filenameProperty).val(response.newFilename);
                body.data('sendCount', getSendCount() - 1);// file done uploading
                if(getSendCount() === 0) { // no file being uploaded 
                    toggleElement(sendBtn, false);
                }
            })
            .on('drop', function(event){
                // prevent a file drop when number of files exceeds options.maxFiles
                fileError = false;
                var numberOfFiles = event.dataTransfer.files.length;
                var numMaxFiles = this.options.maxFiles;

                if( (numberOfFiles > numMaxFiles) ) {
                    alert( DROPZONE_LANG_STRINGS.dictTooManyFiles + numMaxFiles);
                    fileError = true;
                } 
                // provide option for overriding existing file
                else if (this.files.length >= numMaxFiles) {
                    if( confirm( DROPZONE_LANG_STRINGS.dictOverrideFile ) ) {
                        //remove present file and let Dropzone continue
                        this.removeFile(this.getAcceptedFiles()[0]);
                    } else {
                        fileError = true;
                    }
                }
            })
            .on('error', function(file){
                if(fileError) {
                    this.removeFile(file);
                } else {
                    body.data('sendCount', getSendCount() - 1); // error, file not uploading
                    if(getSendCount() === 0) { // no file being uploaded 
                        toggleElement(sendBtn, false);
                    }
                }
            })
            .on('addedfile', function(file){
                // adding font-awesome icon before filename
                var ext = file.name.split('.').pop();
                ext = ext.toLowerCase();
                var faClass = returnFaClass(ext);
                $(dz + ' .fa').addClass(faClass);
            })
            .on('processing', function(file){
                body.data('sendCount', getSendCount() + 1); // another file getting uploaded
                toggleElement(sendBtn, true);
            })
            .on('removedfile', function(file){
                file_descr.val('');
                file_name.val('');
            })
            ;

            $( dz ).bind('click', function(evt){
                fileError = false;
                    var numMaxFiles = that.options.maxFiles;
                    if (that.files.length >= numMaxFiles) {
                        if($(this).data('confirmed') === 'no') {
                            if( confirm( DROPZONE_LANG_STRINGS.dictOverrideFile ) ) {
                                //remove present file and let Dropzone continue
                                that.removeFile(that.getAcceptedFiles()[0]);
                            } else {
                                $(this).data('confirmed', 'yes');    
                                evt.preventDefault();
                                return false;
                            }
                        }
                    }
                    $(this).data('confirmed', 'yes');
            });
            // DROPZONE triggers a click additionally to the user click on the .dropzone Element.
            // .bind(mosuedown) fixes following IE bug: user selects file, clicks the dropzone again, confirm message appears ($(dz).bind(click) function)
            //                                       if user clicks cancel, Dropzone triggers another click and confirm message appears again
            // $(dz).bind(mousedown) + data-confirmed exists to prevent such behavior
            $(dz).bind('mousedown', function(evt){
                $(dz).data('confirmed', 'no');
            });
        },
        accept: function(file, done) {
            if(fileError) {
                done("error");
                //fileError = false;
            } else {
                done();
            }
        }
    }); 
            /* if edit page, show uploaded files */
    if(file_descr.val() !== '') {
        var data = {name: file_descr.val(), status: 'success', accepted: true};
        dz.emit("addedfile", data); // --> triggers 'addedfile'
        dz.emit("complete", data); // --> triggers 'complete'           
        dz.files.push(data);         // add file to the dropzone.files array
    }
    // returning dropzone, to allow access from outside this function
    return dz;
}

/* hide / disable submit element */

/* el = jQuery Object
 * hide = bool
 */
// IMPORTANT! jQuery 1.6 and above: change .attr to .prop
function toggleElement(el, hide) {
    var elTagName = el.prop("tagName");
    if (elTagName === 'INPUT') {
        el.prop('disabled', hide);
    } else {
        if(hide) {
            el.hide();
        } else {
            el.show();
        }
    }
} 
/* 
 * sendCount keeps track of number of files currently being uploaded on the whole document 
 * this function returns the current sendCount as integer
 */
function getSendCount() {
    return parseInt($('body').data('sendCount'));
}

/* 
 * ausgelagert in project-min.js
 */

/*
function returnFaClass(ext) {
    var faClass;
    switch(ext) {
        case 'pdf': 
            faClass = 'fa-file-pdf-o'; 
            break;
        case 'zip': 
        case 'rar':
            faClass = 'fa-file-zip-o'; 
            break;
        case 'doc':
        case 'docx': 
            faClass = 'fa-file-word-o'; 
            break;
        case 'xls':
        case 'xlsx':
            faClass = 'fa-file-excel-o';
            break;
        case 'ppt':
        case 'pptx':
            faClass = 'fa-file-powerpoint-o';
            break;
        case 'png':
        case 'jpeg':
        case 'jpg':
        case 'exif':
        case 'tiff':
        case 'gif':
        case 'bmp':
            faClass = 'fa-file-image-o';
            break;
        case 'txt':
        case 'csv':
            faClass = 'fa-file-text-o';
            break;
        default: 
            faClass = 'fa-file';
            break;
    }
    return faClass;
} */


