<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../common.php');
require_once('./class.update.php');

checkSession();
if ( ! checkAccess() ) {
	echo "Error, you do not have access to update Codiad.";
	exit;
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
		/*$this->archive = $update->archive;
		$this->path = Common::getConstant('BASE_PATH');
		$this->protocol = $this->check_protocol();
		
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
				curl_setopt($curl, CURLOPT_URL, $this->archive);
				//curl_setopt($curl, CURLOPT_POSTFIELDS, "");
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13');
				$raw_file_data = curl_exec($curl);
				curl_close($curl);
				
				file_put_contents( $filepath, $raw_file_data );
				return ( filesize( $filepath ) > 0 ) ? true : false;
			break;
			
			case( "fopen" ):
				
			break;
		}
	}
	
	function extract() {
		
		if ( ! extension_loaded( 'zip' ) ) {
			
			echo "<script>document.getElementById('progress').innerHTML = '<p class=\"error_box\">Error, the php zip extension does not seem to be installed.  Can not continue with update.  Please install the <a href=\"http://php.net/manual/en/book.zip.php\" target=\"_blank\">php zip extension</a></p>'> ... </p>';</script>";
			return false;
		}
		
		$zip = new ZipArchive;
		if ( $zip->open( $this->path . "/update.zip" ) === TRUE ) {
			
			$zip->extractTo( $this->path );
			$zip->close();
			
			return true;
		} else {
			
			return false;
		}
	}
	
	function remove_directory( $path ) {
		
		$files = glob($path . '/*');
		foreach ($files as $file) {
		
			is_dir($file) ? $this->remove_directory($file) : unlink($file);
		}
		rmdir($path);
		return;
	}
		
	function update() {
		
		$sessions = "../../data/sessions";
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Downloading latest version ... </p>';</script>";
		if ( ! $this->download() ) {
			
			echo "<script>document.getElementById('progress').innerHTML += '<br><p class=\"error_box\">Error downloading latest version</p>';</script>";
			return;
		}
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Extracting update ... </p>';</script>";
		if ( ! $this->extract() ) {
			
			echo "<script>document.getElementById('progress').innerHTML += '<br><p class=\"error_box\">Error extracting update</p>';</script>";
			return;
		}
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Updating ... </p>';</script>";
		
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
		
		foreach( $folder_conflictions as $file ) {
			
			unlink( $file );
		}
		
		
		$src = $this->path . "/codiad-master/";
		$src_folder = $this->path . "/codiad-master";
		$dest = $this->path . "/";
		
		$this->copyr( $src, $dest );
		
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Removing Update ... </p>';</script>";
		$this->remove_directory( $src );
	}
}

if( isset( $_GET["action"] ) && $_GET["action"] !== '' ) {
	
	$updater = new updater();
	$action = $_GET["action"];
	
	switch( $action ) {
		
		case( "check_update" ):
			
			echo $updater->check_update();
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
				width: 100%;
			}
			
			.title {
				
				color: #666;
				display: block;
				font-weight: 500;
				margin: 10px;
				text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
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
				
				update: async function() {
					
					let result = await this.check_update();
					
					console.log( result );
					if( result === "true" ) {
						
						progress.innerText = "An update was found.  Starting update.";
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
			<p id="progress"></p>
		</div>
		<script>
			codiad.update.init();
		</script>
	</body>
</html>
