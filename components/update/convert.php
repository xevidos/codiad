<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../common.php');
require_once('../settings/class.settings.php');
require_once('../project/class.project.php');
checkSession();
if ( ! checkAccess() ) {
	echo "Error, you do not have access to update Codiad.";
	exit;
}

$user_settings_file = DATA . "/settings.php";
$projects_file = DATA . "/projects.php";
$projects_file = DATA . "/users.php";

$system_settings_file = null;
$Settings = new Settings();
$Common = new Common();
$Project = new Project();

if( file_exists( $user_settings_file ) ) {
	
	$user_settings = getJSON( 'settings.php' );
	foreach( $user_settings as $user => $settings ) {
		
		$Settings->username = $user;
		foreach( $settings as $setting => $value ) {
			
			$Settings->update_option( $setting, $value, true );
		}
	}
	unlink( $user_settings_file );
}

if( file_exists( $projects_file ) ) {
	
	$projects = getJSON( 'projects.php' );
	foreach( $projects as $project => $data ) {
		
		$Project->add_project( $data["name"], $data["path"], true );
	}
	unlink( $projects_file );
}