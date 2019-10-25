<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once( "../settings/class.settings.php" );

class User {
	
	//////////////////////////////////////////////////////////////////
	// PROPERTIES
	//////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Construct
	//////////////////////////////////////////////////////////////////
	
	public function __construct() {
		
	}
	
	public function add_user( $username, $password, $access ) {
		
		global $sql;
		$query = "INSERT INTO users( username, password, access, project ) VALUES ( ?, ?, ?, ? );";
		$bind_variables = array( $username, $password, $access, null );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		$pass = false;
		
		if( $return > 0 ) {
			
			$this->set_default_options( $username );
			$pass = true;
		}
		return false;
	}
	
	public function delete_user( $username ) {
		
		global $sql;
		$query = "DELETE FROM user_options WHERE user=( SELECT id FROM users WHERE username=? );";
		$bind_variables = array( $username );
		$return = $sql->query( $query, $bind_variables, -1, "rowCount" );
		if( $return > -1 ) {
			
			$query = "
			DELETE FROM projects
			WHERE owner=( SELECT id FROM users WHERE username=? )
			AND ( SELECT COUNT(*) FROM access WHERE project = projects.id AND WHERE user <> ( SELECT id FROM users WHERE username=? ) );";
			$bind_variables = array(
				$username,
				$username
			);
			$return = $sql->query( $query, $bind_variables, -1, "rowCount" );
			
			if( $return > -1 ) {
				
				$query = "DELETE FROM users WHERE username=?;";
				$bind_variables = array( $username );
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
	
	public function set_default_options( $username ) {
		
		foreach( Settings::DEFAULT_OPTIONS as $id => $option ) {
			
			global $sql;
			$query = "INSERT INTO user_options ( name, user, value ) VALUES ( ?, ( SELECT id FROM users WHERE username=? ), ? );";
			$bind_variables = array(
				$option["name"],
				$username,
				$option["value"],
			);
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $result == 0 ) {
				
				$query = "UPDATE user_options SET value=? WHERE name=? AND user=( SELECT id FROM users WHERE username=? );";
				$bind_variables = array(
					$option["value"],
					$option["name"],
					$username,
				);
				$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			}
		}
	}

	//////////////////////////////////////////////////////////////////
	// Authenticate
	//////////////////////////////////////////////////////////////////
	
	public function Authenticate( $username, $password ) {
		
		if( $username == "" || $password == "" ) {
			
			return false;
		}
		
		global $sql;
		$pass = false;
		$password = $this->encrypt_password( $password );
		$query = "SELECT * FROM users WHERE username=? AND password=?;";
		$bind_variables = array( $username, $password );
		$return = $sql->query( $query, $bind_variables, array() );
		
		/**
		 * Check and make sure the user is not using the old encryption.
		 */
		
		if( ( strtolower( DBTYPE ) == "mysql" ) && empty( $return ) ) {
			
			$query = "SELECT * FROM users WHERE username=? AND password=PASSWORD( ? );";
			$bind_variables = array( $username, $password );
			$return = $sql->query( $query, $bind_variables, array() );
			
			if( ! empty( $return ) ) {
				
				$query = "UPDATE users SET password=? WHERE username=?;";
				$bind_variables = array( $password, $username );
				$return = $sql->query( $query, $bind_variables, array() );
				
				$query = "SELECT * FROM users WHERE username=? AND password=?;";
				$bind_variables = array( $username, $password );
				$return = $sql->query( $query, $bind_variables, array() );
			}
		}
		
		if( ! empty( $return ) ) {
			
			$user = $return[0];
			$pass = true;
			$token = mb_strtoupper( strval( bin2hex( openssl_random_pseudo_bytes( 16 ) ) ) );
			$_SESSION['id'] = SESSION_ID;
			$_SESSION['user'] = $username;
			$_SESSION['user_id'] = $user["id"];
			$_SESSION['token'] = $token;
			$_SESSION["login_session"] = true;
			
			$query = "UPDATE users SET token=? WHERE username=?;";
			$bind_variables = array( sha1( $token ), $username );
			$return = $sql->query( $query, $bind_variables, 0, 'rowCount' );
			$projects = $sql->query( "SELECT path FROM projects WHERE id = ?", array( $user["project"] ), array() );
			
			if( isset( $user['project'] ) && $user['project'] != '' && ! empty( $projects ) ) {
				
				$_SESSION['project'] = $projects[0]["path"];
				$_SESSION['project_id'] = $user['project'];
			}
			
			$this->checkDuplicateSessions( $username );
		}
		return $pass;
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
		
		return strtolower( preg_replace( '/[^\w\-\._@]/', '-', $username ) );
	}
	
	//////////////////////////////////////////////////////////////////
	// Create Account
	//////////////////////////////////////////////////////////////////
	
	public function Create( $username, $password ) {
		
		$username = self::CleanUsername( $username );
		$password = $this->encrypt_password( $password );
		$result = $this->add_user( $username, $password, Permissions::SYSTEM_LEVELS["user"] );
		return $result;
	}
	
	//////////////////////////////////////////////////////////////////
	// Delete Account
	//////////////////////////////////////////////////////////////////
	
	public function Delete( $username ) {
		
		$username = self::CleanUsername( $username );
		return $this->delete_user( $username );
	}
	
	//////////////////////////////////////////////////////////////////
	// Encrypt Password
	//////////////////////////////////////////////////////////////////
	
	private function encrypt_password( $password ) {
		
		return sha1( md5( $password ) );
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
	
	public function update_access( $username, $access ) {
		
		global $sql;
		$query = "UPDATE users SET access=? WHERE username=?;";
		$bind_variables = array( $access, $username );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success", "Updated access for {$this->username}" );
		} else {
			
			echo formatJSEND( "error", "Error updating access" );
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
