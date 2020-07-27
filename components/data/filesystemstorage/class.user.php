<?php

namespace FileSystemStorage;

class User {
	
	protected static $instance = null;
	
	function __construct() {}
	
	public static function create_user( $user ) {
		
		global $data;
		$result = $data->fss->update_data( "users", self::create_user_call );
		echo var_dump( $result );
		return;
	}
	
	public static function create_user_callback( $headers, $d ) {
		
		$new = array();
		foreach( $headers as $h ) {
			
			if( isset( $user[$h] ) ) {
				
				$new[$h] = $user[$h];
			}
		}
		$d[] = $data;
		return $d;
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
	
	public static function get_users() {
		
		global $data;
		return $data->fss->get_data( "users" );
	}
}

?>