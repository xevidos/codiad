( function( global, $ ) {
	
	// Define core
	let codiad = global.codiad,
	scripts = document.getElementsByTagName( 'script' ),
	path = scripts[scripts.length-1].src.split( '?' )[0],
	curpath = path.split( '/' ).slice( 0, -1 ).join( '/' ) + '/';
	
	$( function() {
		
		codiad.PLUGIN_NAME.init();
	});
	
	codiad.PLUGIN_NAME = {
		
		init: function() {},
	};
})( this, jQuery );