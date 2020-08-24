( function( global, $ ) {
	
	// Define core
	let codiad = global.codiad,
	scripts = document.getElementsByTagName( 'script' ),
	path = scripts[scripts.length-1].src.split( '?' )[0],
	curpath = path.split( '/' ).slice( 0, -1 ).join( '/' ) + '/';
	
	$( document ).ready( function() {
		
		codiad.login.init();
	});
	
	codiad.login = {
		
		form: null,
		
		init: function() {
			
			console.log( codiad );
			this.load_styles();
			
			let d = {
				
				username: {
					
					default: "",
					label: "Username: ",
					name: "username",
					required: true,
					type: "text",
				},
				password: {
					
					default: "",
					label: "Password: ",
					name: "password",
					required: true,
					type: "text",
				},
			};
			this.form = new codiad.forms({
				data: d,
				container: $( "#container" ),
				submit_label: "Login",
			});
			this.form.submit = this.submit;
		},
		
		load_styles: function() {
			
			codiad.addThemeCSS( "jquery.toastmessage.css" );
			codiad.addThemeCSS( "reset.css" );
			codiad.addThemeCSS( "fonts.css" );
			codiad.addThemeCSS( "screen.css" );
			codiad.addThemeCSS( "forms/screen.css" );
		},
		
		submit: async function() {
			
			let _this = this;
			let submit = _this.v.controls.find( `[type="submit"]` );
			
			if( _this.saving ) {
				
				return;
			}
			
			_this.saving = true;
			submit.attr( "disabled", true );
			submit.text( "Submitting ..." );
			
			let data = await _this.m.get_values();
			//let response = await codiad.common.ajax( "./index.php", "POST", data );
			
			console.log( data );
			
			submit.attr( "disabled", true );
			submit.text( "Logging In ..." );
			
			submit.text( _this.submit_label );
			submit.attr( "disabled", false );
			_this.saving = false;
		},
	};
})( self, jQuery );