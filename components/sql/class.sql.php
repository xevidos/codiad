<?php

require_once( __DIR__ . "/class.sql.conversions.php" );

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
	
	public function create_table( $table_name, $fields=array(), $attributes=array() ) {
		
		$query = $this->conversions->table( $table_name, $fields, $attributes );
		//$this->query( $query, array(), array(), null, "rowCount" );
	}
	
	public function create_tables( $table ) {
		
		/**
		Tables layout
		array(
			
			"table_name" => array(
				
				"fields" => array(
					
					"id" => "int",
					"test_field" => "string"
				),
				"attributes" => array(
					
					"id" => array( "id" ),
					"test_field" => array( "not null" ),
				)
			),
			"table2_name" => array(
				
				"fields" => array(
					
					"id" => "int",
					"test_field" => "string"
				),
				"attributes" => array(
					
					"id" => array( "id" ),
					"test_field" => array( "not null" ),
				)
			)
		);
		*/
		
		$query = $this->conversions->tables( $table );
		//echo var_dump( $query ) . "<br>";
		$result = $this->query( $query, array(), array() );
	}
	
	public static function escape_identifier( $i ) {
		
		$i = preg_replace('/[^A-Za-z0-9_]+/', '', $i );
		return $i;
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
	
	public function select( $table, $fields=array(), $where=array() ) {
		
		$array = $this->conversions->select( $table, $fields, $where );
		$query = $array[0];
		$bind_vars = $array[1];
		$result = $this->query( $query, $bind_vars, array() );
		//echo var_dump( $query, $bind_vars ) . "<br>";
		return $result;
	}
	
	public function update( $table, $fields=array(), $where=array() ) {
		
		$query = $this->conversions->update( $table, $fields, $where );
		//echo var_dump( $query ) . "<br>";
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
		
		//echo var_dump( $error, $return );
		
		$this->close();
		return( $return );
	}
}
?>