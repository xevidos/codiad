<?php


class sql {
	
	public $connection = null;
	public $identifier_character = null;
	protected static $instance = null;
	
	public function __construct() {
		
		
	}
	
	public function close() {
		
		$this->connection = null;
	}
	
	public function connect() {
		
		if( $this->connection == null ) {
			
			$host = DBHOST;
			$dbname = DBNAME;
			$dbtype = DBTYPE;
			$username = DBUSER;
			$password = DBPASS;
			
			$this->connection = new PDO( "{$dbtype}:host={$host};dbname={$dbname}", $username, $password );
		}
		
		return( $this->connection );
	}
	
	public static function escape_identifier( $i ) {
		
		$i = preg_replace('/[^A-Za-z0-9_]+/', '', $i );
		$i = $i;
	}
	
	public static function is_not_error( $i ) {
		
		$return = false;
		$result = json_decode( $i );
		
		if ( json_last_error() !== JSON_ERROR_NONE || ( ! $i == NULL && ! $i["status"] == "error" ) ) {
			
			$return = true;
		}
		return( $return );
	}
	
	public static function get_instance() {
		
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function query( $query, $bind_variables, $default, $action='fetchAll' ) {
		
		$connection = $this->connect();
		$statement = $connection->prepare( $query );
		$statement->execute( $bind_variables );
		 
		switch( $action ) {
			
			case( 'rowCount' ):
				
				$return = $statement->rowCount();
			break;
			
			case( 'fetchAll' ):
				
				$return = $statement->fetchAll( \PDO::FETCH_ASSOC );
			break;
			
			case( 'fetchColumn' ):
				
				$return = $statement->fetchColumn();
			break;
			
			default:
				
				$return = $statement->fetchAll( \PDO::FETCH_ASSOC );
			break;
		}
		
		$error = $statement->errorInfo();
		if( ! $error[0] == "00000" ) {
			
			echo var_export( $error );
			echo var_export( $return );
			$return = $default;
		}
		
		$this->close();
		return( $return );
	}
}
?>