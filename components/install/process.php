<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), Isaac Brown (telaaedifex.com),
*  distributed as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

//////////////////////////////////////////////////////////////////////
// Paths
//////////////////////////////////////////////////////////////////////

$path = $_POST['path'];

$rel = str_replace( '/components/install/process.php', '', $_SERVER['REQUEST_URI'] );

$workspace = $path . "/workspace";
$users = $path . "/data/users.php";
$projects = $path . "/data/projects.php";
$active = $path . "/data/active.php";
$sessions = $path . "/data/sessions";
$config = $path . "/config.php";

//////////////////////////////////////////////////////////////////////
// Functions
//////////////////////////////////////////////////////////////////////

function saveFile( $file, $data ) {
	
	$write = fopen( $file, 'w' ) or die( "can't open file" );
	fwrite( $write, $data );
	fclose( $write );
}

function saveJSON( $file, $data ) {
	
	$data = "<?php/*|\r\n" . json_encode( $data ) . "\r\n|*/?>";
	saveFile( $file, $data );
}

function encryptPassword( $p ) {
	
	return sha1( md5( $p ) );
}

function cleanUsername( $username ) {
	
	return preg_replace( '#[^A-Za-z0-9' . preg_quote( '-_@. ' ).  ']#', '', $username );
}

function isAbsPath( $path ) {
	
	return $path[0] === '/';
}

function cleanPath( $path ) {
	
	// prevent Poison Null Byte injections
	$path = str_replace( chr( 0 ), '', $path );
	
	// prevent go out of the workspace
	while ( strpos( $path, '../' ) !== false ) {
		
		$path = str_replace( '../', '', $path );
	}
	return $path;
}

//////////////////////////////////////////////////////////////////////
// Verify no overwrites
//////////////////////////////////////////////////////////////////////

