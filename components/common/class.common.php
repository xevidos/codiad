<?php

require_once( COMPONENTS . "/events/class.events.php" );
require_once( COMPONENTS . "/data/class.data.php" );

class Common {
	
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
	
	public static function get_client_ip() {
		
		$ipaddress = '';
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && $_SERVER['HTTP_CLIENT_IP'] ) {
			
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} else if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && $_SERVER['HTTP_X_FORWARDED_FOR'] ) {
			
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if( isset( $_SERVER['HTTP_X_FORWARDED'] ) && $_SERVER['HTTP_X_FORWARDED'] ) {
			
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} else if( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) && $_SERVER['HTTP_FORWARDED_FOR'] ) {
			
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if( isset( $_SERVER['HTTP_FORWARDED'] ) && $_SERVER['HTTP_FORWARDED'] ) {
			
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} else if( isset( $_SERVER['REMOTE_ADDR'] ) && $_SERVER['REMOTE_ADDR'] ) {
			
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		} else {
			
			$ipaddress = 'UNKNOWN';
		}
		return $ipaddress;
	}
	
	public static function get_current_url() {
		
		return ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	}
	
	public static function i18n( $key, $args = array() ) {
		
		global $lang;
		$key = ucwords( strtolower( $key ) ); //Test, test TeSt and tESt are exacly the same
		$return = isset( $lang[$key] ) ? $lang[$key] : $key;
		foreach( $args as $k => $v ) {
			
			$return = str_replace( "%{" . $k . "}%", $v, $return );
		}
		return $return;
	}
	
	static function status( $status, $action, $message ) {
		
		$response = array(
			"status" => $status,
			"message" => null,
		);
		
		switch( $action ) {
			
			case ( "return" ):
				
				if( is_array( $message ) ) {
					
					$response = array_merge( $response, $message );
				} else {	
					
					$response["message"] = $message;
				}
				return $response;
			break;
			
			case ( "exit" ):
				
				if( is_array( $message ) ) {
					
					$response = array_merge( $response, $message );
				} else {
					
					$response["message"] = $message;
				}
				exit( json_encode( $response, JSON_PRETTY_PRINT ) );
			break;
			
			case ( "throw" ):
				
				if( is_array( $message ) ) {
					
					$response = array_merge( $response, $message );
				} else {
					
					$response["message"] = $message;
				}
				throw new Error( json_encode( $response, JSON_PRETTY_PRINT ) );
			break;
			
		}
	}
}

?>