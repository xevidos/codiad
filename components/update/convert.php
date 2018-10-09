<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../common.php');
require_once('../settings/class.settings.php');

checkSession();
if ( ! checkAccess() ) {
	echo "Error, you do not have access to update Codiad.";
	exit;
}

$user_settings_file = DATA . "/settings.php";
$system_settings_file = null;
$Settings = new Settings();

if( file_exists( $user_settings_file ) ) {
	
	$user_settings = getJSON( 'settings.php' );
	foreach( $user_settings as $user => $settings ) {
		
		$Settings->username = $user;
		foreach( $settings as $setting => $value ) {
			
			//echo var_dump( $setting, $value );
			$Settings->add_option( $setting, $value, true );
		}
	}
	unlink( $user_settings_file );
}