if ( ! ( defined( "DBHOST" ) && defined( "DBNAME" ) && defined( "DBUSER" ) && defined( "DBPASS" ) && defined( "DBTYPE" ) ) ) {
	
	//////////////////////////////////////////////////////////////////
	// Get POST responses
	//////////////////////////////////////////////////////////////////
	
	$username = cleanUsername( $_POST['username'] );
	$password = encryptPassword( $_POST['password'] );
	$project_name = $_POST['project_name'];
	if ( isset( $_POST['project_path'] ) ) {
	
		$project_path = $_POST['project_path'];
	} else {
		
		$project_path = $project_name;
	}
	$timezone = $_POST['timezone'];
	
	$dbhost = $_POST['dbhost'];
	$dbname = $_POST['dbname'];
	$dbuser = $_POST['dbuser'];
	$dbpass = $_POST['dbpass'];
	
	$connection = mysqli_connect( $dbhost, $dbuser, $dbpass, $dbname ) or die ( 'Error connecting to mysql database.  Please contact the website administrator.' );
	$bind_vars = array();
	$bind = "";
	$sql = "
-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 11, 2018 at 05:31 PM
-- Server version: 5.7.24-0ubuntu0.18.04.1
-- PHP Version: 7.2.10-0ubuntu0.18.04.1

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `code_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE IF NOT EXISTS `options` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `access` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `project` varchar(255) DEFAULT NULL,
  `access` varchar(255) NOT NULL,
  `groups` text,
  `token` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `user_options`
--

CREATE TABLE IF NOT EXISTS `user_options` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `option_name` (`name`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_path` (`path`,`owner`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_options`
--
ALTER TABLE `user_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `option_name` (`name`,`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;
--
-- AUTO_INCREMENT for table `user_options`
--
ALTER TABLE `user_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2541;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
";
	$result = mysqli_multi_query( $connection, $sql ) or die( "Error creating tables." );
	
	
	
	//////////////////////////////////////////////////////////////////
	// Create Projects files
	//////////////////////////////////////////////////////////////////
	
	$project_path = cleanPath( $project_path );
	
	if ( ! isAbsPath( $project_path ) ) {
	
		$project_path = str_replace( " ", "_", preg_replace( '/[^\w-\.]/', '', $project_path ) );
		if( ! is_dir( $workspace . "/" . $project_path ) ) {
			
			mkdir( $workspace . "/" . $project_path );
		}
	} else {
		
		$project_path = cleanPath( $project_path );
		if ( substr( $project_path, -1 ) == '/' ) {
			
			$project_path = substr( $project_path, 0, strlen( $project_path ) - 1 );
		}
		if ( ! file_exists( $project_path ) ) {
			
			if ( ! mkdir( $project_path . '/', 0755, true ) ) {
				
				die( "Unable to create Absolute Path" );
			}
		} else {
			
			if ( ! is_writable( $project_path ) || ! is_readable( $project_path ) ) {
				
				die( "No Read/Write Permission" );
			}
		}
	}
	
	$connection = mysqli_connect( $dbhost, $dbuser, $dbpass, $dbname ) or die ( 'Error connecting to mysql database.  Please contact the website administrator.' );
	$bind_vars = array(
		$project_name,
		$project_path,
		$username
	);
	$bind = "sss";
	$sql = "INSERT INTO `projects`(`name`, `path`, `owner`) VALUES (?,?,?);";
	$result = mysqli_prepare( $connection, $sql ) or die( "Error inserting into projects." );
	$result->bind_param( $bind, ...$bind_vars );
	$result->execute();
	
	$bind_vars = array(
		"",
		"",
		$username,
		$password,
		"",
		$project_path,
		"admin",
		"",
		""
	);
	$bind = "sssssssss";
	$sql = "INSERT INTO `users`(`first_name`, `last_name`, `username`, `password`, `email`, `project`, `access`, `groups`, `token`) VALUES (?,?,?,PASSWORD(?),?,?,?,?,?)";
	$result = mysqli_prepare( $connection, $sql ) or die( "Error inserting into users." );
	$result->bind_param( $bind, ...$bind_vars );
	$result->execute();
	
	
	
	/**
	* Create sessions path.
	*/
	
	if ( ! is_dir( $sessions ) ) {
	
		mkdir( $sessions, 00755 );
	}
	
	//////////////////////////////////////////////////////////////////
	// Create Active file
	//////////////////////////////////////////////////////////////////
	
	saveJSON($active, array(''));
	
	//////////////////////////////////////////////////////////////////
	// Create Config
	//////////////////////////////////////////////////////////////////
	
	
	$config_data = '<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), Isaac Brown (telaaedifex.com),
*  distributed as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

//////////////////////////////////////////////////////////////////
// CONFIG
//////////////////////////////////////////////////////////////////

// PATH TO CODIAD
define("BASE_PATH", "' . $path . '");

// BASE URL TO CODIAD (without trailing slash)
define("BASE_URL", "' . $_SERVER["HTTP_HOST"] . $rel . '");

// THEME : default, modern or clear (look at /themes)
define("THEME", "default");

// ABSOLUTE PATH
define("WHITEPATHS", BASE_PATH . ",/home");

// SESSIONS (e.g. 7200)
$cookie_lifetime = "0";

// TIMEZONE
date_default_timezone_set("' . $_POST['timezone'] . '");

// External Authentification
//define("AUTH_PATH", "/path/to/customauth.php");

// Site Name
define("SITE_NAME", "' . $_POST['site_name'] . '");

// Database Information
define( "DBHOST", "' . $_POST['dbhost'] . '" );
define( "DBNAME", "' . $_POST['dbname'] . '" );
define( "DBUSER", "' . $_POST['dbuser'] . '" );
define( "DBPASS", "' . $_POST['dbpass'] . '" );
define( "DBTYPE", "mysql" );

//////////////////////////////////////////////////////////////////
// ** DO NOT EDIT CONFIG BELOW **
//////////////////////////////////////////////////////////////////

// PATHS
define("COMPONENTS", BASE_PATH . "/components");
define("PLUGINS", BASE_PATH . "/plugins");
define("THEMES", BASE_PATH . "/themes");
define("DATA", BASE_PATH . "/data");
define("WORKSPACE", BASE_PATH . "/workspace");

// URLS
define("WSURL", BASE_URL . "/workspace");

// Marketplace
//define("MARKETURL", "http://market.codiad.com/json");
';
	
	saveFile( $config, $config_data );
	echo( "success" );
}