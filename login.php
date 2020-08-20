<?php

require_once( __DIR__ . "/components/initialize/class.initialize.php" );

Initialize::get_instance();

$components = scandir( COMPONENTS );

unset( $components["."], $components[".."] );

// Theme
$theme = THEME;
if( isset( $_SESSION['theme'] ) ) {
	
	$theme = $_SESSION['theme'];
}

?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="
			width=device-width,
			initial-scale=1.0,
			maximum-scale=1.0,
			user-scalable=no">
		<title><?php echo SITE_NAME;?></title>
		<?php
		// Load System CSS Files
		$stylesheets = array(
			"jquery.toastmessage.css",
			"reset.css",
			"fonts.css",
			"screen.css"
		);
		
		foreach( $stylesheets as $sheet ) {
			
			if( file_exists( THEMES . "/". $theme . "/" . $sheet ) ) {
				
				echo( '<link rel="stylesheet" href="themes/' . $theme . '/' . $sheet . '?v=' . Update::get_version() . '">' );
			} else {
				
				echo( '<link rel="stylesheet" href="themes/default/' . $sheet . '?v=' . Update::get_version() . '">' );
			}
		}
		
		// Load Component CSS Files    
		foreach( $components as $component ) {
			
			if( file_exists( THEMES . "/". $theme . "/" . $component . "/screen.css" ) ) {
				
				echo( '<link rel="stylesheet" href="themes/' . $theme . '/' . $component . '/screen.css?v=' . Update::get_version() . '">' );
			} else {
				
				if( file_exists( "themes/default/" . $component . "/screen.css" ) ) {
					
					echo( '<link rel="stylesheet" href="themes/default/' . $component . '/screen.css?v=' . Update::get_version() . '">' );
				} else {
					
					if( file_exists( COMPONENTS . "/" . $component . "/screen.css" ) ) {
						
						echo( '<link rel="stylesheet" href="components/' . $component . '/screen.css?v=' . Update::get_version() . '">' );
					}
				}
			}
		}
		
		if( file_exists( THEMES . "/". $theme . "/favicon.ico" ) ) {
			
			echo( '<link rel="icon" href="' . THEMES . '/' . $theme . '/favicon.ico" type="image/x-icon" />' );
		} else {
			
			echo( '<link rel="icon" href="assets/images/favicon.ico" type="image/x-icon" />' );
		}
		?>
		<style>
			
			#container {
				
				overflow-y: auto;
				position: fixed;
				right: 50%;
				top: 50%;
				transform: translate( 50%,-50% );
				width: 50%;
			}
			
			@media only screen and (max-width: 650px) {
				
				#container {
					
					width: 80%;
				}
			}
		</style>
		<script src="./assets/js/jquery-3.5.1.js"></script>
		<script src="./assets/js/jquery.toastmessage.js"></script>
		<script src="./assets/js/codiad.js"></script>
		<script src="./assets/js/message.js"></script>
		<script src="./assets/js/events.js"></script>
		<script src="./assets/js/loading.js"></script>
		<script src="./assets/js/common.js"></script>
		<script src="./assets/js/forms.js"></script>
	</head>
	<body>
		<div id="container"></div>
		<script>
			
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
						
						let _ = this;
						
						this.d = {
							
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
							data: _.d,
							container: $( "#container" ),
							submit_label: "Login",
						});
						this.form.submit
					},
					
					submit: function() {
						
						let _this = this;

						if( _this.saving ) {
							
							return;
						}
						
						_this.saving = true;
						
						submit.attr( "disabled", true );
						submit.text( "Logging In ..." );
						
						submit.text( _this.submit_label );
						submit.attr( "disabled", false );
						_this.saving = false;
					},
				};
			})( this, jQuery );
		</script>
	</body>
</html>