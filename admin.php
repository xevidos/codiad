<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once( 'common.php' );

if( ! isset( $_SESSION["token"] ) || ! isset( $_SESSION["user"] ) ) {
	
	header( 'Location: index.php' );
	exit();
}

checkSession();

if( ! checkAccess() ) {
	
	?>
	<p>
		Error, you do not have access to the administration page.
	</p>
	<?php
	return;
}

// Read Components, Plugins, Themes
$admin_components = Common::readDirectory( __DIR__ . "/admin/components" );
$components = Common::readDirectory(COMPONENTS);
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
<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo htmlentities( $site_name ); ?></title>
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
		
		$admin_stylesheets = array(
			"admin/screen.css"
		);
		
		foreach( $admin_stylesheets as $sheet ) {
			
			if( file_exists( THEMES . "/" . $theme . "/" . $sheet ) ) {
				
				echo( '<link rel="stylesheet" href="themes/' . $theme . '/' . $sheet . '">' );
			} else {
				
				echo( '<link rel="stylesheet" href="themes/default/' . $sheet . '">' );
			}
		}
		?>
		<script>
			codiad = {};
		</script>
	</head>
	<body>
		<script>
			var i18n = (function(lang) {
			return function(word,args) {
			var x;
			var returnw = (word in lang) ? lang[word] : word;
			for(x in args){
			returnw=returnw.replace("%{"+x+"}%",args[x]);   
			}
			return returnw;
			}
			})(<?php echo json_encode($lang); ?>)
		</script>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
		<script>!window.jQuery && document.write(unescape('%3Cscript src="js/jquery-1.7.2.min.js"%3E%3C/script%3E'));</script>
		<script src="js/jquery-ui-1.8.23.custom.min.js"></script>
		<script src="js/jquery.css3.min.js"></script>
		<script src="js/jquery.easing.js"></script>
		<script src="js/jquery.toastmessage.js"></script>
		<script src="js/jquery.ui.touch-punch.min.js"></script>
		<script src="js/amplify.min.js"></script>
		<script src="js/localstorage.js"></script>
		<script src="js/jquery.hoverIntent.min.js"></script>
		<script src="js/system.js"></script>
		<script src="js/sidebars.js"></script>
		<script src="js/modal.js"></script>
		<script src="js/message.js"></script>
		<script src="js/jsend.js"></script>
		<script src="js/instance.js?v=<?php echo time(); ?>"></script>
		<script src="admin/js/admin.js"></script>
		<div id="message"></div>
		<a class="mobile_menu_trigger">&#9776;</a>
		<section class="container">
			<div class="sidebar" id="sidebar">
				<div class="sidebar_menu">
					<div class="mobile_menu_close"><a class="sidebar_option" style="text-align: right;padding-right: 5%;">&times;</a></div>
					<div class="sidebar_option option_selected"><a class="sidebar_option">Dashboard</a></div>
				</div>
			</div>
			<div class="content">
				<p>testig</p>
			</div>
		</section>
		<?php
		// JS
		foreach( $admin_components as $component ) {
			
			if( file_exists( __DIR__ . "/admin/components" . "/" . $component . "/init.js" ) ) {
				
				echo( '<script src="admin/components/' . $component . '/init.js"></script>"' );
			}
		}
		?>
	</body>
</html>