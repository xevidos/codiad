<?php
/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), Isaac Brown (telaaedifex.com),
*  distributed as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

//////////////////////////////////////////////////////////////////////
// Paths
//////////////////////////////////////////////////////////////////////

$path = $_POST['path'];

$rel = str_replace( '/components/install/process.php', '', $_SERVER['REQUEST_URI'] );

$workspace = $path . "/workspace";
$users = $path . "/data/users.php";
$projects = $path . "/data/projects.php";
$active = $path . "/data/active.php";
$sessions = $path . "/data/sessions";
$config = $path . "/config.php";

//////////////////////////////////////////////////////////////////////
// Functions
//////////////////////////////////////////////////////////////////////

function saveFile( $file, $data ) {
	
	$write = fopen( $file, 'w' ) or die( '{"message": "can\'t open file"}' );
	fwrite( $write, $data );
	fclose( $write );
}

function saveJSON( $file, $data ) {
	
	$data = "<?php/*|\r\n" . json_encode( $data ) . "\r\n|*/?>";
	saveFile( $file, $data );
}

function encryptPassword( $p ) {
	
	return sha1( md5( $p ) );
}

function cleanUsername( $username ) {
	
	return preg_replace( '#[^A-Za-z0-9' . preg_quote( '-_@. ' ).  ']#', '', $username );
}

function isAbsPath( $path ) {
	
	return $path[0] === '/';
}

function cleanPath( $path ) {
	
	// prevent Poison Null Byte injections
	$path = str_replace( chr( 0 ), '', $path );
	
	// prevent go out of the workspace
	while ( strpos( $path, '../' ) !== false ) {
		
		$path = str_replace( '../', '', $path );
	}
	return $path;
}

//////////////////////////////////////////////////////////////////////
// Verify no overwrites
//////////////////////////////////////////////////////////////////////

if ( ! ( defined( 'DBHOST' ) && defined( 'DBNAME' ) && defined( 'DBUSER' ) && defined( 'DBPASS' ) && defined( 'DBTYPE' ) ) ) {
	
	//////////////////////////////////////////////////////////////////
	// Get POST responses
	//////////////////////////////////////////////////////////////////
	
	$username = cleanUsername( $_POST['username'] );
	$password = encryptPassword( $_POST['password'] );
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
	
	//Valid databases Codiad is able to use
	$db_types = [
		'mysql',
		'postgresql',
		//'sqlite',
	];

	//Is selected database type valid?
	if( ! in_array( $dbtype, $db_types ) ) {
		
		die( '{"message": "Invalid database. Please select one of the following: ' . implode( ", ", $db_types ) . '.", "error": "' . addslashes(json_encode( array( $dbtype, $db_types ) ) ) . '"}' );
	}

	try {
		
		$connection = new PDO( "{$dbtype}:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass );
	} catch( PDOException $e ) {
		
		die( '{"message":"Could not connect to database.","error":"' . addslashes( json_encode( $e->getMessage() ) ) .'"}' );
	}
	$bind_vars = array();
	$bind = "";
	$database_sql_fullpath = $path . '/components/install/sql/' . $dbtype . '.sql';
	if( ! is_file( $database_sql_fullpath ) ) {
		
        die( '{"message":"Could not find the sql script for the database type: ' . $dbtype . '","error":"' . addslashes( json_encode( array( "path" => $database_sql_fullpath, "dbtype" => $dbtype ) ) ) .'"}' );
	}
    $sql = file_get_contents( $database_sql_fullpath );

	try {
		
		//Create the database
		$result = $connection->exec( $sql );
	} catch( PDOException $e ) {
		
		die( '{"message":"Could not create initial tables in database.","error":"' . addslashes( json_encode( $e->getMessage() ) ) .'"}' );
	}
	
	$error = $connection->errorInfo();
	if( ! $error[0] == "00000" ) {
		
		die( '{"message":"Could not create initial tables in database.","error":"' . addslashes( json_encode( $error ) ) .'"}' );
	}
	
	//////////////////////////////////////////////////////////////////
	// Create Projects files
	//////////////////////////////////////////////////////////////////
	
	$project_path = cleanPath( $project_path );
	
	if ( ! isAbsPath( $project_path ) ) {
		
		$project_path = str_replace( " ", "_", preg_replace( '/[^\w-\.]/', '', $project_path ) );
		if( ! is_dir( $workspace . "/" . $project_path ) ) {
			
			mkdir( $workspace . "/" . $project_path );
		}
	} else {
		
		$project_path = cleanPath( $project_path );
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
	

	/**
	* Create sessions path.
	*/
	
	if ( ! is_dir( $sessions ) ) {
		
		mkdir( $sessions, 00755 );
	}
	
	//////////////////////////////////////////////////////////////////
	// Create Active file
	//////////////////////////////////////////////////////////////////
	
	saveJSON( $active, array( '' ) );
	
	//////////////////////////////////////////////////////////////////
	// Create Config
	//////////////////////////////////////////////////////////////////
	
	
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
define("BASE_PATH", "' . $path . '");

// BASE URL TO CODIAD (without trailing slash)
define("BASE_URL", "' . $_SERVER["HTTP_HOST"] . $rel . '");

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
