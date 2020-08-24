<?php

require_once( __DIR__ . "/components/initialize/class.initialize.php" );

Initialize::get_instance();
$valid_session = Authentication::check_session();
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
		<script>
			
			var theme = `<?php echo $theme;?>`;
			var themes = `<?php echo THEMES;?>`;
		</script>
		<script src="./assets/js/jquery-3.5.1.js" defer></script>
		<script src="./assets/js/jquery.toastmessage.js" defer></script>
		<script src="./assets/js/codiad.js" defer></script>
		<script src="./assets/js/message.js" defer></script>
		<script src="./assets/js/events.js" defer></script>
		<script src="./assets/js/loading.js" defer></script>
		<script src="./assets/js/common.js" defer></script>
		<script src="./assets/js/forms.js" defer></script>
		<?php
		
		if( file_exists( THEMES . "/". $theme . "/favicon.ico" ) ) {
			
			echo( '<link rel="icon" href="' . THEMES . '/' . $theme . '/favicon.ico" type="image/x-icon" />' );
		} else {
			
			echo( '<link rel="icon" href="./assets/images/favicon.ico" type="image/x-icon" />' );
		}
		
		if( $valid_session ) {
			
			echo( '<script src="./assets/js/initialize.js" defer></script>' );
		} else {
			
			echo( '<script src="./assets/js/login.js" defer></script>' );
		}
		?>
	</head>
	<body>
		<div id="container">
			<div class="lds-container">
				<div class="lds-dual-ring"></div>
			</div>
		</div>
		<style>
			
			html, body {
				
				background-color: #1a1a1a;
				color: #fff;
				font: normal 13px 'Ubuntu', sans-serif;
				height: 100%;
				margin: 0;
				overflow: hidden;
				width: 100%;
			}
			
			#container {
				
				overflow-y: auto;
				position: fixed;
				right: 50%;
				text-align: left;
				top: 50%;
				transform: translate( 50%,-50% );
				width: 25%;
			}
			
			.lds-container {
				
				text-align: center;
			}
			.lds-dual-ring {
				
				display: inline-block;
				height: 90px;
				width: 90px;
			}
			.lds-dual-ring:after {
				
				content: " ";
				display: block;
				width: 64px;
				height: 64px;
				margin: 0;
				border-radius: 50%;
				border: 6px solid #fff;
				border-color: #fff transparent #fff transparent;
				animation: lds-dual-ring 1.2s linear infinite;
			}
			
			@media only screen and (max-width: 850px) {
				
				#container {
					
					width: 50%;
				}
			}
			
			@media only screen and (max-width: 650px) {
				
				#container {
					
					width: 75%;
				}
			}
			
			@keyframes lds-dual-ring {
				
				0% {
					
					transform: rotate(0deg);
				}
				100% {
					
					transform: rotate(360deg);
				}
			}
		</style>
	</body>
</html>