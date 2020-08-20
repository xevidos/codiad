<?php

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

require_once( __DIR__ . "/components/initialize/class.initialize.php" );

Initialize::get_instance();
Authentication::check_token();

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
		
		// Load Plugin CSS Files
		foreach( $plugins as $plugin ) {
			
			if( file_exists( THEMES . "/". $theme . "/" . $plugin . "/screen.css" ) ) {
				
				echo( '<link rel="stylesheet" href="themes/' . $theme . '/' . $plugin . '/screen.css?v=' . Update::get_version() . '">' );
			} else {
				
				if( file_exists( "themes/default/" . $plugin . "/screen.css" ) ) {
					
					echo( '<link rel="stylesheet" href="themes/default/' . $plugin . '/screen.css?v=' . Update::get_version() . '">' );
				} else {
					
					if( file_exists( PLUGINS . "/" . $plugin . "/screen.css" ) ) {
						
						echo( '<link rel="stylesheet" href="plugins/' . $plugin . '/screen.css?v=' . Update::get_version() . '">' );
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
		
	</body>
</html>