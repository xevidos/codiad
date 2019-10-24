( function( global, $ ) {

	let codiad = global.codiad;
	
	//////////////////////////////////////////////////////////////////////
	// Parse JSEND Formatted Returns
	//////////////////////////////////////////////////////////////////////
	
	codiad.jsend = {
		
		parse: function( d ) {
		
			// (Data)
			let obj = $.parseJSON( d );
			
			if ( obj === undefined || obj === null ) {
				
				return 'error';
			}
			
			if ( obj !== undefined && obj !== null && Array.isArray( obj.debug ) ) {
				
				var debug = obj.debug.join('\nDEBUG: ');
				if( debug !== '' ) {
					
					debug = 'DEBUG: ' + debug;
				}
				console.log( debug );
			}
			
			if ( obj.status == 'error' ) {
				
				codiad.message.error( obj.message );
				return 'error';
			} else if( obj.status == 'warning' ) {
				
				codiad.message.warning( obj.message );
				return 'warning';
			} else if( obj.status == 'notice' ) {
				
				codiad.message.notice( obj.message );
				return 'notice';
			} else {
				
				return obj.data;
			}
		}
	};
})(this, jQuery);
