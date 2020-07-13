<?php

class Data {
	
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
	
	public function query( $query, $bind_vars, $default, $action='fetchAll', $errors="default" ) {
		
		$return = common::get_default_return();
		
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
	}
}

?>