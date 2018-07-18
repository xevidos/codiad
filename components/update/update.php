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

	
	function __construct() {
		
		$update = new Update();
		$this->archive = $update->archive;
		$this->path = Common::getConstant('BASE_PATH');
		$this->protocol = $this->check_protocol();
		
		//Trigger update
		$this->update();
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
		
		$src = $this->path . "/codiad-master/";
		$src_folder = $this->path . "/codiad-master";
		$dest = $this->path . "/";
		
		$this->copyr( $src, $dest );
		
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Removing Update ... </p>';</script>";
		$this->remove_directory( $src );
	}
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
				//float: left;
				//font-size: 15px;
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
		<script>
			
		</script>
	</head>
	<body>
		<h1 class="title" style="text-align: center;">
			Telaaedifex Codiad Updater
		</h1>
		<div id="progress">
			Starting Update ...
		</div>
	</body>
</html>
<?php
new updater();
?><?php

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

	
	function __construct() {
		
		$update = new Update();
		$this->archive = $update->archive;
		$this->path = Common::getConstant('BASE_PATH');
		$this->protocol = $this->check_protocol();
		
		//Trigger update
		$this->update();
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
	
	//////////////////////////////////////////////////////////////////
	// Download latest archive
	//////////////////////////////////////////////////////////////////
	
	function download() {
		
		switch( $this->protocol ) {
			
			case( "curl" ):
				
				$filepath = $this->path . "/update.zip";
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
		if ( $zip->open( $this->path . "/update.zip", ZipArchive::OVERWRITE ) === TRUE ) {
			
			$zip->extractTo( $this->path );
			$zip->close();
			
			return true;
		} else {
			
			return false;
		}
	}
	
	function update() {
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Downloading latest version ... </p>';</script>";
		if ( ! $this->download() ) {
			
			echo "<script>document.getElementById('progress').innerHTML += '<br><p class=\"error_box\">Error downloading latest version</p>';</script>";
		}
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Extracting update ... </p>';</script>";
		if ( ! $this->extract() ) {
			
			echo "<script>document.getElementById('progress').innerHTML += '<br><p class=\"error_box\">Error extracting update</p>';</script>";
		}
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Updating ... </p>';</script>";
		try {
			
			exec( "cp -a " );
		} catch ( exception $e ) {
			
			echo "<script>document.getElementById('progress').innerHTML = '<p class=\"error_box\">Update Failed ... </p>';</script>";
			return;
		}
		
		echo "<script>document.getElementById('progress').innerHTML = '<p class=\"status_box\">Removing Update ... </p>';</script>";
		exec( "rm -rf " . $this->path . "/update.zip;rm -rf " . $this->path . "/codiad-master" );
	}
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
				//float: left;
				//font-size: 15px;
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
		<script>
			
		</script>
	</head>
	<body>
		<h1 class="title" style="text-align: center;">
			Telaaedifex Codiad Updater
		</h1>
		<div id="progress">
			Starting Update ...
		</div>
	</body>
</html>
<?php
new updater();
?>