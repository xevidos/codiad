<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../common.php');
require_once('./class.update.php');

$user_settings_file = DATA . "/settings.php";
$projects_file = DATA . "/projects.php";
$users_file = DATA . "/users.php";
//checkSession();
if ( ! checkAccess() ) {
	echo "Error, you do not have access to update Codiad.";
	exit();
}

if ( ( file_exists( $user_settings_file ) || file_exists( $projects_file ) || file_exists( $users_file ) ) || ! ( defined( "DBHOST" ) && defined( "DBNAME" ) && defined( "DBUSER" ) && defined( "DBPASS" ) && defined( "DBTYPE" ) ) ) {
	
	?>
	<p>
		Hello,  this update requires new variables in your Codiad config.php<br />
		Please place the following code in your config.php with the correct values applying to your databse and then reload this page. <br>
		<br>
		Please be aware that at the moment, only mysql databases are supported.  However, more database support is planned.
	</p>
	
	<code>
<pre>
define( "DBHOST", "localhost" );
define( "DBNAME", "database" );
define( "DBUSER", "username" );
define( "DBPASS", "password" );
define( "DBTYPE", "mysql" );
</pre>
	</code>
	<?php
	exit();
}

/**
 * Initiate the update class so we do not have to redefine their
 * variables.
 */

class updater {
	
	/**
	 * Telaaedifex Codiad updater
	 * 
	 * This updater will extract an archive and then update each file
	 * with file put contents.
	 */
	 
	/**
	 * Constants
	 */
	 
	/**
	 * Properties
	 */
	 
	public $archive = "";
	public $path = "";
	public $protocol = "";
	public $update = null;
	
	function __construct() {
		
		$this->update = new Update();
		$this->protocol = $this->check_protocol();
		$this->archive = $this->update->archive;
		$this->path = BASE_PATH;
		
		/*
		//Trigger update
		$this->update();*/
	}
	
	function check_protocol() {
		
		if( extension_loaded( 'curl' ) ) {
			
			//Curl is loaded
			return "curl";
		} elseif( ini_get('allow_url_fopen') ) {
			
			//Remote get file is enabled
			return "fopen";
		} else {
			
			//None are enabled exit.
			return "none";
		}
	}
	
	function check_update() {
		
		$response = $this->update->getRemoteVersion();
		$local_version = $this->update::VERSION;
		$remote_version = $response["name"];
		$return = "false";
		
		if( $local_version < $remote_version ) {
			
			$return = "true";
		}
		
		return( $return );
	}
	
	function check_version() {
		
		$local_version = $this->update::VERSION;
		$remote_version = $response["name"];
		$return = "false";
		
		if( $local_version <= "v.2.9.2" ) {
			
			$return = "convert";
		}
		
		return( $return );
	}
	
	function convert() {
		
		require_once('../settings/class.settings.php');
		require_once('../project/class.project.php');
		require_once('../user/class.user.php');
		$Settings = new Settings();
		$Project = new Project();
		$User = new User();
		$connection = $Settings->connect();
		
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
	$bind = "";
	$bind_param = array();
	$result = mysqli_prepare( $connection, $sql ) or die( $error );
	$result->bind_param( $bind, ...$bind_variables );
	$result->execute();
	
	if( $connection->error ) {
		
		$return = formatJSEND( "error", $connection->error );
	}
		
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
		
		if( file_exists( $users_file ) ) {
			
			$users = getJSON( 'users.php' );
			foreach( $users as $user ) {
				
				$User->username = $user["username"];
				$User->password = $user["password"];
				$User->add_user();
			}
			unlink( $users_file );
		}
	}
	
	function copyr( $source, $dest ) {
		// Check for symlinks
		if (is_link($source)) {
			return symlink(readlink($source), $dest);
		}
		
		// Simple copy for a file
		if (is_file($source)) {
			return copy($source, $dest);
		}
		
		// Make destination directory
		if (!is_dir($dest)) {
			mkdir($dest);
		}
		
		// Loop through the folder
		$dir = dir( $source );
		while (false !== $entry = $dir->read()) {
		// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue;
			}
			
			// Deep copy directories
			$this->copyr("$source/$entry", "$dest/$entry");
		}
		
