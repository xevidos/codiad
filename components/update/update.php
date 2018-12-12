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

if ( ! ( defined( "DBHOST" ) && defined( "DBNAME" ) && defined( "DBUSER" ) && defined( "DBPASS" ) && defined( "DBTYPE" ) ) ) {
	
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
	
	const DEFAULT_OPTIONS = array(
		array(
			"name" => "codiad.editor.autocomplete",
			"value" => "false",
		),
		array(
			"name" => "codiad.editor.fileManagerTrigger",
			"value" => "false",
		),
		array(
			"name" => "codiad.editor.fontSize",
			"value" => "14px",
		),
		array(
			"name" => "codiad.editor.highlightLine",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.indentGuides",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.overScroll",
			"value" => "0.5",
		),
		array(
			"name" => "codiad.editor.persistentModal",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.printMargin",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.printMarginColumn",
			"value" => "80",
		),
		array(
			"name" => "codiad.editor.rightSidebarTrigger",
			"value" => "false",
		),
		array(
			"name" => "codiad.editor.softTabs",
			"value" => "false",
		),
		array(
			"name" => "codiad.editor.tabSize",
			"value" => "4",
		),
		array(
			"name" => "codiad.editor.theme",
			"value" => "twilight",
		),
		array(
			"name" => "codiad.editor.wrapMode",
			"value" => "false",
		),
		array(
			"name" => "codiad.settings.autosave",
			"value" => "true",
		),
		array(
			"name" => "codiad.settings.plugin.sync",
			"value" => "true",
		),
		array(
			"name" => "codiad.settings.plugin.sync",
			"value" => "true",
		),
	);
	 
	/**
	 * Properties
	 */
	 
	public $archive = "";
	public $path = "";
	public $protocol = "";
	public $update = null;
	public $username = "";
	
	function __construct() {
		
		
		$this->update = new Update();
		$this->protocol = $this->check_protocol();
		$this->archive = $this->update->archive;
		$this->path = BASE_PATH;
		$this->username = $_SESSION["user"];
		/*
		//Trigger update
		$this->update();*/
	}
	
	function backup() {
		
		$backup = "../../backup/";
		$source = "../../";
		//Add Sessions path if not there.
		
		/**
		 * Create sessions path.
		 */
		
		if ( ! is_dir( $backup ) ) {
			
			mkdir( $backup, 00755 );
		}
		
		function copy_backup( $source, $dest ) {
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
				if ($entry == '.' || $entry == '..' || $entry == 'backup' || $entry == 'codiad-master' || $entry == 'workspace') {
					continue;
				}
				
				// Deep copy directories
				copy_backup("$source/$entry", "$dest/$entry");
			}
			
			// Clean up
			$dir->close();
		}
		
		copy_backup( $source, $backup );
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
		
		require_once('../../common.php');
		require_once('../sql/class.sql.php');
		
		$user_settings_file = DATA . "/settings.php";
		$projects_file = DATA . "/projects.php";
		$users_file = DATA . "/users.php";
		
		$sql = new sql();
		$connection = $sql->connect();
		
		$sql = "
CREATE TABLE IF NOT EXISTS `options`(
    `id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `value` TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS `projects`(
    `id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `owner` VARCHAR(255) NOT NULL,
    `access` TEXT
);
CREATE TABLE IF NOT EXISTS `users`(
    `id` INT(11) NOT NULL,
    `first_name` VARCHAR(255) DEFAULT NULL,
    `last_name` VARCHAR(255) DEFAULT NULL,
    `username` VARCHAR(255) NOT NULL,
    `password` TEXT NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `project` VARCHAR(255) DEFAULT NULL,
    `access` VARCHAR(255) NOT NULL,
    `groups` TEXT,
    `token` TEXT
);
CREATE TABLE IF NOT EXISTS `user_options`(
    `id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `username` VARCHAR(255) NOT NULL,
    `value` TEXT NOT NULL
);
ALTER TABLE `options` ADD PRIMARY KEY(`id`), ADD UNIQUE KEY `option_name`(`name`);
ALTER TABLE `projects` ADD PRIMARY KEY(`id`), ADD UNIQUE KEY `project_path`(`path`, `owner`);
ALTER TABLE `users` ADD PRIMARY KEY(`id`), ADD UNIQUE KEY `username`(`username`);
ALTER TABLE `user_options` ADD PRIMARY KEY(`id`), ADD UNIQUE KEY `option_name`(`name`, `username`);
ALTER TABLE `options` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `projects` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_options` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
";
		if ( $connection->multi_query( $sql ) !== TRUE ) {
			
			$this->restore();
			exit( $connection->error );
		}
		
		if( file_exists( $user_settings_file ) ) {
			
			unlink( $user_settings_file );
		}
		
		if( file_exists( $projects_file ) ) {
			
			$projects = getJSON( 'projects.php' );
			foreach( $projects as $project => $data ) {
				
				$owner = 'nobody';
				$sql = "INSERT INTO `projects`( `name`, `path`, `owner` ) VALUES ( ?, ?, ? );";
				$bind = "sss";
				$bind_variables = array( $data["name"], $data["path"], $owner );
				$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error creating project $project." ) );
				
				if( sql::check_sql_error( $return ) ) {
				} else {
					
					$this->restore();
					exit(formatJSEND( "error", "There was an error adding projects to database." ));
				}
			}
			unlink( $projects_file );
		}
		
		if( file_exists( $users_file ) ) {
			
			$users = getJSON( 'users.php' );
			foreach( $users as $user ) {
				
				if( $user["username"] === $_SESSION["user"] ) {
					
					$access = "admin";
				} else {
					
					$access = "user";
				}
				$sql = "INSERT INTO `users`( `username`, `password`, `access`, `project` ) VALUES ( ?, PASSWORD( ? ), ?, ? );";
				$bind = "ssss";
				$bind_variables = array( $user["username"], $user["password"], $access, null );
				$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error that username is already taken." ) );
				
				if( sql::check_sql_error( $return ) ) {
					
					$this->username = $user["username"];
					$this->set_default_options();
					//echo formatJSEND( "success", array( "username" => $user["username"] ) );
				} else {
					
					$this->restore();
					exit(formatJSEND( "error", "The Username is Already Taken" ));
				}
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
			if ($entry == '.' || $entry == '..' || $entry == 'backup' || $entry == 'codiad-master' || $entry == 'workspace' || $entry == 'plugins') {
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
	
	function restore() {
		
		$dest = "../../";
		$source = "../../backup/";
		
		$this->copyr( $source, $dest );
		$this->remove_directory( $source );
	}
	
	public function set_default_options() {
		
		foreach( self::DEFAULT_OPTIONS as $id => $option ) {
			
			$this->update_option( $option["name"], $option["value"], true );
		}
	}
	
	function update() {
		
		$this->backup();
		
		try {
			
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
				
				$this->path . "/components/autocomplete",
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
				
				$this->path . "/.gitignore",
				$this->path . "/codiad-master/.gitignore",
				
				$this->path . "/.gitlab-ci.yml",
				$this->path . "/codiad-master/.gitlab-ci.yml"
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
		} catch( Exception $e ) {
			
			$this->restore();
			return( $e );
		}
	}
	
	public function update_option( $option, $value, $user_setting = null ) {
		
		$query = "INSERT INTO user_options ( `name`, `username`, `value` ) VALUES ( ?, ?, ? );";
		$bind = "sss";
		$bind_variables = array(
			$option,
			$this->username,
			$value,
		);
		$result = sql::sql( $query, $bind, $bind_variables, formatJSEND( "error", "Error, Could not add user's settings." ) );
		
		if( $result !== true ) {
			
			$query = "UPDATE user_options SET `value`=? WHERE `name`=? AND `username`=?;";
			$bind = "sss";
			$bind_variables = array(
				$value,
				$option,
				$this->username,
			);
			$result = sql::sql( $query, $bind, $bind_variables, formatJSEND( "error", "Error, Could not update user's settings." ) );
		}
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