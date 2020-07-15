<?php

require_once( "./filesystemstorage/class.data.php" );

class FileSystemStorage {
	
	protected static $instance = null;
	
	function __construct() {}
	
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
		
		$path = "./filesystemstorage/$table.inc";
		$return = common::get_default_return();
		
		if( is_file( $path ) ) {
			
			$data = file_get_contents( $path );
			$c = unserialize( $data );
			
			if( is_callable( $filter ) ) {
				
				try {
					
					$return["data"] = $filter( $c->get_data( $fields ) );
				} catch( Throwable $e ) {
					
					$return["status"] = "error";
					$return["message"] = "Unable to call filter function.";
					$return["error"] = array(
						"message" => $e->getMessage(),
						"object" => $e,
					);
				}
			} else {
				
				$return["data"] = $c->get_data( $fields );
			}
		} else {
			
			$return["status"] = "error";
			$return["message"] = "The requested table does not exist.";
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
		
		$return = common::get_default_return();
		$path = "./filesystemstorage/$table.inc";
		
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