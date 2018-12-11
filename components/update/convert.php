<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../common.php');
require_once('../settings/class.settings.php');
require_once('../project/class.project.php');
require_once('../user/class.user.php');

checkSession();
if ( ! checkAccess() ) {
	echo "Error, you do not have access to update Codiad.";
	exit;
}

$user_settings_file = DATA . "/settings.php";
$projects_file = DATA . "/projects.php";
$users_file = DATA . "/users.php";

$system_settings_file = null;
$Settings = new Settings();
$Common = new Common();
$Project = new Project();
$User = new User();

