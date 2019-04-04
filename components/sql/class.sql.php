<?php

require_once( "./class.sql.conversions.php" );

class sql {
	
	public $connection = null;
	public $conversions = null;
	public $identifier_character = null;
	protected static $instance = null;
	
	public function __construct() {
		
		$this->conversions = new sql_conversions();
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
	
	public static function create_table( $table_name, $fields=array(), $attributes=array() ) {
		
		$dbtype = DBTYPE;
		$query = "{$this->conversions->actions["create"][$dbtype]} {$table_name} (";
		
		foreach( $fields as $id => $type ) {
			
			$query .= "{$id} {$this->conversions->data_types[$type][$dbtype]}";
			
			foreach( $attributes[$id] as $attribute ) {
				
				$attribute_string = $this->conversions->specials["$attribute"];
				
				if( ! strpos( $attribute_string, "%table_name%" ) === FALSE ) {
					
					$attribute_string = str_replace( "%table_name%", $table_name, $attribute_string );
				}
				
				if( ! strpos( $attribute_string, "%fields%" ) === FALSE ) {
					
					$fields_string = "";
					
					foreach( $fields as $field ) {
						
						$fields_string .= "field,";
					}
					
					$fields_string = substr( $fields_string, 0, -1 );
					$attribute_string = str_replace( "%fields%", $fields_string, $attribute_string );
				}
				$query .= " {$attribute_string}";
			}
			$query .= ",";
		}
		
		$query .= ");";
		
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