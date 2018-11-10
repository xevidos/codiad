<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

class User {
	
	//////////////////////////////////////////////////////////////////
	// PROPERTIES
	//////////////////////////////////////////////////////////////////
	
	public $access		= 'user';
	public $username    = '';
	public $password    = '';
	public $project     = '';
	public $projects    = '';
	public $users       = '';
	public $actives     = '';
	public $lang        = '';
	public $theme       = '';
	
	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Construct
	//////////////////////////////////////////////////////////////////
	
	public function __construct() {
		
		$this->actives = getJSON( 'active.php' );
	}
	
	public function add_user() {
		
		$sql = "INSERT INTO `users`( `username`, `password`, `access`, `project` ) VALUES ( ?, PASSWORD( ? ), ?, ? );";
		$bind = "ssss";
		$bind_variables = array( $this->username, $this->password, $this->access, null );
		$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error that username is already taken." ) );
		
		if( sql::check_sql_error( $return ) ) {
			
			echo formatJSEND( "success", array( "username" => $this->username ) );
		} else {
			
			echo formatJSEND( "error", "The Username is Already Taken" );
		}
	}
	
	public function get_user( $username ) {
		
		$sql = "SELECT * FROM `users` WHERE `username`=?";
		$bind = "s";
		$bind_variables = array( $username );
		$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error can not select user." ) );
		
		if( sql::check_sql_error( $return ) ) {
			
			echo formatJSEND( "success", $return );
		} else {
			
			echo $return;
		}
	}
	
	public function list_users() {
		
		$sql = "SELECT * FROM `users`";
		$bind = "";
		$bind_variables = array( $this->username, $this->password, $this->access, null );
		$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error can not select users." ) );
		
		return( $return );
	}

	//////////////////////////////////////////////////////////////////
	// Authenticate
	//////////////////////////////////////////////////////////////////
	
	public function Authenticate() {
		
		if( ! is_dir( SESSIONS_PATH ) ) {
			
			mkdir( SESSIONS_PATH, 00755 );
		}
		
		$permissions = array(
			"755",
			"0755"
		);
		
		$server_user = posix_getpwuid( posix_geteuid() );
		$sessions_permissions = substr( sprintf( '%o', fileperms( SESSIONS_PATH ) ), -4 );
		$sessions_owner = posix_getpwuid( fileowner( SESSIONS_PATH ) );
		
		if( ! ( $sessions_owner === $server_user ) ) {
			
			try {
				
				chown( SESSIONS_PATH, $server_user );
			} catch( Exception $e ) {
				
				echo( formatJSEND("error", "Error, incorrect owner of sessions folder.  Expecting: $server_user, Recieved: " . $sessions_owner ) );
				return;
			}
		}
		
		if( ! in_array( $sessions_permissions, $permissions ) ) {
			
			try {
				
				chmod( SESSIONS_PATH, 00755 );
			} catch( Exception $e ) {
				
				echo( formatJSEND("error", "Error, incorrect permissions on sessions folder.  Expecting: 0755, Recieved: " . $sessions_permissions ) );
				return;
			}
		}
		
		$pass = false;
		$this->EncryptPassword();
		$sql = "SELECT * FROM `users` WHERE `username`=? AND `password`=PASSWORD( ? );";
		$bind = "ss";
		$bind_variables = array( $this->username, $this->password );
		$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error fetching user information." ) );
		
		if( mysqli_num_rows( $return ) > 0 ) {
			
			$pass = true;
			$token = mb_strtoupper( strval( bin2hex( openssl_random_pseudo_bytes( 16 ) ) ) );
			$_SESSION['id'] = SESSION_ID;
			$_SESSION['user'] = $this->username;
			$_SESSION['token'] = $token;
			$_SESSION['lang'] = $this->lang;
			$_SESSION['theme'] = $this->theme;
			$_SESSION["login_session"] = true;
			$user = mysqli_fetch_assoc( $return );
			
			$sql = "UPDATE `users` SET `token`=PASSWORD( ? ) WHERE `username`=?;";
			$bind = "ss";
			$bind_variables = array( $token, $this->username );
			sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error updating user information." ) );
			
			if( $user['project'] != '' ) {
				
				$_SESSION['project'] = $user['project'];
			}
			
			$this->checkDuplicateSessions( $this->username );
		}
		
		if( $pass ) {
			
			echo formatJSEND( "success", array( "username" => $this->username ) );
		} else {
			
			echo formatJSEND( "error", "Incorrect Username or Password" );
		}
	}
	
	/**
	* Check duplicate sessions
	* 
	* This function checks to see if the user is currently logged in
	* on any other machine and if they are then log them off.  This
	* will fix the issue with the new auto save attempting to save both
	* users at the same time.
	*/
	
