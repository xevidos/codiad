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
			
			form {
				
				left: 50%;
				margin-left: -175px;
				padding: 35px;
				position: fixed;
				top: 30%;
				width: 350px;
			}
		</style>
	</head>
	<body>
		<form id="login" method="post">
			<label>
				<span class="icon-user login-icon"></span>
				<?php echo Common::i18n("Username");?>
				<input type="text" name="username" autofocus="autofocus" autocomplete="off">
			</label>
			<label>
				<span class="icon-lock login-icon"></span>
				<?php echo Common::i18n("Password");?>
				<input type="password" name="password">
				<span class="icon-eye in-field-icon-right hide_field">
			</label>
			<button><?php echo Common::i18n("Login");?></button>
		</form>
		<script>
			( function( global, $ ) {
				
				$( document ).ready( function() {
					
					$( ".hide_field" ).on( "click", function( e ) {
						
						let password = $( "input[name='password']" );
						
						if( password.attr( "type" ) == "password" ) {
							
							password.attr( "type", "text" );
						} else {
							
							password.attr( "type", "password" );
						}
					});
				});
			})( this, jQuery );
		</script>
	</body>
</html>