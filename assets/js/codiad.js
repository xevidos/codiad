( function( global, $ ) {
	
	var codiad = global.codiad = {
		
		theme: global.theme,
		themes: global.themes,
		
		init: function() {},
		
		addCSS: function( url, container ) {
			
			console.log( url, container );
			return container.append( `<link rel="stylesheet" href="${url}" />` );
		},
		
		addThemeCSS: function( file ) {
			
			return this.addCSS( `themes/${this.theme}/${file}`, $( 'body' ) );
		},
	};
})( this, jQuery );