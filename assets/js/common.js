( function( global, $ ) {
	
	// Define core
	let codiad = global.codiad,
	scripts = document.getElementsByTagName( 'script' ),
	path = scripts[scripts.length-1].src.split( '?' )[0],
	curpath = path.split( '/' ).slice( 0, -1 ).join( '/' ) + '/';
	
	codiad.common = {
		
		ajax: function( url, type = "GET", data = {} ) {
			
			return new Promise( function( resolve, reject ) {
				
				$.ajax({
					url: url,
					type: type,
					data: data,
					success: function( result ) {
						
						resolve( result );
					},
					error: function( jqXHR, textStatus, errorThrown ) {
						
						console.log( 'jqXHR:' );
						console.log( jqXHR );
						console.log( 'textStatus:' );
						console.log( textStatus );
						console.log( 'errorThrown:' );
						console.log( errorThrown );
						reject( textStatus );
					}
				});
			});
		},
		
		call: function(  ) {
			
			return new Promise( function( resolve, reject ) {
				
				reject( false );
			});
		}
	};
})( this, jQuery );