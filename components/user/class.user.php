<?php

class User {
	
	protected static $instance = null;
	
	function __construct() {}
	
	function create_user( $user ) {
		
		global $data;
		$pass = true;
		$return = Common::get_default_return();
		$requirements = array(
			"username",
			"password",
			"password1",
			"access",
		);
		
		foreach( $requirements as $r ) {
			
			if( ! isset( $user[$r] ) ) {
				
				$return["status"] = "error";
				$return["message"] = "Error, $r is required but was not provided.";
				$pass = false;
				break;
			}
		}
		
		if( $pass && $user["password"] !== $user["password1"] ) {
			
			$return["status"] = "error";
			$return["message"] = "Error, the passwords provided do not match.";
			$pass = false;
		}
		
		if( $pass ) {
			
			$query = array(
				"default" => "INSERT INTO users( username, password, access, project ) VALUES ( ?, ?, ?, ? );",
				"filesystem" => array( "FileSystemStorage\\User", "create_user", array( $user ) ),
			);
			$bind_vars = array(
				$user["username"],
				$user["password"],
				$user["access"],
				null,
			);
			$return = $data->query( $query, $bind_vars, false );
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
	
	function get_user( $identifier ) {
		
		if( is_int(  ) ) {
			
		} else {
			
		}
	}
	
	function get_user_by_id( $id ) {}
	
	function get_user_by_username( $username ) {}
	
	function get_users() {
		
		global $data;
		
		$query = array(
			"default" => "SELECT * FROM users",
			"filesystem" => array( "FileSystemStorage\\User", "get_users" ),
		);
		return $data->query( $query, array(), array() );
	}
}

?>