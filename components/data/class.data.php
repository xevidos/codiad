<?php

require_once( "class.filesystemstorage.php" );

class Data {
	
	const DB_TYPES = array(
		"Filesystem" => "filesystem",
		"MySQL" => "mysql",
		"PostgreSQL" => "pgsql",
	);
	const DB_REQUIREMENTS = array(
		
		"mysql" => array(
			"PDO",
			"pdo_mysql"
		),
		"pgsql" => array(
			"PDO",
			"pdo_pgsql"
		),
	);
	
	public $connection = null;
	public $fss = null;
	protected static $instance = null;
	
	function __construct() {
		
		if( DBTYPE === "filesystem" ) {
			
			$this->fss = FileSystemStorage::get_instance();
		}
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
			$options = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			);
			
			$this->connection = new PDO( "{$dbtype}:host={$host};dbname={$dbname}", $username, $password, $options );
		}
		
		return $this->connection;
	}
	
	/**
	 * Return an instance of this class.
	 *
	 * @since	${current_version}
	 * @return	object	A single instance of this class.
	 */
	public static function get_instance() {
		
		if( null == self::$instance ) {
			
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	function install( $dbtype ) {
		
		$return = Common::get_default_return();
		$pass = true;
		
		if( ! in_array( $dbtype, array_values( self::DB_TYPES ) ) ) {
			
			$return["status"] = "error";
			$return["message"] = "Storage type is not supported.";
			$pass = false;
		}
		
		if( $pass ) {
			
			if( $dbtype === "filesystem" ) {
				
				$fss = FileSystemStorage::get_instance();
				$return = $fss->install();
			} else {
				
				$script = __DIR__ . "/sql/" . DBTYPE . ".sql";
				
				if( ! is_file( $script ) ) {
					
					return "Error, no database scripts specified for currently selected dbtype.";
				}
				
				try {
					
					$query = file_get_contents( $script );
					$connection = $this->connect();
					$result = $connection->exec( $query );
					
					$return["status"] = "success";
					$return["message"] = "Created default tables.";
					$return["value"] = $result;
				} catch( Throwable $error ) {
					
					$return["status"] = "error";
					$return["message"] = "Error creating default tables.";
					$return["value"] = $error->getMessage();
					$pass = false;
				}
			}
		}
		return $return;
	}
	
	public function query( $query, $bind_vars, $default, $action='fetchAll', $errors="default" ) {
		
		$return = Common::get_default_return();
		
		if( is_array( $query ) ) {
			
			if( in_array( DBTYPE, array_keys( $query ) ) ) {
				
				$query = $query[DBTYPE];
			} else {
				
				$return = $default;
				
				if( $errors == "message" ) {
					
					$return = json_encode( array( "error" => "No query specified for database type." ) );
				} elseif( $errors == "exception" ) {
					
					throw new Error( "No query specified for database type." );
				}
				return $return;
			}
		}
		
		if( is_callable( $query ) ) {
			
			$return = call_user_func( $query );
		} else {
			
			try {
				
				$connection = $this->connect();
				$statement = $connection->prepare( $query );
				$statement->execute( $bind_variables );
				
				switch( $action ) {
					
					case( 'rowCount' ):
						
						$return = $statement->rowCount();
					break;
					
					case( 'fetch' ):
						
						$return = $statement->fetch( \PDO::FETCH_ASSOC );
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
			} catch( Throwable $error ) {
				
				$return = $default;
				
				if( $errors == "message" ) {
					
					$return = json_encode( array( $error->getMessage() ) );
				} elseif( $errors == "exception" ) {
					
					throw $error;
				}
			}
			$this->close();
		}
		return $return;
	}
}

?>