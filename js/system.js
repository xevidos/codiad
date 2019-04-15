/*
 *  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
 *  as-is and without warranty under the MIT License. See
 *  [root]/license.txt for more. This information must remain intact.
 */

(function(global, $){

    var codiad = global.codiad = {};

    //////////////////////////////////////////////////////////////////////
    // loadScript instead of getScript (checks and balances and shit...)
    //////////////////////////////////////////////////////////////////////

    $.loadScript = function(url, arg1, arg2) {
        var cache = true,
            callback = null;
        //arg1 and arg2 can be interchangable
        if ($.isFunction(arg1)) {
            callback = arg1;
            cache = arg2 || cache;
        } else {
            cache = arg1 || cache;
            callback = arg2 || callback;
        }

        var load = true;
        //check all existing script tags in the page for the url
        jQuery('script[type="text/javascript"]')
            .each(function() {
            return load = (url != $(this)
                .attr('src'));
        });
        if (load) {
            //didn't find it in the page, so load it
            jQuery.ajax({
                type: 'GET',
                url: url,
                success: callback,
                dataType: 'script',
                cache: cache
            });
        } else {
            //already loaded so just call the callback
            if (jQuery.isFunction(callback)) {
                callback.call(this);
            };
        };
    };

    //////////////////////////////////////////////////////////////////////
    // Init
    //////////////////////////////////////////////////////////////////////

    $(function() {
        // Console fix for IE
        if (typeof(console) === 'undefined') {
            console = {}
            console.log = console.error = console.info = console.debug = console.warn = console.trace = console.dir = console.dirxml = console.group = console.groupEnd = console.time = console.timeEnd = console.assert = console.profile = function () {};
        }        
        
        // Sliding sidebars
        codiad.sidebars.init();
        var handleWidth = 10;
        
        // Messages
        codiad.message.init();

        $(window)
            .on('load resize', function() {

            var marginL, reduction;
            if ($("#sb-left")
                .css('left') !== 0 && !codiad.sidebars.leftLock) {
                marginL = handleWidth;
                reduction = 2 * handleWidth;
            } else {
                marginL = $("#sb-left")
                    .width();
                reduction = marginL + handleWidth;
            }
            $('#editor-region')
                .css({
                'margin-left': marginL + 'px',
                'height': ($('body')
                    .outerHeight()) + 'px'
            });
            $('#root-editor-wrapper')
                .css({
                'height': ($('body')
                    .outerHeight() - 60) + 'px' // TODO Adjust '75' in function of the final tabs height.
            });

            // Run resize command to fix render issues
            // Add a check to see if it is not undefined due to an
            // error being generated on the login page.
            if ( codiad.editor !== undefined && codiad.editor !== null ) {
            	
            	codiad.editor.resize();
            	codiad.active.updateTabDropdownVisibility();
            }
        });

        $('#settings').click(function(){
            codiad.settings.show();
        });
    });
    
    $(function() {
		
		codiad.system.init();
	});
	
	codiad.system = {
		
		controller: 'components/system/controller.php',
		session_id: '',
		site_id: '',
		
		init: function() {
			
			let _this = this;
			
		},
		
		create_default_tables: function() {
			
			jQuery.ajax({
					
				url: this.controller,
				type: "POST",
				dataType: 'html',
				data: {
					action: 'create_default_tables'
				},
				success: function( data ) {
					
					let response = codiad.jsend.parse( data );
					
					if( response.status != 'error' ) {
						
						codiad.message.success( i18n( 'Created Default Tables' ) );
					} else {
						
						codiad.message.error( i18n( 'Error Creating Default Tables' ) );
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					
					codiad.message.error( i18n( 'Error Creating Default Tables' ) );
					console.log('jqXHR:');
					console.log(jqXHR);
					console.log('textStatus:');
					console.log(textStatus);
					console.log('errorThrown:');
					console.log(errorThrown);
				},
			});
		},
	};

})(this, jQuery);

