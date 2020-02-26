<?php
/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), and Isaac Brown (telaaedifex.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$sql = null;
Common::startSession();

//////////////////////////////////////////////////////////////////
// Common Class
//////////////////////////////////////////////////////////////////

class Common {
	
	//////////////////////////////////////////////////////////////////
	// PROPERTIES
	//////////////////////////////////////////////////////////////////
	
	public static $debugMessageStack = array();
	
	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Construct
	//////////////////////////////////////////////////////////////////
	
	public static function construct() {

		$path = str_replace( "index.php", "", $_SERVER['SCRIPT_FILENAME'] );
		foreach ( array( "components", "plugins" ) as $folder ) {
			
			if( strpos( $_SERVER['SCRIPT_FILENAME'], $folder ) ) {
				
				$path = substr( $_SERVER['SCRIPT_FILENAME'], 0, strpos( $_SERVER['SCRIPT_FILENAME'], $folder ) );
				break;
			}
		}
		
		if( file_exists( __DIR__ . '/config.php' ) ) {
			
			require_once( __DIR__ . '/config.php' );
		}
		
		if( ! defined( 'BASE_PATH' ) ) {
			
			define( 'BASE_PATH', __DIR__ );
		}
		
		if( ! defined( 'COMPONENTS' ) ) {
			
			define( 'COMPONENTS', BASE_PATH . '/components' );
		}
		
		if( ! defined( 'PLUGINS' ) ) {
			
			define( 'PLUGINS', BASE_PATH . '/plugins' );
		}
		
		if( ! defined( 'DATA' ) ) {
			
			define( 'DATA', BASE_PATH . '/data' );
		}
		
		if( ! defined( 'SESSIONS_PATH' ) ) {
			
			define( 'SESSIONS_PATH', BASE_PATH . '/data/sessions' );
		}
		
		if( ! defined( 'SITE_ID' ) ) {
			
			define( 'SITE_ID', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		}
		
		if( ! defined( 'THEMES' ) ) {
			
			define( "THEMES", BASE_PATH . "/themes" );
		}
		
		if( ! defined( 'THEME' ) ) {
			
			define( "THEME", "default" );
		}
		
		if( ! defined( 'LANGUAGE' ) ) {
			
			define( "LANGUAGE", "en" );
		}
		
		if( ! defined( 'UPLOAD_CACHE' ) ) {
			
			if( ! is_dir( sys_get_temp_dir() ) ) {
				
				define( "UPLOAD_CACHE", DATA . "/uploads" );
			} else {
				
				define( "UPLOAD_CACHE", rtrim( sys_get_temp_dir(), "/" ) );
			}
		}
		
		require_once( COMPONENTS . "/permissions/class.permissions.php" );
		require_once( COMPONENTS . "/update/class.update.php" );
		require_once( COMPONENTS . "/sql/class.sql.php" );
		global $sql;
		$sql = sql::get_instance();
	}
	
	//////////////////////////////////////////////////////////////////
	// New Methods
	//////////////////////////////////////////////////////////////////
	
	public static function get_user_id( $username ) {
		
		global $sql;
		$user_id = false;
		$query = "SELECT id FROM users WHERE username = ? LIMIT 1;";
		$bind_variables = array( $username );
		$return = $sql->query( $query, $bind_variables, array(), "fetch" );
		
		if( ! empty( $return ) ) {
			
			$user_id = $return["id"];
		}
		return $user_id;
	}
	
	public static function get_users( $return = "return", $exclude_current = false ) {
		
		global $sql;
		$query = "SELECT * FROM users";
		$bind_variables = array();
		
		if( $exclude_current ) {
			
			$query .= " WHERE username <> ?";
			array_push( $bind_variables, $_SESSION["user"] );
		}
		
		$result = $sql->query( $query, $bind_variables, array() );
		
		switch( $return ) {
			
			case( "json" ):
				
				$return = json_encode( $result );
			break;
			
			case( "return" ):
				
				$return = $result;
			break;
		}
		return( $return );
	}
	
	public static function get_version() {
		
		return Update::VERSION;
	}
	
	public static function is_admin() {
		
		global $sql;
		$admin = false;
		
		if( isset( $_SESSION["user_id"] ) ) {
			
			$query = "SELECT COUNT( * ) FROM users WHERE id=? AND access=?;";
			$bind_variables = array( $_SESSION["user_id"], Permissions::SYSTEM_LEVELS["admin"] );
			$return = $sql->query( $query, $bind_variables, -1, 'fetchColumn' );
			$admin = ( $return > 0 );
		}
		return $admin;
	}
	
	public static function logout() {
		
		if( isset( $_SESSION["user"] ) ) {
			
			global $sql;
			$query = "UPDATE users SET token=? WHERE username=?;";
			$bind_variables = array( null, $_SESSION["user"] );
			$return = $sql->query( $query, $bind_variables, formatJSEND( "error", "Error updating user information." ), 'fetchColumn' );
			
			if( ! $return > 0 ) {
				
				$json = json_decode( $return, true );
				echo( $return );
			}
			
		}
		session_unset();
		session_destroy();
		session_start();
	}
	
	//////////////////////////////////////////////////////////////////
	// Search Users
	//////////////////////////////////////////////////////////////////
	
	public static function search_users( $username, $return = "return", $exclude_current = false ) {
		
		global $sql;
		$query = "SELECT username FROM users WHERE username LIKE ?";
		$bind_variables = array( "%{$username}%" );
		
		if( $exclude_current ) {
			
			$query .= " AND username != ?";
			array_push( $bind_variables, $_SESSION["user"] );
		}
		
		$result = $sql->query( $query, $bind_variables, array() );
		
		if( ! empty( $result ) ) {
			
			switch( $return ) {
				
				case( "exit" ):
					
					exit( formatJSEND( "success", $result ) );
				break;
				
				case( "json" ):
					
					$return = json_encode( $result );
				break;
				
				case( "return" ):
					
					$return = $result;
				break;
			}
		} else {
			
			switch( $return ) {
				
				case( "exit" ):
					
					exit( formatJSEND( "error", "Error selecting user information." ) );
				break;
				
				case( "json" ):
					
					$return = formatJSEND( "error", "Error selecting user information." );
				break;
				
				case( "return" ):
					
					$return = null;
				break;
			}
		}
		
		return( $return );
	}
	
	//////////////////////////////////////////////////////////////////
	// Start Sessions
	//////////////////////////////////////////////////////////////////
	
	public static function start_session() {
		
		Common::construct();
		
		//Set a Session Name
		try {
			
			session_name( md5( BASE_PATH ) );
			session_save_path( SESSIONS_PATH );
		} catch( exception $e ) {
		}
		
		if( ! is_dir( SESSIONS_PATH ) ) {
			
			mkdir( SESSIONS_PATH );
		}
		
		session_start();
		
		if( ! defined( 'SESSION_ID' ) ) {
			
			define( "SESSION_ID", session_id() );
		}
		
		//Check for external authentification
		if( defined( 'AUTH_PATH' ) ) {
			
			require_once( AUTH_PATH );
		}
		
		global $lang;
		if ( isset( $_SESSION['lang'] ) ) {
			
			include BASE_PATH . "/languages/{$_SESSION['lang']}.php";
		} else {
			
			include BASE_PATH . "/languages/" . LANGUAGE . ".php";
		}
	}
	
	public static function return( $output, $action = "return" ) {
		
		switch( $action ) {
			
			case( "exit" ):
				
				exit( $output );
			break;
			
			case( "return" ):
				
				return( $output );
			break;
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Old Methods
	//////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////////
	// SESSIONS
	//////////////////////////////////////////////////////////////////
	
	public static function startSession() {
		
		Common::start_session();
	}
	
	//////////////////////////////////////////////////////////////////
	// Read Content of directory
	//////////////////////////////////////////////////////////////////
	
	public static function readDirectory( $foldername ) {
		
		$tmp = array();
		$allFiles = scandir( $foldername );
		foreach ( $allFiles as $fname ) {
			
			if( $fname == '.' || $fname == '..' ) {
				
				continue;
			}
			if( is_dir( $foldername . '/' . $fname ) ) {
				
				$tmp[] = $fname;
			}
		}
		return $tmp;
	}
	
	//////////////////////////////////////////////////////////////////
	// Log debug message
	// Messages will be displayed in the console when the response is
	// made with the formatJSEND function.
	//////////////////////////////////////////////////////////////////
	
	public static function debug( $message ) {
		
		Common::$debugMessageStack[] = $message;
	}
	
	//////////////////////////////////////////////////////////////////
	// URLs
	//////////////////////////////////////////////////////////////////
	
	public static function getConstant( $key, $default = null ) {
		
		return defined( $key ) ? constant( $key ) : $default;
	}
	
	//////////////////////////////////////////////////////////////////
	// Localization
	//////////////////////////////////////////////////////////////////
	
	public static function i18n( $key, $args = array() ) {
		
		echo Common::get_i18n( $key, $args );
	}
	
	public static function get_i18n( $key, $args = array() ) {
		
		global $lang;
		$key = ucwords( strtolower( $key ) ); //Test, test TeSt and tESt are exacly the same
		$return = isset( $lang[$key] ) ? $lang[$key] : $key;
		foreach( $args as $k => $v ) {
			
			$return = str_replace( "%{" . $k . "}%", $v, $return );
		}
		return $return;
	}
	
	//////////////////////////////////////////////////////////////////
	// Check Session / Key
	//////////////////////////////////////////////////////////////////
	
	public static function checkSession() {
		
		$pass = false;
		
		if( isset( $_SESSION["token"] ) && isset( $_SESSION["user_id"] ) ) {
			
			global $sql;
			$query = "SELECT COUNT( * ) FROM users WHERE id=? AND token=?;";
			$bind_variables = array( $_SESSION["user_id"], sha1( $_SESSION["token"] ) );
			$return = $sql->query( $query, $bind_variables, formatJSEND( "error", "Error checking access." ), "fetchColumn" );
			
			if( $return > 0 ) {
				
				$pass = true;
			}
		}
		
		if( ! $pass ) {
			
			logout();
			exit( '{"status":"error","message":"Authentication Error<script>window.location.href = window.location.protocol + `' . "//" . Common::getConstant('BASE_URL') . '`</script>"}' );
		}
	}
	
	
	//////////////////////////////////////////////////////////////////
	// Get JSON
	//////////////////////////////////////////////////////////////////
	
	public static function getJSON( $file, $namespace = "" ) {
		
		$path = DATA . "/";
		if( $namespace != "" ) {
			
			$path = $path . $namespace . "/";
			$path = preg_replace( '#/+#', '/', $path );
		}
		
		$json = file_get_contents( $path . $file );
		$json = str_replace( ["\n\r", "\r", "\n"], "", $json );
		$json = str_replace( "|*/?>", "", str_replace( "<?php/*|", "", $json ) );
		$json = json_decode( $json, true );
		return $json;
	}
	
	//////////////////////////////////////////////////////////////////
	// Save JSON
	//////////////////////////////////////////////////////////////////
	
	public static function saveJSON( $file, $data, $namespace = "" ) {
		
		$path = DATA . "/";
		if( $namespace != "" ) {
			
			$path = $path . $namespace . "/";
			$path = preg_replace( '#/+#', '/', $path );
			if( ! is_dir( $path ) ) {
				
				mkdir( $path );
			}
		}
		
		$data = "<?php\r\n/*|" . json_encode( $data ) . "|*/\r\n?>";
		$write = fopen( $path . $file, 'w' ) or die( "can't open file " . $path . $file );
		fwrite( $write, $data );
		fclose( $write );
	}
	
	//////////////////////////////////////////////////////////////////
	// Format JSEND Response
	//////////////////////////////////////////////////////////////////
	
	public static function formatJSEND( $status, $data = false, $debug = false ) {
		
		/// Debug /////////////////////////////////////////////////
		$jsend = array(
			"status" => null,
			"data" => null,
			"debug" => null,
			"message" => null,
		);
		
		if( count( Common::$debugMessageStack ) > 0 ) {
			
			$jsend["debug"] = json_encode( Common::$debugMessageStack );
		}
		
		if( $debug ) {
			
			$jsend["debug"] = $debug;
		}
		
		if( $status == "success" ) {
			
			// Success ///////////////////////////////////////////////
			$jsend["status"] = "success";
			
			if( $data ) {
				
				$jsend["data"] = $data;
			}
		} else {
			
			// Error /////////////////////////////////////////////////
			$jsend["status"] = "error";
			$jsend["message"] = $data;
		}
		// Return ////////////////////////////////////////////////
		return json_encode( $jsend );
	}
	
	//////////////////////////////////////////////////////////////////
	// Check Function Availability
	//////////////////////////////////////////////////////////////////
	
	public static function checkAccess() {
		
		return self::is_admin();
	}
	
	//////////////////////////////////////////////////////////////////
	// Check Path
	//////////////////////////////////////////////////////////////////
	
	public static function checkPath( $path ) {
		
		return Permissions::has_manager( $path );
	}
	
	
	//////////////////////////////////////////////////////////////////
	// Check Function Availability
	//////////////////////////////////////////////////////////////////
	
	public static function isAvailable( $func ) {
		
		if ( ini_get( 'safe_mode' ) ) {
			
			return false;
		}
		$disabled = ini_get( 'disable_functions' );
		if ( $disabled ) {
			
			$disabled = explode( ',', $disabled );
			$disabled = array_map( 'trim', $disabled );
			return ! in_array( $func, $disabled );
		}
		return true;
	}
	
	//////////////////////////////////////////////////////////////////
	// Check If Path is absolute
	//////////////////////////////////////////////////////////////////
	
	public static function isAbsPath( $path ) {
		
		return( ( isset( $path[0] ) && $path[0] === '/' ) || ( isset( $path[1] ) && $path[1] === ':' ) ) ? true : false;
	}
	
	//////////////////////////////////////////////////////////////////
	// Check If WIN based system
	//////////////////////////////////////////////////////////////////
	
	public static function isWINOS( ) {
		
		return( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' );
	}
}

//////////////////////////////////////////////////////////////////
// Wrapper for old method names
//////////////////////////////////////////////////////////////////

function checkAccess() { return Common::checkAccess(); }
function checkPath( $path ) { return Common::checkPath($path); }
function checkSession() { Common::checkSession(); }
function debug( $message ) { Common::debug( $message ); }
function formatJSEND( $status, $data=false ){ return Common::formatJSEND($status,$data); }
function get_i18n( $key, $args = array() ) { return Common::get_i18n($key, $args); }
function get_user_id( $username ) { return Common::get_user_id( $username ); }
function get_users( $return = "return", $exclude_current = false ) { return Common::get_users( $return, $exclude_current ); }
function get_version() { return Common::get_version(); }
function getJSON( $file,$namespace=""){ return Common::getJSON( $file, $namespace ); }
function i18n( $key, $args = array() ) { echo Common::i18n( $key, $args ); }
function is_admin() { return Common::is_admin(); }
function isAvailable( $func ) { return Common::isAvailable( $func ); }
function logout() { return Common::logout(); }
function saveJSON( $file, $data, $namespace="" ){ Common::saveJSON( $file, $data, $namespace ); }
function search_users( $username, $return = "return", $exclude_current = false ) { return Common::search_users( $username, $return, $exclude_current ); }
?>
