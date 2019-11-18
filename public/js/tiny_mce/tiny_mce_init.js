function setup_tinymce(language){

    // remove all existing instances of tinymce to don't break them
	destroy_tinymce();

	tinyMCE.init({
        // General options
	  	mode : "specific_textareas",
	  	editor_selector : "tinymce",
	  	convert_urls : false,
        theme : "advanced",
        relative_urls : false,       
        skin : "o2k7",
        language : language,
        skin_variant : "silver",
        plugins : "safari,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,jbimages",
		//remove auto p tag
		force_br_newlines : true,
        force_p_newlines : false,
        forced_root_block : 'div', // Needed for 3.x
        

        // Theme options
        theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
        theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
        theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
        theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage,jbimages",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true,
		theme_advanced_fonts : "Andale Mono=andale mono,times;"+
	        "Arial=arial,helvetica,sans-serif;"+
	        "Arial Black=arial black,avant garde;"+
	        "Book Antiqua=book antiqua,palatino;"+
			"Calibri=Calibri,sans-serif;"+
	        "Comic Sans MS=comic sans ms,sans-serif;"+
	        "Courier New=courier new,courier;"+
	        "Georgia=georgia,palatino;"+
	        "Helvetica=helvetica;"+
	        "Impact=impact,chicago;"+
	        "Symbol=symbol;"+
	        "Tahoma=tahoma,arial,helvetica,sans-serif;"+
	        "Terminal=terminal,monaco;"+
	        "Times New Roman=times new roman,times;"+
	        "Trebuchet MS=trebuchet ms,geneva;"+
	        "Verdana=verdana,geneva;"+
	        "Webdings=webdings;"+
	        "Wingdings=wingdings,zapf dingbats",

        

        // Drop lists for link/image/media/template dialogs
        template_external_list_url : "js/template_list.js",
        external_link_list_url : "js/link_list.js",
        external_image_list_url : "js/image_list.js",
        media_external_list_url : "js/media_list.js",

	});
	
}

function destroy_tinymce() {
    tinymce.EditorManager.editors.forEach(function(editor) {
        tinymce.EditorManager.execCommand('mceRemoveEditor', false, editor.id);
    });
}