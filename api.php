<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), and Isaac Brown (telaaedifex.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once( './config.php' );
require_once( './common.php' );

class api {
	
	private $sql = null;
	public $user = null;
	
	public function __construct() {
		
		$requirements = array(
			"action",
			"user",
		);
		
		if( isset( $_POST["action"] ) ) {
			
			$request = $_POST;
		} elseif( isset( $_GET["action"] ) ) {
			
			$request = $_GET;
		} else {
			
			$this->return( "error", "Error, action was expected but not recieved." );
		}
		
		foreach( $requirements as $requirement ) {
			
			if( ! isset( $request[$requirement] ) ) {
				
				$this->return( "error", "Error, '$requirement' was expected but not recieved." );
			}
		}
		
		if( isset( $request["action"] ) ) {
			
			$action = $request["action"];
			
			if( $action === "get_token" ) {
				
				if( isset( $request["user"] ) && isset( $request["password"] ) ) {
					
					$this->get_token( $request["user"], $request["password"] );
				}
				exit();
			}
			
			if( ! isset( $request["token"] ) ) {
				
				$this->return( "error", "Error, token was expected but not recieved." );
			}
			
			if( ! isset( $request["atts"] ) ) {
				
				$this->return( "error", "Error, atts were expected but not recieved." );
			}
			
			$this->user = $request["user"];
			$_SESSION["user"] = $request["user"];
			$_SESSION["token"] = $request["token"];
			
			
			
			if( $this->check_session() ) {
				
				require_once( "./components/filemanager/class.dirzip.php" );
				//require_once( "./components/filemanager/class.filemanager.php" );
				//require_once( "./components/project/class.project.php" );
				//require_once( "./components/settings/class.settings.php" );
				//require_once( "./components/user/class.user.php" );
				
				$atts = json_decode( $request["atts"], true );
				
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					
					$atts = $request["atts"];
				}
				call_user_func( array( $this, $action ), $atts );
			} else {
				
				$this->return( "error", "Error, unauthenticated. " . var_dump( $_SESSION["token"] ) );
			}
			exit();
		}
	}
	
	function check_session() {
		
		global $sql;
		$pass = false;
		$query = "SELECT * FROM users WHERE username=? AND token=?;";
		$bind_variables = array( $_SESSION["user"], sha1( $_SESSION["token"] ) );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			$pass = true;
		} else {
			
			$pass = false;
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
		
		global $sql;
		$pass = false;
		$query = "SELECT * FROM users WHERE username=? AND password=?;";
		$bind_variables = array( $username, $this->encrypt( $password ) );
		$return = $sql->query( $query, $bind_variables, array() );
		
		if( ! empty( $return ) ) {
			
			$pass = true;
			$user = $return[0];
			$_SESSION['user'] = $username;
			
			$token = mb_strtoupper( strval( bin2hex( openssl_random_pseudo_bytes( 16 ) ) ) );
			$_SESSION['token'] = $token;
			$query = "UPDATE users SET token=? WHERE username=?;";
			$bind_variables = array( sha1( $token ), $username );
			$sql->query( $query, $bind_variables, 0, "rowCount" );
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