	public static function checkDuplicateSessions( $username ) {
	
		session_write_close();
		$all_sessions = array();
		$sessions = glob( SESSIONS_PATH . "/*" );
		session_id( SESSION_ID );
		
		foreach( $sessions as $session ) {
			
			if( strpos( $session, "sess_") == false ) {
				
				continue;
			}
			
			$session = str_replace( "sess_", "", $session );
			$session = str_replace( SESSIONS_PATH . "/", "", $session );
			//This skips temp files that aren't sessions
			if( strpos( $session, "." ) == false ) {
				
				session_id( $session );
				session_start();
				$_SESSION["id"] = $session;
				array_push( $all_sessions, $_SESSION );
				
				if( isset( $_SESSION["user"] ) && $_SESSION["user"] === $username && isset( $_SESSION["login_session"] ) && $_SESSION["login_session"] === true && SESSION_ID !== session_id() ) {
					
					session_destroy();
				} else {
					
					session_abort();
				}
			}
		}
		session_id( SESSION_ID );
		session_start();
	}
	
	//////////////////////////////////////////////////////////////////
	// Check Duplicate
	//////////////////////////////////////////////////////////////////
	
	public function CheckDuplicate() {
		
		$pass = true;
		foreach( $this->users as $user => $data ) {
			
			if( $data['username'] == $this->username ) {
				
				$pass = false;
			}
		}
		return $pass;
	}
	
	//////////////////////////////////////////////////////////////////
	// Clean username
	//////////////////////////////////////////////////////////////////
	
	public static function CleanUsername( $username ) {
		
		return preg_replace( '#[^A-Za-z0-9' . preg_quote( '-_@. ').']#', '', $username );
	}
	
	//////////////////////////////////////////////////////////////////
	// Create Account
	//////////////////////////////////////////////////////////////////
	
	public function Create() {
		
		$this->EncryptPassword();
		$this->add_user();
	}
	
	//////////////////////////////////////////////////////////////////
	// Delete Account
	//////////////////////////////////////////////////////////////////
	
	public function Delete() {
		
		$sql = "DELETE FROM `users` WHERE `username`=?;";
		$bind = "ss";
		$bind_variables = array( $this->username, $this->password );
		$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error deleting user information." ) );
		
		if( sql::check_sql_error( $return ) ) {
			
			echo formatJSEND( "success", null );
		} else {
			
			echo $return;
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Encrypt Password
	//////////////////////////////////////////////////////////////////
	
	private function EncryptPassword() {
		
		$this->password = sha1( md5( $this->password ) );
	}
	
	//////////////////////////////////////////////////////////////////
	// Change Password
	//////////////////////////////////////////////////////////////////
	
	public function Password() {
		
		$this->EncryptPassword();
		$sql = "UPDATE `users` SET `password`=PASSWORD( ? ) WHERE `username`=?;";
		$bind = "ss";
		$bind_variables = array( $this->password, $this->username );
		$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error updating user information." ) );
		
		if( sql::check_sql_error( $return ) ) {
			
		} else {
			
			echo formatJSEND( "success", null );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Set Current Project
	//////////////////////////////////////////////////////////////////
	
	public function Project() {
		
		$sql = "UPDATE `users` SET `project`=? WHERE `username`=?;";
		$bind = "ss";
		$bind_variables = array( $this->project, $this->username );
		$return = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error updating user information." ) );
		
		if( sql::check_sql_error( $return ) ) {
			
			echo formatJSEND( "success", null );
		} else {
			
			echo( $return );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Search Users
	//////////////////////////////////////////////////////////////////
	
	public function search_users( $username, $return = "return" ) {
		
		$sql = "SELECT `username` FROM `users` WHERE `username` LIKE ?;";
		$bind = "s";
		$bind_variables = array( "%{$username}%" );
		$result = sql::sql( $sql, $bind, $bind_variables, formatJSEND( "error", "Error selecting user information." ) );
		$user_list = array();
		
		foreach( $result as $row ) {
			
			array_push( $user_list, $row["username"] );
		}
		
		if( mysqli_num_rows( $result ) > 0 ) {
			
			switch( $return ) {
				
				case( "exit" ):
					
					exit( formatJSEND( "success", $user_list ) );
				break;
				
				case( "json" ):
					
					$return = json_encode( $user_list );
				break;
				
				case( "return" ):
					
					$return = $user_list;
				break;
			}
		} else {
			
			switch( $return ) {
				
				case( "exit" ):
					
					exit( formatJSEND( "error", "Error selecting user information." ) );
				break;
				
				case( "json" ):
					
					$return = formatJSEND( "error", "Error selecting user information." );
				break;
				
				case( "return" ):
					
					$return = null;
				break;
			}
		}
		
		return( $return );
	}
	
	//////////////////////////////////////////////////////////////////
	// Verify Account Exists
	//////////////////////////////////////////////////////////////////
	
	public function Verify() {
			
		$pass = 'false';
		foreach( $this->users as $user => $data ) {
			
			if( $this->username == $data['username'] ) {
				
				$pass = 'true';
			}
		}
		echo( $pass );
	}
}
