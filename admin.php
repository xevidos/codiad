<?php
/**
 * Codiad admin module.
 * 
 * This admin module should provide a new way to install plugins / themes,
 * manage users, add permission levels,
 * 
 * Copyright (c) Codiad, Kent Safranski (codiad.com), and Isaac Brown (telaaedifex.com), distributed
 * as-is and without warranty under the MIT License. See
 * [root]/license.txt for more. This information must remain intact.
 *
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once( './common.php' );
require_once( './admin/assets/classes/initialize.php' );
new initialize();

// Read Components, Plugins, Themes
$components = Common::readDirectory( COMPONENTS );
$plugins = Common::readDirectory( PLUGINS );
$themes = Common::readDirectory( THEMES );

// Theme
$theme = THEME;
if( isset( $_SESSION['theme'] ) ) {
	
	$theme = $_SESSION['theme'];
}

// Get Site name if set
if( defined( "SITE_NAME" ) && ! ( SITE_NAME === "" || SITE_NAME === null ) ) {
	
	$site_name = SITE_NAME;
} else {
	
	$site_name = "Codiad";
}

?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo htmlentities( $site_name ); ?> - Admin</title>
		<?php
		// Load System CSS Files
		$stylesheets = array(
			"jquery.toastmessage.css",
			"reset.css",
			"fonts.css",
			"screen.css"
		);
		
		foreach( $stylesheets as $sheet ) {
			
			if( file_exists( THEMES . "/" . $theme . "/" . $sheet ) ) {
				
				echo( '<link rel="stylesheet" href="themes/' . $theme . '/' . $sheet . '">' );
			} else {
				
				echo( '<link rel="stylesheet" href="themes/default/' . $sheet . '">' );
			}
		}
		
		// Load Component CSS Files    
		foreach( $components as $component ) {
			
			if( file_exists( THEMES . "/". $theme . "/" . $component . "/screen.css" ) ) {
				
				echo( '<link rel="stylesheet" href="themes/' . $theme . '/' . $component . '/screen.css">' );
			} else {
					
				if( file_exists( "themes/default/" . $component . "/screen.css" ) ){
					
					echo( '<link rel="stylesheet" href="themes/default/' . $component . '/screen.css">' );
				} else {
					
					if( file_exists( COMPONENTS . "/" . $component . "/screen.css" ) ){
						
						echo( '<link rel="stylesheet" href="components/' . $component . '/screen.css">' );
					}
				}
			}
		}
		
		// Load Plugin CSS Files
		/*foreach( $plugins as $plugin ) {
			
			if( file_exists( THEMES . "/". $theme . "/" . $plugin . "/screen.css" ) ) {
				
				echo( '<link rel="stylesheet" href="themes/' . $theme . '/' . $plugin . '/screen.css">' );
			} else {
				
				if( file_exists( "themes/default/" . $plugin . "/screen.css" ) ){
					
					echo( '<link rel="stylesheet" href="themes/default/' . $plugin . '/screen.css">' );
				} else {
					
					if( file_exists( PLUGINS . "/" . $plugin . "/screen.css" ) ) {
						
						echo( '<link rel="stylesheet" href="plugins/' . $plugin . '/screen.css">' );
					}
				}
			}
		}*/
		?>
		<link rel="icon" href="favicon.ico" type="image/x-icon" />
		<script>
			var i18n = ( function( lang ) {
				
				return function( word, args ) {
					
					var x;
					var returnw = ( word in lang ) ? lang[word] : word;
					for( x in args ) {
						
						returnw = returnw.replace( "%{"+x+"}%", args[x] );   
					}
					return returnw;
				}
			})( <?php echo json_encode( $lang ); ?> )
		</script>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
		<script>!window.jQuery && document.write(unescape('%3Cscript src="js/jquery-1.7.2.min.js"%3E%3C/script%3E'));</script>
		<script src="js/jquery-ui-1.8.23.custom.min.js"></script>
		<script src="js/jquery.css3.min.js"></script>
		<script src="js/jquery.easing.js"></script>
		<script src="js/jquery.toastmessage.js"></script>
		<script src="js/amplify.min.js"></script>
		<script src="js/jquery.hoverIntent.min.js"></script>
		<script src="js/system.js"></script>
		<script src="js/sidebars.js"></script>
		<script src="js/modal.js"></script>
		<script src="js/message.js"></script>
		<script src="js/jsend.js"></script>
		<script src="js/instance.js?v=<?php echo time();?>"></script>
		<div id="message"></div>
	</head>
	<body>
		<!-- COMPONENTS -->
		<?php
		
		//////////////////////////////////////////////////////////////////
		// LOAD COMPONENTS
		//////////////////////////////////////////////////////////////////
		/*
		// JS
		foreach( $components as $component ) {
			
			if( file_exists( COMPONENTS . "/" . $component . "/init.js" ) ) {
				
				echo('<script src="components/' . $component . '/init.js"></script>');
			}
		}
		
		foreach( $plugins as $plugin ) {
			
			if( file_exists( PLUGINS . "/" . $plugin . "/init.js" ) ) {
				
				echo( '<script src="plugins/' . $plugin . '/init.js"></script>' );
			}
		}
		
		*/
		?>
	</body>
</html>