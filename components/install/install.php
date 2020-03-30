<?php

require_once( __DIR__ . "/../sql/class.sql.php" );
require_once( __DIR__ . "/../settings/class.settings.php" );

class Install {
	
	const PATH_REGEX = '/[^\w\-\._@]/';
	
	public $active = "";
	public $config = "";
	public $db_types = array();
	public $path = "";
	public $projects = "";
	public $rel = "";
	public $sessions = "";
	public $sql = null;
	public $users = "";
	public $workspace = "";
	
	function __construct() {
		
		if( isset( $_POST["path"] ) ) {
			
			$path = $_POST['path'];
			$rel = str_replace( '/components/install/install.php', '', $_SERVER['REQUEST_URI'] );
			
			$this->active = $path . "/data/active.php";
			$this->config = $path . "/config.php";
			$this->projects = $path . "/data/projects.php";
			$this->path = $path;
			$this->sessions = $path . "/data/sessions";
			$this->users = $path . "/data/users.php";
			$this->rel = $rel;
			$this->workspace = $path . "/workspace";
			$this->db_types = sql::DB_TYPES;
			$this->project_name = $_POST["project_name"];
			$this->project_path = $this->clean_path( $_POST["project_path"] );
			$this->username = $this->clean_username( $_POST["username"] );
			$this->password = $this->encrypt_password( $_POST["password"] );
			
			$this->check();
			$this->sql = new sql();
			$this->install();
			exit;
		}
	}
	
	function check() {
		
		if ( ! ( defined( 'DBHOST' ) && defined( 'DBNAME' ) && defined( 'DBUSER' ) && defined( 'DBPASS' ) && defined( 'DBTYPE' ) ) ) {
			
			define( 'DBHOST', $_POST["dbhost"] );
			define( 'DBNAME', $_POST["dbname"] );
			define( 'DBUSER', $_POST["dbuser"] );
			define( 'DBPASS', $_POST["dbpass"] );
			define( 'DBTYPE', $_POST["dbtype"] );
		} else {
			
			$this->JSEND( "The config file already exists.", "One or more of the following have already been set: {DBHOST},{DBNAME},{DBUSER},{DBPASS},{DBTYPE}," );
		}
		
		if( ! in_array( DBTYPE, $this->db_types ) ) {
			
			$this->JSEND( "Invalid database. Please select one of the following: " . implode( ", ", $db_types ),  json_encode( array( $dbtype, $db_types ) ) );
		}
		
		if( ! is_dir( $this->sessions ) ) {
			
			mkdir( $this->sessions, 00755 );
		}
	}
	
	function clean_path( $path ) {
		
		// prevent Poison Null Byte injections
		$path = str_replace( chr( 0 ), '', $path );
		
		// prevent go out of the workspace
		while ( strpos( $path, '../' ) !== false ) {
			
			$path = str_replace( '../', '', $path );
		}
		return $path;
	}
	
	function clean_username( $username ) {
		
		return strtolower( preg_replace( self::PATH_REGEX, '-', $username ) );
	}
	
