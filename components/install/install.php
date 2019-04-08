<?php

class Install {
	
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
		$this->db_types = sql::db_types;
		
		$this->check();
		
		require_once( "../sql/class.sql.php" );
		$this->sql = new sql();
		$this->install();
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
			
			$this->JSEND( "Invalid database. Please select one of the following: " . implode( ", ", $db_types ),  addslashes( json_encode( array( $dbtype, $db_types ) ) ) );
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
		
		return strtolower( preg_replace( '#[^A-Za-z0-9' . preg_quote( '-_@. ').']#', '', $username ) );
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
define( "DBHOST", "' . $_POST['dbhost'] . '" );
define( "DBNAME", "' . $_POST['dbname'] . '" );
define( "DBUSER", "' . $_POST['dbuser'] . '" );
define( "DBPASS", "' . $_POST['dbpass'] . '" );
define( "DBTYPE", "' . $_POST['dbtype'] . '" );

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
	
	function create_project() {
		
		$project_path = $this->clean_path( $project_path );
			
		if ( ! $this->is_abs_path( $project_path ) ) {
			
			$project_path = str_replace( " ", "_", preg_replace( '/[^\w-\.]/', '', $project_path ) );
			if( ! is_dir( $workspace . "/" . $project_path ) ) {
				
				mkdir( $workspace . "/" . $project_path );
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
		
		$bind_variables = array(
			$project_name,
			$project_path,
			$username
		);
		$query = "INSERT INTO projects(name, path, owner) VALUES (?,?,?);";
		$statement = $connection->prepare( $query );
		$statement->execute( $bind_variables );
		$error = $statement->errorInfo();
		
		if( ! $error[0] == "00000" ) {
			
			die( '{"message":"Could not create project in database.","error":"' . addslashes(json_encode( $error )) .'"}' );
		}
	}
	
	function create_tables() {
		
		$this->sql->create_tables(
			array(
				"options" => array(
					"fields" => array(
						"id" => "int",
						"name" => "string",
						"value" => "text",
					),
					"attributes" => array(
						"id" => array( "id" ),
						"name" => array( "not null", "unique" ),
						"value" => array( "not null" ),
					)
				),
				"projects" => array(
					"fields" => array(
						"id" => "int",
						"name" => "string",
						"path" => "text",
						"owner" => "string",
						"access" => "string",
					),
					"attributes" => array(
						
						"id" => array( "id" ),
						"name" => array( "not null" ),
						"path" => array( "not null", "unique" ),
						"owner" => array( "not null", "unique" ),
						"access" => array( "not null" ),
					)
				),
				"users" => array(
					"fields" => array(
						"id" => "int",
						"first_name" => "string",
						"last_name" => "string",
						"username" => "string",
						"password" => "text",
						"email" => "string",
						"project" => "string",
						"access" => "string",
						"groups" => "string",
						"token" => "string",
					),
					"attributes" => array(
						"id" => array( "id" ),
						"username" => array( "not null", "unique" ),
						"password" => array( "not null" ),
						"access" => array( "not null" ),
					)
				),
				"user_options" => array(
					"fields" => array(
						"id" => "int",
						"name" => "string",
						"username" => "string",
						"value" => "text",
					),
					"attributes" => array(
						"id" => array( "id" ),
						"name" => array( "not null", "unique" ),
						"username" => array( "not null", "unique" ),
						"value" => array( "not null" ),
					)
				),
			)
		);
	}
	
	function create_user() {
		
		$bind_variables = array(
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
		$query = "INSERT INTO users(first_name, last_name, username, password, email, project, access, groups, token) VALUES (?,?,?,?,?,?,?,?,?)";
		$statement = $connection->prepare( $query );
		$statement->execute( $bind_variables );
		$error = $statement->errorInfo();
		
		if( ! $error[0] == "00000" ) {
			
			die( '{"message":"Could not create user in database.","error":"' . addslashes(json_encode( $error )) .'"}' );
		}
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
		$this->create_project();
		$this->create_user();
		$this->create_config();
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
	
}

$Install = new Install();

?>
