<?php

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

require_once( __DIR__ . "/../components/initialize/class.initialize.php" );

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
				
				echo( '<link rel="stylesheet" href="../themes/' . $theme . '/' . $sheet . '?v=' . Update::get_version() . '">' );
			}
		}
		
		if( file_exists( THEMES . "/". $theme . "/favicon.ico" ) ) {
			
			echo( '<link rel="icon" href="' . THEMES . '/' . $theme . '/favicon.ico" type="image/x-icon" />' );
		} else {
			
			echo( '<link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon" />' );
		}
		?>
		<style>
			
			#installation {
				
				right: 50%;
				position: fixed;
				top: 30%;
				transform: translate( 50%,-50% );
				width: 50%;
			}
			
			@media only screen and (max-width: 650px) {
				
				#installation {
					
					width: 80%;
				}
			}
		</style>
		<script src="../assets/js/jquery-3.5.1.js"></script>
		<script src="../assets/js/codiad.js"></script>
		<script src="../assets/js/events.js"></script>
		<script src="../assets/js/loading.js"></script>
		<script src="../assets/js/common.js"></script>
		<script src="../assets/js/forms.js"></script>
	</head>
	<body>
		<div id="installation">
		</div>
		<script>
			( function( global, $ ) {
				
				$( document ).ready( function() {
					
					let dbconditions = {
						
						storage: {
							
							values: [
								{
									action: "hide",
									value: "filesystem",
								},
								{
									action: "show",
									value: "mysql",
								},
								{
									action: "show",
									value: "pgsql",
								}
							],
						},
					};
					
					let d = {
						
						permissions: {
							default: "false",
							element: $( '<pre>Checking ...</pre>' ),
							label: "Permission Checks: ",
							name: "permissions",
							type: "custom",
						},
						storage: {
							
							default: "true",
							element: $( '<select></select>' ),
							label: "Data Storage Method: ",
							name: "storage",
							options: {
								"Filesystem": "filesystem",
								"MySQL": "mysql",
								"PostgreSQL": "pgsql",
							},
							type: "select",
						},
						dbhost: {
							
							conditions: $.extend( true, {}, dbconditions ),
							default: "localhost",
							label: "Database Host: ",
							type: "text",
						},
						dbname: {
							
							conditions: $.extend( true, {}, dbconditions ),
							default: "",
							label: "Database Name: ",
							type: "text",
						},
						dbuser: {
							
							conditions: $.extend( true, {}, dbconditions ),
							default: "",
							label: "Database User: ",
							type: "text",
						},
						dbpass: {
							
							conditions: $.extend( true, {}, dbconditions ),
							default: "",
							label: "Database Password: ",
							type: "text",
						},
						dbpass1: {
							
							conditions: $.extend( true, {}, dbconditions ),
							default: "",
							label: "Repeat Password: ",
							type: "text",
						},
					};
					
					let form = new codiad.forms({
						data: d,
						container: $( "#installation" ),
						submit_label: "Check Data Storage Method",
					});
					form.submit = function() {
						
						
						console.log( "Submitted ..." );
					}
				});
			})( this, jQuery );
		</script>
	</body>
</html>