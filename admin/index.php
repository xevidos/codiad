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

require_once('../common.php');
require_once('./assets/classes/initialize.php');
new initialize();
?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo htmlentities( $site_name ); ?> - Admin</title>
		<?php
		// Load System CSS Files
		$stylesheets = array("jquery.toastmessage.css","reset.css","fonts.css","screen.css");
		
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
		foreach( $plugins as $plugin ) {
			
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
		}
		?>
		<link rel="icon" href="favicon.ico" type="image/x-icon" />
	</head>
	<body>
		<!-- COMPONENTS -->
		<?php
		
		//////////////////////////////////////////////////////////////////
		// LOAD COMPONENTS
		//////////////////////////////////////////////////////////////////
		
		// JS
		foreach( $components as $component ) {
			
			if( file_exists( COMPONENTS . "/" . $component . "/init.js" ) ) {
				
				echo('<script src="components/' . $component . '/init.js"></script>"');
			}
		}
		
		foreach( $plugins as $plugin ) {
			
			if( file_exists( PLUGINS . "/" . $plugin . "/init.js" ) ) {
				
				echo('<script src="plugins/' . $plugin . '/init.js"></script>"');
			}
		}
		
		
		?>
	</body>
</html>