		// Clean up
		$dir->close();
		return true;
	}
	
	//////////////////////////////////////////////////////////////////
	// Download latest archive
	//////////////////////////////////////////////////////////////////
	
	function download() {
		
		switch( $this->protocol ) {
			
			case( "curl" ):
				
				$filepath = $this->path . "/update.zip";
				if( file_exists( $filepath ) ) {
					unlink( $filepath );
				}
				$curl = curl_init();
				curl_setopt( $curl, CURLOPT_URL, $this->archive );
				//curl_setopt($curl, CURLOPT_POSTFIELDS, "");
				curl_setopt( $curl, CURLOPT_HEADER, 0 );
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );  
				curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13' );
				$raw_file_data = curl_exec( $curl );
				curl_close( $curl );
				file_put_contents( $filepath, $raw_file_data );
				if( filesize( $filepath ) > 0 ) {
					
					return( "true" );
				} else {
					
					return( "false" );
				}
			break;
		}
	}
	
	function extract() {
		
		if ( ! extension_loaded( 'zip' ) ) {
			
			return "false";
		}
		
		$zip = new ZipArchive;
		if ( $zip->open( $this->path . "/update.zip" ) === TRUE ) {
			
			$zip->extractTo( $this->path );
			$zip->close();
			
			return "true";
		} else {
			
			return "false";
		}
	}
	
	function remove_directory( $path ) {
		
		$files = glob($path . '/*');
		foreach ($files as $file) {
		
			is_dir($file) ? $this->remove_directory($file) : unlink($file);
		}
		
		if( is_dir( $path ) ) {
			
			rmdir( $path );
		}
		return;
	}
		
	function update() {
		
		$sessions = "../../data/sessions";
		//Add Sessions path if not there.
		
		/**
		 * Create sessions path.
		 */
		
		if ( ! is_dir( $sessions ) ) {
			
			mkdir( $sessions, 00755 );
		}
		
		/**
		 * If any directories in the array below are still set delete them.
		 * 
		 */
		$folder_conflictions = array(
			
			$this->path . "/plugins/auto_save",
			$this->path . "/plugins/Codiad-Auto-Save",
			$this->path . "/plugins/Codiad-Auto-Save-master",
			$this->path . "/plugins/Codiad-CodeSettings",
			$this->path . "/plugins/Codiad-CodeSettings-master",
		);
		
		foreach( $folder_conflictions as $dir ) {
			
			$this->remove_directory( $dir );
		}
		
		/**
		 * If any files in the array below are still set delete them.
		 * 
		 */
		 
		$file_conflictions = array(
			
			$this->path . "/.travis.yml",
			$this->path . "/codiad-master/.travis.yml",
		);
		
		foreach( $file_conflictions as $file ) {
			
			if( is_file( $file ) ) {
				
				unlink( $file );
			}
		}
		
		
		$src = $this->path . "/codiad-master/";
		$src_folder = $this->path . "/codiad-master";
		$dest = $this->path . "/";
		
		$this->copyr( $src, $dest );
		$this->remove_directory( $src );
		$this->convert();
		return( "true" );
	}
	
	function version() {
		
		$return = "";
		
		if( file_exists( $user_settings_file ) || file_exists( $projects_file ) || file_exists( $users_file ) ) {
			
			$return = "true";
		}
	}
}

