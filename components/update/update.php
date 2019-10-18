<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once('../../common.php');
require_once('../settings/class.settings.php');
require_once('./class.update.php');



$user_settings_file = BASE_PATH . "/data/settings.php";
$projects_file = BASE_PATH . "/data/projects.php";
$users_file = BASE_PATH . "/data/users.php";
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
	 
	/**
	 * Properties
	 */
	 
	public $archive = "";
	public $development_archive = "";
	public $install_folder = "";
	public $path = "";
	public $protocol = "";
	public $update = null;
	public $username = "";
	public $tags = "";
	public $commits = "";
	
	function __construct() {
		
		
		$this->update = new Update();
		$this->protocol = $this->check_protocol();
		$this->archive = "https://gitlab.com/xevidos/codiad/-/archive/master/codiad-master.zip";
		$this->development_archive = "https://gitlab.com/xevidos/codiad/-/archive/development/codiad-development.zip";
		$this->commits = "https://gitlab.com/api/v4/projects/8466613/repository/commits/";
		$this->tags = "https://gitlab.com/api/v4/projects/8466613/repository/tags/";
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
			if ( is_link( $source ) ) {
				
				return symlink( readlink( $source ), $dest );
			}
			
			// Simple copy for a file
			if ( is_file( $source ) ) {
				
				return copy($source, $dest);
			}
			
			// Make destination directory
			if ( ! is_dir( $dest ) ) {
				
				mkdir( $dest );
			}
			
			$invalid_files = array(
				'.',
				'..',
				'backup',
				'codiad-master',
				'codiad-development',
				'update.zip',
				'workspace',
			);
			
			// Loop through the folder
			$dir = dir( $source );
			while ( false !== $entry = $dir->read() ) {
			// Skip pointers
				if( in_array( $entry, $invalid_files ) ) {
					
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
	
	function check_sql() {
		
		$sql = new sql();
		$connection = $sql->connect();
		$result = $sql->create_default_tables();
		$upgrade_function = str_replace( ".", "_", $this->update::VERSION );
		
		if( is_callable( array( $this, $upgrade_function ) ) ) {
			
			$this->$upgrade_function();
		}
	}
	
	function check_update() {
		
		$response = $this->get_remote_version();
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
		
		$user_settings_file = DATA . "/settings.php";
		$projects_file = DATA . "/projects.php";
		$users_file = DATA . "/users.php";
		$sql = new sql();
		$connection = $sql->connect();
		$result = $sql->create_default_tables();
		
		if ( ! $result === true ) {
			
			$this->restore();
			exit( json_encode( $connection->errorInfo(), JSON_PRETTY_PRINT ) );
		}
		
		if( file_exists( $user_settings_file ) ) {
			
			unlink( $user_settings_file );
		}
		
		if( file_exists( $projects_file ) ) {
			
			$projects = getJSON( 'projects.php' );
			
			if( is_array( $projects ) ) {
				
				foreach( $projects as $project => $data ) {
					
					$owner = 'nobody';
					$query = "INSERT INTO projects( name, path, owner ) VALUES ( ?, ?, ? );";
					$bind_variables = array( $data["name"], $data["path"], $owner );
					$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
					
					if( $return > 0 ) {
					} else {
						
						$this->restore();
						exit( formatJSEND( "error", "There was an error adding projects to database." ) );
					}
				}
			}
			unlink( $projects_file );
		}
		
		if( file_exists( $users_file ) ) {
			
			$users = getJSON( 'users.php' );
			
			if( is_array( $users ) ) {
				
				foreach( $users as $user ) {
					
					if( $user["username"] === $_SESSION["user"] ) {
						
						$access = "admin";
					} else {
						
						$access = "user";
					}
					$query = "INSERT INTO users( username, password, access, project ) VALUES ( ?, ?, ?, ? );";
					$bind_variables = array( $user["username"], $user["password"], $access, null );
					$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
					
					if( $return > 0 ) {
						
						$this->username = $user["username"];
						$this->set_default_options();
						//echo formatJSEND( "success", array( "username" => $user["username"] ) );
					} else {
						
						$this->restore();
						exit(formatJSEND( "error", "The Username is Already Taken" ));
					}
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
			if ($entry == '.' || $entry == '..' || $entry == 'backup' || $entry == 'codiad-master' || $entry == 'codiad-development' || $entry == 'workspace' || $entry == 'plugins') {
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
	
	function download( $development = false ) {
		
		switch( $this->protocol ) {
			
			case( "curl" ):
				
				$filepath = $this->path . "/update.zip";
				if( file_exists( $filepath ) ) {
					unlink( $filepath );
				}
				$curl = curl_init();
				
				if( $development ) {
					
					curl_setopt( $curl, CURLOPT_URL, $this->development_archive );
				} else {
					
					curl_setopt( $curl, CURLOPT_URL, $this->archive );
				}
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
	
	public function get_remote_version( $action="check", $localversion = "" ) {
		
		if ( $this->protocol === "none" ) {
			
			return;
		}
		
		switch( $this->protocol ) {
			
			case( "curl" ):
				
				$curl = curl_init();
				curl_setopt( $curl, CURLOPT_URL, $this->tags );
				//curl_setopt($curl, CURLOPT_POSTFIELDS, "");
				curl_setopt( $curl, CURLOPT_HEADER, 0 );
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );  
				curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13' );
				$content = curl_exec( $curl );
				curl_close( $curl );
				
				$response = json_decode( $content, true );
				//Return latest release
				return $response[0];
			break;
			
			case( "fopen" ):
				
			break;
		}
	}
	
	function remove_directory( $path ) {
		
		$files = glob( $path . '{,.}[!.,!..]*', GLOB_MARK|GLOB_BRACE );
		foreach( $files as $file ) {
			
			is_dir( $file ) ? $this->remove_directory( $file ) : unlink( $file );
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
		
		foreach( Settings::DEFAULT_OPTIONS as $id => $option ) {
			
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
			
			if( is_dir( $this->path . "/codiad-master" ) ) {
				
				$src = $this->path . "/codiad-master/";
				$src_folder = $this->path . "/codiad-master";
				$update_folder = "codiad-master";
			} else {
				
				$src = $this->path . "/codiad-development/";
				$src_folder = $this->path . "/codiad-development";
				$update_folder = "codiad-development";
			}
			
			/**
			 * If any files in the array below are still set delete them.
			 * 
			 */
			 
			$file_conflictions = array(
				
				$this->path . "/.travis.yml",
				$this->path . "/{$update_folder}/.travis.yml",
				
				$this->path . "/.gitignore",
				$this->path . "/{$update_folder}/.gitignore",
				
				$this->path . "/.gitlab-ci.yml",
				$this->path . "/{$update_folder}/.gitlab-ci.yml"
			);
			
			foreach( $file_conflictions as $file ) {
				
				if( is_file( $file ) ) {
					
					unlink( $file );
				}
			}
			
			$dest = $this->path . "/";
			
			$this->copyr( $src, $dest );
			$this->remove_directory( $src );
			return( "true" );
		} catch( Exception $e ) {
			
			$this->restore();
			return( $e );
		}
	}
	
	public function update_database() {
		
		try {
			
			$this->convert();
		} catch( Exception $e ) {
			
			$this->restore();
			return( $e );
		}
		
		$this->check_sql();
		return( "true" );
	}
	
	public function update_option( $option, $value, $user_setting = null ) {
		
		$sql = new sql();
		$query = "INSERT INTO user_options ( name, username, value ) VALUES ( ?, ?, ? );";
		$bind_variables = array(
			$option,
			$this->username,
			$value,
		);
		$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $result == 0 ) {
			
			$query = "UPDATE user_options SET value=? WHERE name=? AND username=?;";
			$bind_variables = array(
				$value,
				$option,
				$this->username,
			);
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
		}
		
		if( $result > 0 ) {
			
			echo formatJSEND( "success", null );
		} else {
			
			echo formatJSEND( "error", "Error, Could not update option $option" );
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
	
	global $sql;
	$updater = new updater();
	$action = $_GET["action"];
	$sql = new sql();
	
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
		
		case( "download_development" ):
			
			echo $updater->download( true );
		break;
		
		case( "extract" ):
			
			echo $updater->extract();
		break;
		
		case( "update" ):
			
			echo $updater->update();
		break;
		
		case( "update_database" ):
			
			echo $updater->update_database();
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
				
				base_url: `<?php echo BASE_URL;?>`,
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
				
				apply_database: function() {
					
					return jQuery.ajax({
							
						url: "update.php",
						type: "GET",
						dataType: 'html',
						data: {
							action: 'update_database',
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
				
				download: function( development=false ) {
					
					let data = {}
					
					if( development ) {
						
						data.action = 'download_development';
					} else {
						
						data.action = 'download';
					}
					
					return jQuery.ajax({
						
						url: "update.php",
						type: "GET",
						dataType: 'html',
						data: data,
						
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
					
					let GET = {};
					let parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function( m, key, value) {
						
						GET[key] = value;
					});
					
					if( Object.keys( GET ).includes( "step" ) && GET.step === "database_update" ) {
						
						progress.innerText = "Applying database update.";
						let apply = await this.apply_database();
						
						if( apply !== "true" ) {
							
							console.log( apply );
							progress.innerText = "Error applying update.";
							return;
						}
						
						progress.innerText = "Successfully completed update.  Returning you to Codiad ...";
						
						setTimeout( function() {
							
							window.location.href = `${location.protocol}//${codiad.update.base_url}`;
						}, 5000);
						return;
					}
					
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
						
						progress.innerText = "Applying filesystem update.";
						let apply = await this.apply();
						
						if( apply !== "true" ) {
							
							console.log( apply );
							progress.innerText = "Error applying update.";
							return;
						}
						
						progress.innerText = "Filesystem update finished.  Please wait, your browser will now reload and start the database update.";
						
						setTimeout( function() {
							
							window.location.href = window.location.href + "?step=database_update"
						}, 5000);
					} else if( result === "false" ) {
						
						progress.innerText = "No update was found ...";
					} else {
						
						progress.innerText = "Error, checking for updates failed.";
					}
				},
				
				update_development: async function() {
					
					let GET = {};
					let parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function( m, key, value) {
						
						GET[key] = value;
					});
					
					if( Object.keys( GET ).includes( "step" ) && GET.step === "database_update" ) {
						
						progress.innerText = "Applying database update.";
						let apply = await this.apply_database();
						
						if( apply !== "true" ) {
							
							console.log( apply );
							progress.innerText = "Error applying update.";
							return;
						}
						
						progress.innerText = "Successfully completed update.  You may now return to Codiad.";
						return;
					}
					
					progress.innerText = "An update was found.  Downloading update.";
					let download = await this.download( true );
					
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
					
					progress.innerText = "Applying filesystem update.";
					let apply = await this.apply();
					
					if( apply !== "true" ) {
						
						console.log( apply );
						progress.innerText = "Error applying update.";
						return;
					}
					
					progress.innerText = "Filesystem update finished.  Please wait, your browser will now reload and start the database update.";
					
					setTimeout( function() {
						
						window.location.href = window.location.href + "?step=database_update"
					}, 5000);
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