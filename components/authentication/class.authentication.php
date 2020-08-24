<?php

class Authentication {
	
	protected static $instance = null;
	
	function __construct() {}
	
	function authenticate( $username, $password ) {
		
		
		return $result;
	}
	
	static function check_session() {}
	
	static function check_token() {
		
		
	}
	
	function generate_session( $username ) {
		
		$Events = Events::get_instance();
		$Events->publish( "Events.generate_session" );
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
	
	public function refresh_session() {}
	
	public function start_session() {
		
		session_start();
	}
}

?>