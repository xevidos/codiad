<?php

class Authentication {
	
	protected static $instance = null;
	
	function __construct() {}
	
	function authenticate( $username, $password ) {
		
		$type = AUTH_TYPE;
		$result = false;
		$path = "types/$type";
		
		if( is_file( $path ) ) {
			
			require_once( $path );
			
			$t_class = ucfirst( $type );
			
			if( class_exists( $type ) && method_exists( $type, "get_instance" ) ) {
				
				$t_i = $type::get_instance();
				$result = $t_i->authenticate( $username, $password );
				
				if( $result === true ) {
					
					$this->generate_session( $username );
				}
			}
		}
		return $result;
	}
	
	static function check_token() {
		
		$_ = self::get_instance();
		$url = Common::get_current_url();
		$type = AUTH_TYPE;
		$path = "types/$type";
		
		if( isset( $_SESSION["username"] ) && isset( $_SESSION["token"] ) ) {
			
			if( is_file( $path ) ) {
				
				require_once( $path );
				
				$t_class = ucfirst( $type );
				
				if( class_exists( $type ) && method_exists( $type, "get_instance" ) ) {
					
					$t_i = $type::get_instance();
					$result = $t_i->check_token();
					
					if( $result === true ) {
						
						$this->refresh_session();
					}
				}
			}
		} else {
			
			header( "Location: " . Common::get_current_url() . "login.php?redirect=" . urlencode( base64_encode( $url ) ) );
			exit();
		}
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