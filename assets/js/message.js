( function( global, $ ) {

	// Define core
	let codiad = global.codiad,
	scripts = document.getElementsByTagName( 'script' ),
	path = scripts[scripts.length-1].src.split( '?' )[0],
	curpath = path.split( '/' ).slice( 0, -1 ).join( '/' ) + '/';
	
	//////////////////////////////////////////////////////////////////////
	// User Alerts / Messages
	//////////////////////////////////////////////////////////////////////
	
	codiad.message = {
		
		init: function() {},
		
		show_message: function( toastType, message, options ) {
			
			options = options || {};
			options.text = message;
			options.type = toastType
			$().toastmessage( 'showToast', options );
		},
		
		success: function( m, options ) {
			
			this.show_message( 'success', m, options );
		},
		
		error: function( m, options ) {
			
			this.show_message( 'error', m, options );
		},
		
		warning: function( m, options ) {
			
			this.show_message( 'warning', m, options );
		},
		
		notice: function( m, options ) {
			
			this.show_message( 'notice', m, options );
		},
		
		hide: function() {
			
			$(".toast-item-wrapper").remove();
		}
	};

})( this, jQuery );
