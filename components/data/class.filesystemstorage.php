<?php

require_once( "filesystemstorage/class.data.php" );


require_once( "filesystemstorage/class.user.php" );

class FileSystemStorage {
	
	protected static $instance = null;
	
	function __construct() {}
	
	function create_table( $table, $data ) {
		
		$return = Common::get_default_return();
		$pass = true;
		$path = DATA . "/$table.inc";
		$return["table"] = $table;
		
		if( file_exists( $path ) ) {
			
			$return["status"] = "error";
			$return["message"] = "Table already exists.";
			$pass = false;
		}
		
		if( $pass ) {
			
			$write = file_put_contents( $path, serialize( $data ) );
			
			if( $write === false ) {
				
				$return["status"] = "error";
				$return["message"] = "Unable to write to table file.";
			} else {
				
				$return["status"] = "success";
				$return["message"] = "Created table.";
				$return["value"] = $write;
			}
		}
		return $return;
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
	
	public function get_data( $table, $fields = array(), $filter = null ) {
		
		$path = DATA . "/$table.inc";
		$return = Common::get_default_return();
		
		if( is_file( $path ) ) {
			
			$data = file_get_contents( $path );
			$c = unserialize( $data );
			$i = $c::get_instance();
			
			if( is_callable( $filter ) ) {
				
				try {
					
					$return["data"] = $filter( $i->get_data( $fields ) );
				} catch( Throwable $e ) {
					
					$return["status"] = "error";
					$return["message"] = "Unable to call filter function.";
					$return["error"] = array(
						"message" => $e->getMessage(),
						"object" => $e,
					);
				}
			} else {
				
				$return["status"] = "success";
				$return["message"] = "";
				$return["value"] = $i->get_data( $fields );
			}
		} else {
			
			$return["status"] = "error";
			$return["message"] = "The requested table does not exist.";
		}
		return $return;
	}
	
	function install() {
		
		$return = Common::get_default_return();
		$pass = true;
		
		$access = new FileSystemStorage\Data();
		$access->set_meta(
			array(
				"user" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"project" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"level" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
			)
		);
		$return["tables"][] = $this->create_table( "access", $access );
		
		$active = new FileSystemStorage\Data();
		$active->set_meta(
			array(
				"user" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"path" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "string",
					"typeof" => "is_string",
				),
				"position" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
					"typeof" => "is_string",
				),
				"focused" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
					"typeof" => "is_string",
				),
			)
		);
		$return["tables"][] = $this->create_table( "active", $active );
		
		$options = new FileSystemStorage\Data();
		$options->set_meta(
			array(
				"id" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"name" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
					"typeof" => "is_string",
				),
				"value" => array(
					"default" => null,
					"length" => null,
					"null" => true,
					"type" => "string",
					"typeof" => "is_string",
				),
			),
			array( "name" )
		);
		$return["tables"][] = $this->create_table( "options", $options );
		
		$projects = new FileSystemStorage\Data();
		$projects->set_meta(
			array(
				"id" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"name" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
					"typeof" => "is_string",
				),
				"path" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "string",
					"typeof" => "is_string",
				),
				"owner" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
			)
		);
		$return["tables"][] = $this->create_table( "project", $projects );
		
		$users = new FileSystemStorage\Data();
		$users->set_meta(
			array(
				"id" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"first_name" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
					"typeof" => "is_string",
				),
				"last_name" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
					"typeof" => "is_string",
				),
				"username" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
					"typeof" => "is_string",
				),
				"password" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "string",
					"typeof" => "is_string",
				),
				"email" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
					"typeof" => "is_string",
				),
				"project" => array(
					"default" => null,
					"length" => null,
					"null" => true,
					"type" => "int",
					"typeof" => "is_int",
				),
				"access" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"token" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
					"typeof" => "is_string",
				),
			),
			array( "username" )
		);
		$return["tables"][] = $this->create_table( "users", $users );
		
		$user_options = new FileSystemStorage\Data();
		$user_options->set_meta(
			array(
				"id" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"name" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
					"typeof" => "is_string",
				),
				"user" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
					"typeof" => "is_int",
				),
				"value" => array(
					"default" => null,
					"length" => null,
					"null" => true,
					"type" => "string",
					"typeof" => "is_string",
				),
			),
			array( "name", "user" )
		);
		$return["tables"][] = $this->create_table( "user_options", $user_options );
		
		$return["status"] = "success";
		$return["message"] = "Successfully created tables.";
		
		foreach( $return["tables"] as $table ) {
			
			if( $table["status"] == "error" ) {
				
				$pass = false;
				$return["status"] = "error";
				$return["message"] = "Unable to create {$table["table"]} table.";
				break;
			}
		}
		return $return;
	}
	
	/**
	 * Update data stored in a file on the server.  In order to stop the file
	 * from being written by multiple people at the same time, we will provide
	 * the user with the data from the file while we have a lock on it.  Then
	 * call the user's function as a callback, and write the data returned by
	 * the aformentioned function and close the file releasing the lock.
	 *
	 * @since	${current_version}
	 * @return	object	A success or failure message.
	 */
	public function update_data( $table, $update ) {
		
		$pass = true;
		$return = Common::get_default_return();
		$path = DATA . "/$table.inc";
		
		if( is_file( $path ) ) {
			
			if( is_callable( array( $update[0], $update[1] ) ) ) {
				
				$handle = fopen( $path, "r+" );
				
				if( flock( $handle, LOCK_EX ) ) {
					
					$data = fread( $handle, filesize( $path ) );
					$c = @unserialize( $data );
					
					echo "<pre>" . print_r( $c, true ) . "</pre><br><br>";
					
					if( ! is_a( $c, "FileSystemStorage\\Data" ) ) {
						
						$pass = false;
						$return["status"] = "error";
						$return["message"] = "Unable to unserialize table.";
						$return["value"] = $c;
					}
					
					if( $pass ) {
						
						try {
							
							if( is_callable( $update ) ) {
								
								$result = call_user_func( $update, $c->get_headers(), $c->get_data() );
							} elseif( is_array( $update ) && isset( $update[2] ) ) {
								
								$result = call_user_func( array( $update[0], $update[1] ), $c->get_headers(), $c->get_data(), ...$update[2] );
							}
							$result = $c->set_data( $result );
							
							if( $result["status"] === "error" ) {
								
								$pass = false;
								$return = $result;
							}
						} catch( Throwable $e ) {
							
							$return["status"] = "error";
							$return["message"] = "Unable to call update function.";
							$return["error"] = array(
								"message" => $e->getMessage(),
								"object" => $e,
							);
						}
						
						if( $pass ) {
							
							ftruncate( $handle, 0 );
							rewind( $handle );
							fwrite( $handle, serialize( $c ) );
							$return["status"] = "success";
							$return["message"] = "Updated $table.";
							$return["value"] = $result;
						}
					}
					
					flock( $handle, LOCK_UN );
					fclose( $handle );
				} else {
					
					$return["status"] = "error";
					$return["message"] = "Unable to obtain a lock on requested file.";
				}
			} else {
				
				$return["status"] = "error";
				$return["message"] = "Update function is not a callable object.";
			}
		} else {
			
			$return["status"] = "error";
			$return["message"] = "The requested table does not exist.";
		}
		return $return;
	}
}

?>