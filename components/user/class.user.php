<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once( "../settings/class.settings.php" );

class User {
	
	const ACCESS = array(
		"admin",
		"user"
	);
	
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
		
		global $sql;
		$query = "INSERT INTO users( username, password, access, project ) VALUES ( ?, ?, ?, ? );";
		$bind_variables = array( $this->username, $this->password, $this->access, null );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			$this->set_default_options();
			echo formatJSEND( "success", array( "username" => $this->username ) );
		} else {
			
			echo formatJSEND( "error", "The Username is Already Taken" );
		}
	}
	
	public function delete_user() {
		
		global $sql;
		$query = "DELETE FROM user_options WHERE username=?;";
		$bind_variables = array( $this->username );
		$return = $sql->query( $query, $bind_variables, -1, "rowCount" );
		if( $return > -1 ) {
			
			$query = "DELETE FROM projects WHERE owner=? AND access IN ( ?,?,?,?,? );";
			$bind_variables = array(
				$this->username,
				"null",
				null,
				"[]",
				"",
				json_encode( array( $this->username ) )
			);
			$return = $sql->query( $query, $bind_variables, -1, "rowCount" );
			
			if( $return > -1 ) {
				
				$query = "DELETE FROM users WHERE username=?;";
				$bind_variables = array( $this->username );
				$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
				
				if( $return > 0 ) {
					
					echo formatJSEND( "success", null );
				} else {
					
					echo formatJSEND( "error", "Error deleting user information." );
				}
			} else {
				
				echo formatJSEND( "error", "Error deleting user project information." );
			}
		} else {
			
			echo formatJSEND( "error", "Error deleting user option information." );
		}
	}
	
	public function get_user( $username ) {
		
		global $sql;
		$query = "SELECT * FROM users WHERE username=?";
		$bind_variables = array( $username );
		$return = $sql->query( $query, $bind_variables, array() );
		
		if( ! empty( $return ) ) {
			
			echo formatJSEND( "success", $return );
		} else {
			
			echo formatJSEND( "error", "Could not select user." );
		}
	}
	
	public function list_users() {
		
		global $sql;
		$query = "SELECT * FROM users";
		$return = $sql->query( $query, array(), array() );
		
		if( ! empty( $return ) ) {
			
			return $return;
		} else {
			
			echo formatJSEND( "error", "Error can not select users." );
			return array();
		}
	}
	
	public function set_default_options() {
		
		foreach( Settings::DEFAULT_OPTIONS as $id => $option ) {
			
			global $sql;
			$query = "INSERT INTO user_options ( name, username, value ) VALUES ( ?, ?, ? );";
			$bind_variables = array(
				$option["name"],
				$this->username,
				$option["value"],
			);
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $result == 0 ) {
				
				$query = "UPDATE user_options SET value=? WHERE name=? AND username=?;";
				$bind_variables = array(
					$option["value"],
					$option["name"],
					$this->username,
				);
				$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			}
		}
	}

	//////////////////////////////////////////////////////////////////
	// Authenticate
	//////////////////////////////////////////////////////////////////
	
	public function Authenticate() {
		
		if( $this->username == "" || $this->password == "" ) {
			
			exit( formatJSEND( "error", "Username or password can not be blank." ) );
		}
		
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
		
		if( is_array( $server_user ) ) {
			
			$server_user = $server_user["uid"];
		}
		
		if( ! ( $sessions_owner === $server_user ) ) {
			
			try {
				
				chown( SESSIONS_PATH, $server_user );
			} catch( Exception $e ) {
				
				exit( formatJSEND("error", "Error, incorrect owner of sessions folder.  Expecting: $server_user, Recieved: " . $sessions_owner ) );
			}
		}
		
		if( ! in_array( $sessions_permissions, $permissions ) ) {
			
			try {
				
				chmod( SESSIONS_PATH, 00755 );
			} catch( Exception $e ) {
				
				exit( formatJSEND("error", "Error, incorrect permissions on sessions folder.  Expecting: 0755, Recieved: " . $sessions_permissions ) );
			}
		}
		
		global $sql;
		$pass = false;
		$this->EncryptPassword();
		$query = "SELECT * FROM users WHERE username=? AND password=?;";
		$bind_variables = array( $this->username, $this->password );
		$return = $sql->query( $query, $bind_variables, array() );
		
		/**
		 * Check and make sure the user is not using the old encryption.
		 */
		
		if( ( strtolower( DBTYPE ) == "mysql" ) && empty( $return ) ) {
			
			$query = "SELECT * FROM users WHERE username=? AND password=PASSWORD( ? );";
			$bind_variables = array( $this->username, $this->password );
			$return = $sql->query( $query, $bind_variables, array() );
			
			if( ! empty( $return ) ) {
				
				$query = "UPDATE users SET password=? WHERE username=?;";
				$bind_variables = array( $this->password, $this->username );
				$return = $sql->query( $query, $bind_variables, array() );
				
				$query = "SELECT * FROM users WHERE username=? AND password=?;";
				$bind_variables = array( $this->username, $this->password );
				$return = $sql->query( $query, $bind_variables, array() );
			}
		}
		
		if( ! empty( $return ) ) {
			
			$pass = true;
			$token = mb_strtoupper( strval( bin2hex( openssl_random_pseudo_bytes( 16 ) ) ) );
			$_SESSION['id'] = SESSION_ID;
			$_SESSION['user'] = $this->username;
			$_SESSION['token'] = $token;
			$_SESSION['lang'] = $this->lang;
			$_SESSION['theme'] = $this->theme;
			$_SESSION["login_session"] = true;
			$user = $return[0];
			
			$query = "UPDATE users SET token=? WHERE username=?;";
			$bind_variables = array( sha1( $token ), $this->username );
			$return = $sql->query( $query, $bind_variables, 0, 'rowCount' );
			
			if( isset( $user['project'] ) && $user['project'] != '' ) {
				
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
	* on any other machine and if they are then log them off using
	* session_destroy, otherwise close the session without saving data
	* using session abort().
	* 
	* This should help fix the issue with auto save
	* attempting to save both users at the same time.
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
		
		return strtolower( preg_replace( '#[^A-Za-z0-9' . preg_quote( '-_@. ').']#', '', $username ) );
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
		
		$this->delete_user();
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
		
		global $sql;
		$this->EncryptPassword();
		$query = "UPDATE users SET password=? WHERE username=?;";
		$bind_variables = array( $this->password, $this->username );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success", "Password changed" );
		} else {
			
			echo formatJSEND( "error", "Error changing password" );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Set Current Project
	//////////////////////////////////////////////////////////////////
	
	public function Project() {
		
		global $sql;
		$query = "UPDATE users SET project=? WHERE username=?;";
		$bind_variables = array( $this->project, $this->username );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success", null );
		} else {
			
			echo formatJSEND( "error", "Error updating project" );
		}
	}
	
	public function update_access() {
		
		global $sql;
		$query = "UPDATE users SET access=? WHERE username=?;";
		$bind_variables = array( $this->access, $this->username );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success", "Updated access for {$this->username}" );
		} else {
			
			echo formatJSEND( "error", "Error updating project" );
		}
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
