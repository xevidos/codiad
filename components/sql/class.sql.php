<?php

class sql {
	
	public $connection = null;
	
	public function __construct() {
		
	}
	
	public static function connect() {
		
		$host = DBHOST;
		$dbname = DBNAME;
		$username = DBUSER;
		$password = DBPASS;
		$connection = mysqli_connect( $host, $username, $password, $dbname ) or die ( formatJSEND( "error", 'Error connecting to mysql database.  Please contact the website administrator.' ) );
		
		return( $connection );
	}
	
	public static function sql( $sql, $bind, $bind_variables, $error ) {
		
		$connection = self::connect();
		$result = mysqli_prepare( $connection, $sql ) or die( $error );
		
		$result->bind_param( $bind, ...$bind_variables );
		$result->execute();
		$return = $result->get_result();
		
		if( $connection->error ) {
			
			$return = $connection->error;
		}
		
		$connection->close();
		return( $return );
	}
}
?>