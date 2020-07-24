<?php

require_once( "filesystemstorage/class.data.php" );

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
				
				$return["data"] = $i->get_data( $fields );
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
				),
				"project" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
				),
				"level" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
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
				),
				"path" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "string",
				),
				"position" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
				),
				"focused" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
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
				),
				"name" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
				),
				"value" => array(
					"default" => null,
					"length" => null,
					"null" => true,
					"type" => "string",
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
				),
				"name" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
				),
				"path" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "string",
				),
				"owner" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
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
				),
				"first_name" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
				),
				"last_name" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
				),
				"username" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
				),
				"password" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "string",
				),
				"email" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
				),
				"project" => array(
					"default" => null,
					"length" => null,
					"null" => true,
					"type" => "int",
				),
				"access" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
				),
				"token" => array(
					"default" => null,
					"length" => 255,
					"null" => true,
					"type" => "string",
				),
			)
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
				),
				"name" => array(
					"default" => null,
					"length" => 255,
					"null" => false,
					"type" => "string",
				),
				"user" => array(
					"default" => null,
					"length" => null,
					"null" => false,
					"type" => "int",
				),
				"value" => array(
					"default" => null,
					"length" => null,
					"null" => true,
					"type" => "string",
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
		
		$return = Common::get_default_return();
		$path = DATA . "/$table.inc";
		
		if( is_file( $path ) ) {
			
			if( is_callable( $update ) ) {
				
				$handle = fopen( $path, "w+" );
				
				if( flock( $handle, LOCK_EX ) ) {
					
					$data = fread( $handle, filesize( $path ) );
					$c = unserialize( $data );
					
					try {
						
						$c->set_data( $update( $c->get_data() ) );
					} catch( Throwable $e ) {
						
						$return["status"] = "error";
						$return["message"] = "Unable to call update function.";
						$return["error"] = array(
							"message" => $e->getMessage(),
							"object" => $e,
						);
					}
					
					fwrite( $handle, serialize( $c ) );
					flock( $handle, LOCK_UN );
					fclose( $handle );
					
					$return["status"] = "success";
					$return["message"] = "Updated $table.";
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