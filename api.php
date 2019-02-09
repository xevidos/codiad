<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), and Isaac Brown (telaaedifex.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once( './config.php' );
require_once( './common.php' );
require_once( './components/sql/class.sql.php' );

class api {
	
	private $sql = null;
	
	public $user = null;
	
	public function __construct() {
		
		$requirements = array(
			"action",
			"user",
		);
		
		foreach( $requirements as $requirement ) {
			
			if( ! isset( $_POST[$requirement] ) ) {
				
				$this->return( "error", "Error, '$requirement' was expected but not recieved." );
			}
		}
		
		if( isset( $_POST["action"] ) ) {
			
			$action = $_POST["action"];
			
			if( $action === "get_token" ) {
				
				if( isset( $_POST["user"] ) && isset( $_POST["password"] ) ) {
					
					$this->get_token( $_POST["user"], $_POST["password"] );
				}
				exit();
			}
			
			$this->user = $_POST["user"];
			$_SESSION["user"] = $_POST["user"];
			$_SESSION["token"] = $_POST["token"];
			
			
			
			if( $this->check_session() ) {
				
				require_once( "./components/filemanager/class.dirzip.php" );
				//require_once( "./components/filemanager/class.filemanager.php" );
				//require_once( "./components/project/class.project.php" );
				//require_once( "./components/settings/class.settings.php" );
				//require_once( "./components/user/class.user.php" );
				
				$atts = json_decode( $_POST["atts"], true );
				
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					
					$atts = $_POST["atts"];
				}
				call_user_func( array( $this, $action ), $atts );
			} else {
				
				$this->return( "error", "Error, unauthenticated. " . var_dump( $_SESSION["token"] ) );
			}
			exit();
		}
	}
	
	function check_session() {
		
		$pass = false;
		$sql = "SELECT * FROM users WHERE username=? AND token=?;";
		$bind = "ss";
		$bind_variables = array( $_SESSION["user"], $_SESSION["token"] );
		$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error checking access." ) );
		
		if( mysqli_num_rows( $return ) > 0 ) {
			
			$pass = true;
		}
		
		return( $pass );
	}
	
	function encrypt( $string ) {
		
		return( sha1( md5( $string ) ) );
	}
	
	function get_file( $atts ) {
		
		
	}
	
	function get_folder( $atts ) {
		
		$folder = $atts["path"];
		
		if( checkPath( $folder ) ) {
			
			
			$targetPath = DATA . '/';
			$dir = WORKSPACE . '/' . $folder;
			
			$filename = explode( "/", $folder );
			$filename = array_pop( $filename ) . "-" . date( 'Y.m.d' );
			$filename .= '.zip';
			$download_file = $targetPath . $filename;
			
			DirZip::zipDir( $dir, $download_file );
			
			$file = file_get_contents( $download_file );
			unlink( $download_file );
			$this->return( "success", $file );
		} else {
			
			$this->return( "error", "No access to folder: " . $folder );
		}
	}
	
	function get_token( $username, $password ) {
		
		$pass = false;
		$sql = "SELECT * FROM users WHERE username=? AND password=PASSWORD( ? );";
		$bind = "ss";
		$bind_variables = array( $username, $this->encrypt( $password ) );
		$return = sql::sql( $sql, $bind, $bind_variables, "Error fetching user information." );
		
		if( mysqli_num_rows( $return ) > 0 ) {
			
			$pass = true;
			$user = mysqli_fetch_assoc( $return );
			$_SESSION['user'] = $username;
			
			if( $user["token"] == null ) {
				
				$token = mb_strtoupper( strval( bin2hex( openssl_random_pseudo_bytes( 16 ) ) ) );
				$_SESSION['token'] = $token;
				$sql = "UPDATE users SET token=PASSWORD( ? ) WHERE username=?;";
				$bind = "ss";
				$bind_variables = array( $token, $username );
				sql::sql( $sql, $bind, $bind_variables, "Error updating user information." );
				
			} else {
				
				$token = $user["token"];
				$_SESSION['token'] = $token;
			}
		}
		
		if( $pass ) {
			
			$this->return( "success", $token );
		} else {
			
			$this->return( "error", "Incorrect Username or Password" );
		}
	}
	
	function return( $status, $value ) {
		
		//$return = json_encode( array( $status, $value ) );
		exit( $value );
	}
}

new api();
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title><?php echo SITE_NAME . "API";?></title>
	</head>
	<body>
		
	</body>
</html>