	function create_config() {
		
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
define("BASE_PATH", "' . $this->path . '");

// BASE URL TO CODIAD (without trailing slash)
define("BASE_URL", "' . $_SERVER["HTTP_HOST"] . $this->rel . '");

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
define( "DBHOST", \'' . addslashes( urldecode( $_POST['dbhost'] ) ) . '\' );
define( "DBNAME", \'' . addslashes( urldecode( $_POST['dbname'] ) ) . '\' );
define( "DBUSER", \'' . addslashes( urldecode( $_POST['dbuser'] ) ) . '\' );
define( "DBPASS", \'' . addslashes( urldecode( $_POST['dbpass'] ) ) . '\' );
define( "DBTYPE", \'' . addslashes( urldecode( $_POST['dbtype'] ) ) . '\' );

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
		return file_put_contents( $this->config, $config_data );
	}
	
	function create_project() {
		
		$project_path = $this->project_path;
		$connection = $this->sql->connect();
		
		if ( ! $this->is_abs_path( $project_path ) ) {
			
			$project_path = preg_replace( self::PATH_REGEX, '-', $project_path );
			$project_path = $this->username . "/" . $project_path;
			
			if( ! is_dir( $this->workspace . "/" . $project_path ) ) {
				
				mkdir( $this->workspace . "/" . $project_path, 0755, true );
			}
		} else {
			
			if ( substr( $project_path, -1 ) == '/' ) {
				
				$project_path = substr( $project_path, 0, strlen( $project_path ) - 1 );
			}
			if ( ! file_exists( $project_path ) ) {
				
				if ( ! mkdir( $project_path . '/', 0755, true ) ) {
					
					die( '{"message": "Unable to create Absolute Path"}' );
				}
			} else {
				
				if ( ! is_writable( $project_path ) || ! is_readable( $project_path ) ) {
					
					die( '{"message": "No Read/Write Permission"}' );
				}
			}
		}
		
		$query = "DELETE FROM projects WHERE path = ?;";
		$bind_variables = array(
			$project_path
		);
		$statement = $connection->prepare( $query );
		$statement->execute( $bind_variables );
		
		$query = "INSERT INTO projects(name, path, owner) VALUES (?,?,( SELECT id FROM users WHERE username = ? LIMIT 1 ));";
		$bind_variables = array(
			$this->project_name,
			$project_path,
			$this->username
		);
		$statement = $connection->prepare( $query );
		$statement->execute( $bind_variables );
	}
	
	function create_tables() {
		
		$result = $this->sql->create_default_tables();
		
		if ( ! $result["create_tables"] === true ) {
			
			exit( json_encode( $result ) );
		}
	}
	
	function create_user() {
		
		$bind_variables = array(
			$this->username,
			$this->password,
			$this->project_path,
			Permissions::LEVELS["admin"]
		);
		$query = "INSERT INTO users( username, password, project, access ) VALUES ( ?,?,( SELECT id FROM projects WHERE path = ? LIMIT 1 ),? )";
		
		try {
			
			$connection = $this->sql->connect();
			$statement = $connection->prepare( $query );
			$statement->execute( $bind_variables );
		} catch( exception $e ) {
			
			exit( "Error could not create user: " . $e->getMessage() );
		}
		$this->set_default_options();
	}
	
	function encrypt_password( $string ) {
		
		return sha1( md5( $string ) );
	}
	
	function is_abs_path( $path ) {
		
		return $path[0] === '/';
	}
	
	function install() {
		
		$project_name = $_POST['project_name'];
		if ( isset( $_POST['project_path'] ) ) {
			
			$project_path = $_POST['project_path'];
		} else {
			
			$project_path = $project_name;
		}
		$timezone = $_POST['timezone'];
		
		$dbtype = $_POST['dbtype'];
		$dbhost = $_POST['dbhost'];
		$dbname = $_POST['dbname'];
		$dbuser = $_POST['dbuser'];
		$dbpass = $_POST['dbpass'];
		
		$connection = $this->sql->connect();
		
		$this->create_tables();
		$this->create_user();
		$this->create_project();
		//exit( "stop" );
		$this->create_config();
		return "success";
	}
	
	function JSEND( $message, $error=null ) {
		
		$message = array(
			"message" => $message
		);
		
		if( ! $error === null ) {
			
			$message["error"] = $error;
		}
		exit( json_encode( $message ) );
	}
	
	public function set_default_options() {
		
		foreach( Settings::DEFAULT_OPTIONS as $id => $option ) {
			
			$query = array(
				"*" => "INSERT INTO user_options ( name, user, value ) VALUES ( ?, ( SELECT id FROM users WHERE username = ? ), ? );",
				"pgsql" => 'INSERT INTO user_options ( name, "user", value ) VALUES ( ?, ( SELECT id FROM users WHERE username = ? ), ? );',
			);
			$bind_variables = array(
				$option["name"],
				$this->username,
				$option["value"],
			);
			$result = $this->sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $result == 0 ) {
				
				$query = array(
					"*" => "UPDATE user_options SET value=? WHERE name=? AND user=( SELECT id FROM users WHERE username = ? );",
					"pgsql" => 'UPDATE user_options SET value=? WHERE name=? AND "user"=( SELECT id FROM users WHERE username = ? );',
				);
				$bind_variables = array(
					$option["value"],
					$option["name"],
					$this->username,
				);
				$result = $this->sql->query( $query, $bind_variables, 0, "rowCount" );
			}
		}
	}
}

$Install = new Install();

?>
