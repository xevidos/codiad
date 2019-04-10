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
	
	public $username    = "";
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
	
	//////////////////////////////////////////////////////////////////
	// List User's Active Files
	//////////////////////////////////////////////////////////////////
	
	public function ListActive() {
		
		global $sql;
		$query = "SELECT path,focused FROM active WHERE username=?";
		$bind_variables = array( $this->username );
		$result = $sql->query( $query, $bind_variables, array() );
		$tainted = false;
		$root = WORKSPACE;
		$active_list = $result;
		
		if( ! empty( $return ) ) {
			
			foreach ( $result as $id => $data ) {
				
				if ( $this->isAbsPath( $data['path'] ) ) {
					
					$root = "";
				} else {
					
					$root = $root.'/';
				}
				
				if ( ! file_exists( $root . $data['path'] ) ) {
					
					$tainted = true;
					unset( $active_list[$id] );
				}
			}
		}
		
		if( $tainted ) {
			
			$this->update_active( $active_list );
		}
		
		echo formatJSEND( "success", $active_list );
	}
	
	//////////////////////////////////////////////////////////////////
	// Check File
	//////////////////////////////////////////////////////////////////
	
	public function Check() {
		
		global $sql;
		$query = "SELECT username FROM active WHERE path=?";
		$bind_variables = array( $this->path );
		$result = $sql->query( $query, $bind_variables, array() );
		$tainted = false;
		$user = false;
		$users = array();
		$root = WORKSPACE;
		
		foreach( $result as $id => $data ) {
			
			array_push( $users, $data["username"] );
			if( $data["username"] == $this->username ) {
				
				$user = true;
			}
		}
		
		if ( ( count( $result ) == 1 && ! $user ) || count( $result ) > 1 ) {
			
			echo formatJSEND( "warning", "Warning: File " . substr( $this->path, strrpos( $this->path, "/" ) +1 ) . " Currently Opened By: " . implode( ", ", $users ) );
		} else {
			
			echo formatJSEND("success");
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Add File
	//////////////////////////////////////////////////////////////////
	
	public function Add() {
		
		global $sql;
		$query = "INSERT INTO active( username, path, focused ) VALUES ( ?, ?, ? );";
		$bind_variables = array( $this->username, $this->path, false );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success" );
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
	// Remove File
	//////////////////////////////////////////////////////////////////
	
	public function Remove() {
		
		global $sql;
		$query = "DELETE FROM active WHERE path=? AND username=?;";
		$bind_variables = array( $this->path, $this->username );
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
		$query = "DELETE FROM active WHERE username=?;";
		$bind_variables = array( $this->username );
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
		$query = "UPDATE active SET focused=? WHERE username=?;UPDATE active SET focused=? WHERE path=? AND username=?;";
		$bind_variables = array( false, $this->username, true, $this->path, $this->username );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo formatJSEND( "success" );
		}
	}
}
