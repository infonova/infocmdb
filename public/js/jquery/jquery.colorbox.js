/*! jQuery Colorbox Plugin - v1.0 - 08/01/2013
* https://github.com/stdevteam/jquery-colorselector
* Copyright (c) 2013 STDev (http://st-dev.com); Licensed MIT */
(function($) {
    
    var counter = 0;
    //
    // plugin definition
    //
    $.fn.colorbox = function(options, functionValue) {
        //allow for passing function names instead of params
        if(options === 'setColor'){
            return this.each(function() {
                $.fn.colorbox.setColor($(this), functionValue);
            })
        }else if(options === 'setColorIndex'){
            return this.each(function() {
                $.fn.colorbox.setColorIndex($(this), functionValue);
            });
        }else{
            // build main options before element iteration
            var opts = $.extend({}, $.fn.colorbox.defaults, options);
			
			//check for indexes
			if(opts.colors.length !== opts.indexes.length){
				opts.indexes = [];
				for(var j in opts.colors){
					opts.indexes.push(parseInt(j));
				}
			}
			
            counter = counter+1;
            // iterate and reformat each matched element
            return this.each(function() {
                var $this = $(this);

                if(opts.labels.length < 1){
                    alert('At least one box need to be provided');
                    return false;
                }
                if(
                    opts.labels.length !== opts.colors.length ||
                    opts.labels.length !== opts.indexes.length ||
                    opts.colors.length !== opts.indexes.length
                ){
                    alert('Labels and Colors need to have equal length');
                    return false;
                }

                var $colobox = $("<div />").addClass('colorbox-cont')
                    .css({
                        'width': parseInt(opts.perLine*opts.width)+'px',
                        'position': 'absolute',
                        'display' : 'none',
                        'background-color': '#fff',
                        'border': '1px solid',
                        'padding': '2px',
                        'z-index': 255,
                });

                for(var i in opts.labels){
                    $colobox.append(
                        $("<div />").attr('id', 'box-'+counter+'_'+opts.indexes[i])
                            .addClass('box-item')
                            .css({
                                'width': opts.width+'px', 
                                'height': opts.height+'px',
                                'backgroundColor': '#'+opts.colors[i],
                                'float': 'left',
                                'cursor': 'pointer'
                            })
                            .attr('title', opts.labels[i])
                            .data('details', {
                                index: opts.indexes[i],
                                label: opts.labels[i],
                                color: opts.colors[i],
                                holder: $this
                            })
                            .click(function(){
                                $(this).parent().hide('fast');
                                var data = $(this).data('details');
                                //set the background color
                                data.holder.css({
                                    'backgroundColor': '#'+data.color
                                });
                                if(typeof opts.onSelect == 'function'){
                                    opts.onSelect($(this), data.color, data.index, data.label);
                                }
                        })
                    );
                }

                $(this).parent().append($colobox);

                $(this).click(function(){
                    var $colorbox = $(this).siblings().find('.colorbox-cont');
                    if($colobox.is(':visible')){
                        $colobox.hide('fast');
                    }else{
                        $colobox.show('fast');
                    }
                });
                //save options for later use
                $(this).data('options', opts);
            });
        }
    };
    //
    // define and expose our set color function
    //
    $.fn.colorbox.setColor = function($this, color){
        $this.css({'backgroundColor': '#'+color});
    };
    /**
     * Our set color by index function 
     * @param {Object} $this jQuery object that we deal with
     * @param {Number} index The color index to apply
     */
    $.fn.colorbox.setColorIndex = function($this, index){
        var fetchedOptions = $this.data('options'),colorIndex,color;
        if(typeof fetchedOptions == 'object'){
            colorIndex = jQuery.inArray(index, fetchedOptions.indexes);
            if(colorIndex > -1){
                color = fetchedOptions.colors[colorIndex];
                $this.css({'backgroundColor': '#'+color});
            }
        }
    };
    //
    // plugin defaults
    //
    $.fn.colorbox.defaults = {
        labels: [
            'delete', 'black', 'red', 'green', 'darkgreen', 'orange', 'blue', 'purple',
        ],
        colors: [
            '', '000000', 'EBCECE', 'D4FF91', '8FBC8F', 'FFA500', 'C4E0FF', 'D6C2FC',
        ],
        indexes: [0],
        width: 20,
        height: 20,
        perLine: 4,
        onSelect: $.noop
    };
    //
    // end of closure
    //
})(jQuery);