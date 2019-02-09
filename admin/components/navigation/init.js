( function( global, $ ) {
	
	// Define core
	let codiad = global.codiad,
	scripts = document.getElementsByTagName( 'script' ),
	path = scripts[scripts.length-1].src.split( '?' )[0],
	curpath = path.split( '/' ).slice( 0, -1 ).join( '/' ) + '/';
	
	$( function() {
		
		codiad.admin.navigation.init();
	});
	
	codiad.admin.navigation = {
		
		path: curpath,
		
		init() {
			
			this.add_listeners();
		},
		
		add_listeners() {
			
			let _this = codiad.admin.navigation;
			$( ".mobile_menu_close" ).on( "click", _this.close_nav );
			$( ".mobile_menu_trigger" ).on( "click", _this.trigger_nav );
		},
		
		close_nav() {
			
			$( "#sidebar" ).css( "width", "0px" );
			$( ".container .content" ).off( "click", _this.close_nav );
		},
		
		open_nav() {
			
			$( "#sidebar" ).css( "width", "250px" );
			$( ".container .content" ).on( "click", _this.close_nav );
		},
		
		trigger_nav() {
			
			let _this = codiad.admin.navigation;
			let width = $( "#sidebar" ).width();
			
			$( ".content" ).html( width )
			
			if( width > 0 ) {
				
				_this.close_nav();
			} else {
				
				_this.open_nav();
			}
		}
	};
})( this, jQuery );
