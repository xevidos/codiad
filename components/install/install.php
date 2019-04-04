<?php

class Install {
	
	public $active = "";
	public $config = "";
	public $db_types = array();
	public $projects = "";
	public $sessions = "";
	public $sql = null;
	public $users = "";
	public $workspace = "";
	
	function __construct() {
		
		$path = $_POST['path'];
		$rel = str_replace( '/components/install/process.php', '', $_SERVER['REQUEST_URI'] );
		
		$this->active = $path . "/data/active.php";
		$this->config = $path . "/config.php";
		$this->projects = $path . "/data/projects.php";
		$this->sessions = $path . "/data/sessions";
		$this->users = $path . "/data/users.php";
		$this->workspace = $path . "/workspace";
		$this->db_types = sql::db_types;
		
		$this->check();
		
		require_once( "../sql/class.sql.php" );
		$this->sql = new sql();
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
