<?php

require_once( __DIR__ . "/class.sql.conversions.php" );
require_once( __DIR__ . "/../permissions/class.permissions.php" );

class sql {
	
	const DB_TYPES = array(
		
		"MySQL" => "mysql",
		"PostgreSQL" => "pgsql",
		//"SQLite" => "sqlite",
	);
	
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
			$options = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			);
			
			$this->connection = new PDO( "{$dbtype}:host={$host};dbname={$dbname}", $username, $password, $options );
		}
		
		return( $this->connection );
	}
	
	public function create_table( $table_name, $fields=array(), $attributes=array() ) {
		
		$query = $this->conversions->table( $table_name, $fields, $attributes );
		//$this->query( $query, array(), array(), null, "rowCount" );
	}
	
	public function create_default_tables() {
		
		$create_tables = $this->create_tables(
			array(
				"active" => array(
					"fields" => array(
						"username" => "string",
						"path" => "text",
						"position" => "string",
						"focused" => "string"
					),
					"attributes" => array(
						"username" => array( "not null" ),
						"path" => array( "not null" ),
						"focused" => array( "not null" ),
					)
				),
				"access" => array(
					"fields" => array(
						"project" => "int",
						"user" => "int",
						"level" => "int",
					),
					"attributes" => array(
						"id" => array( "not null" ),
						"user" => array( "not null" ),
						"level" => array( "not null" ),
					)
				),
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
					),
					"attributes" => array(
						
						"id" => array( "id" ),
						"name" => array( "not null" ),
						"path" => array( "not null", "unique" ),
						"owner" => array( "not null", "unique" ),
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
						"project" => "int",
						"access" => "string",
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
		$structure_updates = $this->update_table_structure();
		$result = array(
			"create_tables" => $create_tables,
			"structure_updates" => $structure_updates
		);
		exit( json_encode( $result, JSON_PRETTY_PRINT ) );
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
		
		try { 
			
			$query = $this->conversions->tables( $table );
			$connection = $this->connect();
			$result = $connection->exec( $query );
			return true;
		} catch( exception $error ) {
			
			return $error->getMessage();
		}
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
		//return $query;
	}
	
	public function update_table_structure() {
		
		$status_updates = array();
		$sql_conversions = new sql_conversions();
		
		try {
			
			$access_query = "INSERT INTO access( project, user, level ) VALUES ";
			$projects = $this->query( "SELECT id, access FROM projects", array(), array(), "fetchAll", "exception" );
			$users = $this->query( "SELECT id, username FROM users", array(), array(), "fetchAll", "exception" );
			$delete = Permissions::LEVELS["delete"];
			
			foreach( $users as $row => $user ) {
				
				foreach( $projects as $row => $project ) {
					
					$access = json_decode( $project["access"], true );
					if( ! is_array( $access ) || empty( $access ) ) {
						
						continue;
					}
					
					foreach( $access as $granted_user ) {
						
						if( $granted_user == $user["username"] ) {
							
							$access_query .= "( {$project["id"]}, {$user["id"]}, $delete ),";
						}
					}
				}
			}
			
			if( $access_query !== "INSERT INTO access( project, user, level ) VALUES " ) {
				
				$result = $this->query( substr( $access_query, 0, -1 ), array(), 0, "rowCount", "exception" );
			}
			$result = $this->query( "ALTER TABLE projects DROP COLUMN access", array(), 0, "rowCount" );
			$status_updates["access_column"] = "Cached data and removed access column.";
		} catch( Exception $error ) {
			
			//The access field is not there.
			//echo var_export( $error->getMessage(), $access_query );
			$status_updates["access_column"] = array(
				"error_message" => $error->getMessage(),
				"dev_message" => "No access column to convert."
			);
		}
		
		try {
			
			$update_query = "";
			$projects = $this->query( "SELECT id, path FROM projects", array(), array(), "fetchAll", "exception" );
			$result = $this->query( "SELECT project FROM users", array(), array(), "fetchAll", "exception" );
			$convert = false;
			$delete = Permissions::LEVELS["delete"];
			
			foreach( $result as $row => $user ) {
				
				if( ! is_numeric( $user["project"] ) ) {
					
					$convert = true;
				}
				
				foreach( $projects as $row => $project ) {
					
					if( $project["path"] == $user["project"] ) {
						
						$update_query .= "UPDATE users SET project={$project["id"]};";
					}
				}
			}
			
			if( $convert && strlen( $update_query ) > 0 ) {
				
				//change project to users table
				$result = $this->query( "ALTER TABLE users DROP COLUMN project", array(), array(), "rowCount", "exception" );
				$result = $this->query( "ALTER TABLE users ADD COLUMN project " . $sql_conversions->data_types["int"][DBTYPE], array(), array(), "rowCount", "exception" );
				$result = $this->query( $update_query, array(), array(), "rowCount", "exception" );
			} else {
				
				$status_updates["users_current_project"] = array( "dev_message" => "Users current project column to project_id conversion not needed." );
			}
		} catch( Exception $error ) {
			
			//echo var_dump( $error->getMessage() );
			$status_updates["users_current_project"] = array(
				"error_message" => $error->getMessage(),
				"dev_message" => "Users current project column to project_id conversion failed."
			);
		}
		
		try {
			
			$result = $this->query( "ALTER TABLE users DROP COLUMN groups", array(), array(), "rowCount", "exception" );
			$status_updates["users_groups_column"] = array( "dev_message" => "Removal of the groups column from the users table succeeded." );
		} catch( Exception $error ) {
			
			//echo var_dump( $error->getMessage() );
			$status_updates["users_groups_column"] = array(
				"error_message" => $error->getMessage(),
				"dev_message" => "Removal of the groups column from the users table failed.  This usually means there was never one to begin with"
			);
		}
		
		if( DBTYPE === "mysql" || DBTYPE === "pgsql" ) {
			
			$constraint = ( DBTYPE === "mysql" ) ? "INDEX" : "CONSTRAINT";
			
			try {
				
				$projects = $this->query( "ALTER TABLE projects DROP $constraint path1500owner255;", array(), 0, "rowCount", "exception" );
			} catch( Exception $error ) {
				
				//echo var_dump( $error->getMessage() );
				$status_updates["path_owner_constraint"] = array(
					"error_message" => $error->getMessage(),
					"dev_message" => "Removal of path1500owner255 constraint in the projects table failed.  This usually means there was never one to begin with"
				);
			}
			
			try {
				
				$projects = $this->query( "ALTER TABLE active DROP $constraint username255path1500;", array(), 0, "rowCount", "exception" );
			} catch( Exception $error ) {
				
				//echo var_dump( $error->getMessage() );
				$status_updates["username_path_constraint"] = array(
					"error_message" => $error->getMessage(),
					"dev_message" => "Removal of username255path1500 constraint in the active table failed.  This usually means there was never one to begin with"
				);
			}
		}
		return $status_updates;
	}
	
	public function query( $query, $bind_variables, $default, $action='fetchAll', $errors="default" ) {
		
		/**
		 * Errors:
		 * default - this value could be anything such as true or foobar
		 * message
		 * exception
		 */
		
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
		} catch( exception $error ) {
			
			$return = $default;
			
			if( $errors == "message" ) {
				
				$return = json_encode( array( $error->getMessage() ) );
			} elseif( $errors == "exception" ) {
				
				throw $error;
			}
		}
		$this->close();
		return( $return );
	}
}
?>