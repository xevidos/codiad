<?php

require_once( __DIR__ . "/../permissions/class.permissions.php" );

class sql {
	
	const DB_TYPES = array(
		
		"MySQL" => "mysql",
		"PostgreSQL" => "pgsql",
		//"SQLite" => "sqlite",
	);
	
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
			$options = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			);
			
			$this->connection = new PDO( "{$dbtype}:host={$host};dbname={$dbname}", $username, $password, $options );
		}
		
		return( $this->connection );
	}
	
	public function create_default_tables() {
		
		$create_tables = $this->create_tables();
		$structure_updates = $this->update_table_structure();
		$result = array(
			"create_tables" => $create_tables,
			"structure_updates" => $structure_updates
		);
		return $result;
	}
	
	public function create_tables() {
		
		$script = __DIR__ . "/scripts/" . DBTYPE . ".sql";
		
		if( ! is_file( $script ) ) {
			
			return "Error, no database scripts specified for currently selected dbtype.";
		}
		
		try {
			
			$query = file_get_contents( $script );
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
	
	public function update_table_structure() {
		
		$status_updates = array();
		
		if( DBTYPE === "mysql" || DBTYPE === "pgsql" ) {
			
			try {
				
				$access_query = array(
					"mysql" => "INSERT INTO access( project, user, level ) VALUES ",
					"pgsql" => 'INSERT INTO access( project, "user", level ) VALUES ',
				);
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
								
								$access_query[DBTYPE] .= "( {$project["id"]}, {$user["id"]}, $delete ),";
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
				$result = $this->query( "SELECT username,project FROM users", array(), array(), "fetchAll", "exception" );
				$convert = false;
				$delete = Permissions::LEVELS["delete"];
				
				foreach( $result as $row => $user ) {
					
					if( ! is_numeric( $user["project"] ) ) {
						
						$convert = true;
					}
					
					foreach( $projects as $row => $project ) {
						
						if( $project["path"] == $user["project"] ) {
							
							$update_query .= "UPDATE users SET project={$project["id"]} WHERE username = '{$user["username"]}';";
						}
					}
				}
				
				if( $convert && strlen( $update_query ) > 0 ) {
					
					//change project to users table
					$result = $this->query( "ALTER TABLE users DROP COLUMN project", array(), array(), "rowCount", "exception" );
					$result = $this->query( "ALTER TABLE users ADD COLUMN project INT", array(), array(), "rowCount", "exception" );
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
				
				$this->query( array(
					"mysql" => "ALTER TABLE user_options DROP INDEX name255username255;",
					"pgsql" => "ALTER TABLE user_options DROP CONSTRAINT name255username255;",
				), array(), 0, "rowCount", "exception" );
			} catch( Exception $error ) {
				
				//The access field is not there.
				//echo var_export( $error->getMessage(), $access_query );
				$status_updates["nameusername_user_option_constraint"] = array(
					"error_message" => $error->getMessage(),
					"dev_message" => "No constriant to remove."
				);
			}
			
			try {
				
				$update_query = array(
					"mysql" => "",
					"pgsql" => "",
				);
				$options = $this->query( "SELECT id, name, username, value FROM user_options", array(), array(), "fetchAll", "exception" );
				$users = $this->query( "SELECT id, username FROM users", array(), array(), "fetchAll", "exception" );
				$delete = Permissions::LEVELS["delete"];
				
				foreach( $users as $row => $user ) {
					
					foreach( $options as $row => $option ) {
						
						if( $option["username"] == $user["username"] ) {
							
							if( DBTYPE == "mysql" ) {
								
								$update_query[DBTYPE] .= "UPDATE user_options SET user={$user["id"]} WHERE id={$option["id"]};";
							} else {
								
								$update_query[DBTYPE] .= "UPDATE user_options SET \"user\"={$user["id"]} WHERE id={$option["id"]};";
							}
						}
					}
				}
				
				if( strlen( $update_query ) > 0 ) {
					
					//change project to users table
					$result = $this->query( "ALTER TABLE user_options DROP COLUMN username", array(), array(), "rowCount", "exception" );
					$result = $this->query( array(
						"mysql" => "ALTER TABLE user_options ADD COLUMN user INT",
						"pgsql" => 'ALTER TABLE user_options ADD COLUMN "user" INT',
					), array(), array(), "rowCount", "exception" );
					$result = $this->query( $update_query, array(), array(), "rowCount", "exception" );
				} else {
					
					$status_updates["username_user_option_column"] = array( "dev_message" => "User options username column needed no conversion." );
				}
			} catch( Exception $error ) {
				
				//The access field is not there.
				//echo var_export( $error->getMessage(), $access_query );
				$status_updates["username_user_option_column"] = array(
					"error_message" => $error->getMessage(),
					"dev_message" => "No username column to convert."
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
			
			try {
				
				$projects = $this->query( array(
					"mysql" => "ALTER TABLE projects DROP INDEX path1500owner255;",
					"pgsql" => "ALTER TABLE projects DROP CONSTRAINT path1500owner255;",
				), array(), 0, "rowCount", "exception" );
			} catch( Exception $error ) {
				
				//echo var_dump( $error->getMessage() );
				$status_updates["path_owner_constraint"] = array(
					"error_message" => $error->getMessage(),
					"dev_message" => "Removal of path1500owner255 constraint in the projects table failed.  This usually means there was never one to begin with"
				);
			}
			
			try {
				
				$convert = false;
				$update_query = "";
				$projects = $this->query( "SELECT id, name, path, owner FROM projects", array(), array(), "fetchAll", "exception" );
				$users = $this->query( "SELECT id, username FROM users", array(), array(), "fetchAll", "exception" );
				$delete = Permissions::LEVELS["delete"];
				
				foreach( $projects as $row => $project ) {
					
					if( ! is_numeric( $project["owner"] ) ) {
						
						$convert = true;
					}
					
					$current_user = null;
					
					foreach( $users as $row => $user ) {
						
						if( $project["owner"] == $user["username"] ) {
							
							$update_query .= "UPDATE projects SET owner={$user["id"]} WHERE id={$project["id"]};";
							$current_user = $user;
							break;
						}
					}
					
					if( $current_user != null && $project["owner"] != $current_user["username"] ) {
						
						$update_query .= "UPDATE projects SET owner=-1 WHERE id={$project["id"]};";
					}
				}
				
				if( strlen( $update_query ) > 0 && $convert ) {
					
					//change project to users table
					$result = $this->query( "ALTER TABLE projects DROP COLUMN owner", array(), array(), "rowCount", "exception" );
					$result = $this->query( "ALTER TABLE projects ADD COLUMN owner INT", array(), array(), "rowCount", "exception" );
					$result = $this->query( $update_query, array(), array(), "rowCount", "exception" );
				} else {
					
					$status_updates["owner_projects_column"] = array( "dev_message" => "User projects owner column needed no conversion." );
				}
			} catch( Exception $error ) {
				
				//The access field is not there.
				//echo var_export( $error->getMessage(), $access_query );
				$status_updates["username_user_option_column"] = array(
					"error_message" => $error->getMessage(),
					"dev_message" => "No username column to convert."
				);
			}
			
			try {
				
				$projects = $this->query( array(
					"mysql" => "ALTER TABLE active DROP INDEX username255path1500;",
					"pgsql" => "ALTER TABLE active DROP CONSTRAINT username255path1500;",
				), array(), 0, "rowCount", "exception" );
			} catch( Exception $error ) {
				
				//echo var_dump( $error->getMessage() );
				$status_updates["username_path_constraint"] = array(
					"error_message" => $error->getMessage(),
					"dev_message" => "Removal of username255path1500 constraint in the active table failed.  This usually means there was never one to begin with"
				);
			}
			
			try {
				
				$result = $this->query( "DELETE FROM active;", array(), 0, "rowCount", "exception" );
				$result = $this->query( "ALTER TABLE active DROP COLUMN username;", array(), 0, "rowCount", "exception" );
				$result = $this->query( array(
					"mysql" => "ALTER TABLE active ADD COLUMN user INT",
					"pgsql" => 'ALTER TABLE active ADD COLUMN "user" INT',
				), array(), array(), "rowCount", "exception" );
			} catch( Exception $error ) {
				
				//echo var_dump( $error->getMessage() );
				$status_updates["username_active_coluin"] = array(
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
		
		if( is_array( $query ) ) {
			
			if( in_array( DBTYPE, array_keys( $query ) ) ) {
				
				$query = $query[DBTYPE];
			} else {
				
				if( isset( $query["*"] ) ) {
					
					$query = $query["*"];
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
		}
		
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