<?php

/**
*  Copyright (c) Codiad, Kent Safranski (codiad.com), and Isaac Brown (telaaedifex.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once('../../common.php');

class Active extends Common {
	
	//////////////////////////////////////////////////////////////////
	// PROPERTIES
	//////////////////////////////////////////////////////////////////
	
	public $path        = "";
	public $new_path    = "";
	
	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Construct
	//////////////////////////////////////////////////////////////////
	
	public function __construct() {
	}
	
	public static function remove( $path ) {
		
		global $sql;
		$query = array(
			"*" => "DELETE FROM active WHERE path=? AND user=?;",
			"pgsql" => 'DELETE FROM active WHERE path=? AND "user"=?;',
		);
		$bind_variables = array( $path, $_SESSION["user_id"] );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
	}
	
	//////////////////////////////////////////////////////////////////
	// List User's Active Files
	//////////////////////////////////////////////////////////////////
	
	public function ListActive() {
		
		global $sql;
		$query = array(
			"*" => "SELECT path, position, focused FROM active WHERE user=?",
			"pgsql" => 'SELECT path, position, focused FROM active WHERE "user"=?',
		);
		$bind_variables = array( $_SESSION["user_id"] );
		$result = $sql->query( $query, $bind_variables, array() );
		$tainted = false;
		$root = WORKSPACE;
		$active_list = $result;
		
		if( ! empty( $result ) ) {
			
			foreach ( $result as $id => $data ) {
				
				if ( $this->isAbsPath( $data['path'] ) ) {
					
					$root = "";
				} else {
					
					$root = $root.'/';
				}
				
				if ( ! is_file( $root . $data['path'] ) ) {
					
					self::remove( $data['path'] );
					unset( $active_list[$id] );
				}
			}
		}
		exit( formatJSEND( "success", $active_list ) );
	}
	
	//////////////////////////////////////////////////////////////////
	// Check File
	//////////////////////////////////////////////////////////////////
	
	public function Check() {
		
		global $sql;
		$query = array(
			"*" => "SELECT user FROM active WHERE path=?",
			"pgsql" => 'SELECT "user" FROM active WHERE path=?',
		);
		$bind_variables = array( $this->path );
		$result = $sql->query( $query, $bind_variables, array() );
		$tainted = false;
		$user = false;
		$users = array();
		$root = WORKSPACE;
		
		foreach( $result as $id => $data ) {
			
			array_push( $users, $data["user"] );
			if( $data["user"] == $_SESSION ) {
				
				$user = true;
				break;
			}
		}
		
		if ( ( count( $result ) == 1 && ! $user ) || count( $result ) > 1 ) {
			
			//echo formatJSEND( "warning", "Warning: File " . substr( $this->path, strrpos( $this->path, "/" ) +1 ) . " Currently Opened By: " . implode( ", ", $users ) );
		} else {
			
			echo formatJSEND("success");
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Add File
	//////////////////////////////////////////////////////////////////
	
	public function Add() {
		
		global $sql;
		$query = array(
			"*" => "SELECT focused FROM active WHERE path=? AND user=? LIMIT 1;",
			"pgsql" => 'SELECT focused FROM active WHERE path=? AND "user"=? LIMIT 1;',
		);
		$bind_variables = array( $this->path, $_SESSION["user_id"] );
		$result = $sql->query( $query, $bind_variables, array() );
		
		if( count( $result ) == 0 ) {
			
			$query = array(
				"*" => "UPDATE active SET focused=false WHERE user=? AND path=?;",
				"pgsql" => 'UPDATE active SET focused=false WHERE "user"=? AND path=?;',
			);
			$bind_variables = array( $_SESSION["user_id"], $this->path );
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $result == 0 ) {
				
				global $sql;
				$query = array(
					"*" => "INSERT INTO active( user, path, focused ) VALUES ( ?, ?, ? );",
					"pgsql" => 'INSERT INTO active( "user", path, focused ) VALUES ( ?, ?, ? );',
				);
				$bind_variables = array( $_SESSION["user_id"], $this->path, false );
				$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
				
				if( $result > 0 ) {
					
					echo formatJSEND( "success" );
				}
			}
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Rename File
	//////////////////////////////////////////////////////////////////
	
	public function Rename() {
		
		global $sql;
		$query = "UPDATE active SET path=? WHERE path=?;";
		$bind_variables = array( $this->new_path, $this->path );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success" );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Remove All Files
	//////////////////////////////////////////////////////////////////
	
	public function RemoveAll() {
		
		global $sql;
		$query = array(
			"*" => "DELETE FROM active WHERE user=?;",
			"pgsql" => 'DELETE FROM active WHERE "user"=?;',
		);
		$bind_variables = array( $_SESSION["user_id"] );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success" );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Mark File As Focused
	//  All other files will be marked as non-focused.
	//////////////////////////////////////////////////////////////////
	
	public function MarkFileAsFocused() {
		
		global $sql;
		$query = array(
			"*" => "UPDATE active SET focused=? WHERE path=? AND user=?;",
			"pgsql" => 'UPDATE active SET focused=? WHERE path=? AND "user"=?;',
		);
		$bind_variables = array( true, $this->path, $_SESSION["user_id"] );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success" );
		}
	}
	
	public function savePositions( $positions ) {
		
		global $sql;
		$positions = json_decode( $positions, true );
		$query = array(
			"mysql" => "UPDATE active SET position=? WHERE path=? AND user=?;",
			"pgsql" => 'UPDATE active SET position=? WHERE path=? AND "user"=?;',
		);
		$bind_variables = array();
		
		if( json_last_error() == JSON_ERROR_NONE ) {
			
			foreach( $positions as $path => $cursor ) {
				
				$return = $sql->query( $query, array( json_encode( $cursor ), $path, $_SESSION["user_id"] ), 0, "rowCount" );
			}
			
			if( $return > 0 ) {
				
				exit( formatJSEND( "success" ) );
			}
		} else {
			
			exit( formatJSEND( "success" ) );
		}
	}
}