if( isset( $_GET["action"] ) && $_GET["action"] !== '' ) {
	
	$updater = new updater();
	$action = $_GET["action"];
	
	switch( $action ) {
		
		case( "apply" ):
			
			echo $updater->update();
		break;
		
		case( "check_update" ):
			
			echo $updater->check_update();
		break;
		
		case( "check_version" ):
			
			echo $updater->check_version();
		break;
		
		case( "download" ):
			
			echo $updater->download();
		break;
		
		case( "extract" ):
			
			echo $updater->extract();
		break;
		
		case( "update" ):
			
			echo $updater->update();
		break;
	}
	
	exit();
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Codiad Update</title>
		<style>
			html {
				
			}
			
			body{
				
				background-color: #1a1a1a;
				color: #fff;
				font: normal 13px 'Ubuntu', sans-serif;
				height: 100%;
				overflow: hidden;
				text-align: center;
				width: 100%;
			}
			
			.title {
				
				color: #666;
				display: block;
				font-weight: 500;
				margin: 10px;
				text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
			}
			
			#convert {
				
				display: none;
			}
			
			#progress {
				
				position: fixed;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
			}
			
			
		</style>
		<script src="../../js/jquery-1.7.2.min.js"></script>
		<script>
			const codiad = {};
			codiad.update = {
				
				progress: null,
				
				init: function() {
					
					this.progress = document.getElementById( "progress" );
					this.update();
				},
				
				apply: function() {
					
					return jQuery.ajax({
							
						url: "update.php",
						type: "GET",
						dataType: 'html',
						data: {
							action: 'apply',
						},
						
						success: function( result ) {
							
							return result;
						},
						
						error: function( jqXHR, textStatus, errorThrown ) {
							
							console.log( 'jqXHR:' );
							console.log( jqXHR );
							console.log( 'textStatus:' );
							console.log( textStatus);
							console.log( 'errorThrown:' );
							console.log( errorThrown );
							return null;
						}
					});
				},
				
				check_update: function() {
					
					this.progress.innerText = "Checking for update ... ";
					return jQuery.ajax({
						
						url: "update.php",
						type: "GET",
						dataType: 'html',
						data: {
							action: 'check_update',
						},
						
						success: function( result ) {
							
							return result;
						},
						
						error: function( jqXHR, textStatus, errorThrown ) {
							
							console.log( 'jqXHR:' );
							console.log( jqXHR );
							console.log( 'textStatus:' );
							console.log( textStatus);
							console.log( 'errorThrown:' );
							console.log( errorThrown );
							return null;
						}
					});
				},
				
				download: function() {
					
					return jQuery.ajax({
							
						url: "update.php",
						type: "GET",
						dataType: 'html',
						data: {
							action: 'download',
						},
						
						success: function( result ) {
							
							return result;
						},
						
						error: function( jqXHR, textStatus, errorThrown ) {
							
							console.log( 'jqXHR:' );
							console.log( jqXHR );
							console.log( 'textStatus:' );
							console.log( textStatus);
							console.log( 'errorThrown:' );
							console.log( errorThrown );
							return null;
						}
					});
				},
				
				extract: function() {
					
					return jQuery.ajax({
							
						url: "update.php",
						type: "GET",
						dataType: 'html',
						data: {
							action: 'extract',
						},
						
						success: function( result ) {
							
							return result;
						},
						
						error: function( jqXHR, textStatus, errorThrown ) {
							
							console.log( 'jqXHR:' );
							console.log( jqXHR );
							console.log( 'textStatus:' );
							console.log( textStatus);
							console.log( 'errorThrown:' );
							console.log( errorThrown );
							return null;
						}
					});
				},
				
				update: async function() {
					
					let result = await this.check_update();
					
					console.log( result );
					if( result === "true" ) {
						
						progress.innerText = "An update was found.  Downloading update.";
						let download = await this.download();
						
						if( download !== "true" ) {
							
							console.log( download );
							progress.innerText = "Error downloading update.";
							return;
						}
						
						progress.innerText = "Extracting update.";
						let extract = await this.extract();
						
						if( extract !== "true" ) {
							
							console.log( extract );
							progress.innerText = "Error extracting update.";
							return;
						}
						
						progress.innerText = "Applying update.";
						let apply = await this.apply();
						
						if( apply !== "true" ) {
							
							console.log( apply );
							progress.innerText = "Error applying update.";
							return;
						}
						
						progress.innerText = "Update Finished.";
					} else if( result === "false" ) {
						
						progress.innerText = "No update was found ...";
					} else {
						
						progress.innerText = "Error, checking for updates failed.";
					}
				},
			};
		</script>
	</head>
	<body>
		<h1 class="title" style="text-align: center;">
			Tela Codiad Updater
		</h1>
		<div>
			<p>Do not leave this page until the process has finished.</p>
			<p id="progress"></p>
		</div>
		<script>
			codiad.update.init();
		</script>
	</body>
</html>
