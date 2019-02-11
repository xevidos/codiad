<?php

/*
*  Copyright (c) Codiad & Andr3as, distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

class Settings {
	
	const DEFAULT_OPTIONS = array(
		array(
			"name" => "codiad.editor.autocomplete",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.fileManagerTrigger",
			"value" => "false",
		),
		array(
			"name" => "codiad.editor.fontSize",
			"value" => "14px",
		),
		array(
			"name" => "codiad.editor.highlightLine",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.indentGuides",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.overScroll",
			"value" => "0.5",
		),
		array(
			"name" => "codiad.editor.persistentModal",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.printMargin",
			"value" => "true",
		),
		array(
			"name" => "codiad.editor.printMarginColumn",
			"value" => "80",
		),
		array(
			"name" => "codiad.editor.rightSidebarTrigger",
			"value" => "false",
		),
		array(
			"name" => "codiad.editor.softTabs",
			"value" => "false",
		),
		array(
			"name" => "codiad.editor.tabSize",
			"value" => "4",
		),
		array(
			"name" => "codiad.editor.theme",
			"value" => "twilight",
		),
		array(
			"name" => "codiad.editor.wrapMode",
			"value" => "true",
		),
		array(
			"name" => "codiad.filemanager.autoReloadPreview",
			"value" => "true",
		),
		array(
			"name" => "codiad.projects.sideExpanded",
			"value" => "true",
		),
		array(
			"name" => "codiad.settings.autosave",
			"value" => "true",
		),
		array(
			"name" => "codiad.settings.plugin.sync",
			"value" => "true",
		),
	);
	
	//////////////////////////////////////////////////////////////////
	// PROPERTIES
	//////////////////////////////////////////////////////////////////
	
	public $connection    = '';
	public $username    = '';
	public $settings    = '';
	
	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Construct
	//////////////////////////////////////////////////////////////////
	
	public function __construct() {
	}
	
	public function delete_option( $option, $username ) {
		
		global $sql;
		if( $username == null ) {
			
			$query = "DELETE FROM options WHERE name=?";
			$bind_variables = array(
				$option,
			);
			
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
		} else {
			
			$query = "DELETE FROM options WHERE name=? AND username=?";
			$bind_variables = array(
				$option,
				$this->username,
			);
			
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
		}
		
		if( $result > 0 ) {
			
			echo formatJSEND( "success", null );
		} else {
			
			echo formatJSEND( "error", "Could not delete option: $option" );
		}
	}
	
	public function get_option( $option, $user_setting, $action = "return" ) {
		
		global $sql;
		if( $user_setting == null ) {
			
			$query = "SELECT value FROM options WHERE name=?;";
			$bind_variables = array( $option );
			$return = $sql->query( $query, $bind_variables, array() )[0];
		} else {
			
			$query = "SELECT value FROM user_options WHERE name=? AND username=?;";
			$bind_variables = array( $option, $this->username );
			$return = $sql->query( $query, $bind_variables, array() )[0];
		}
		
		if( ! empty( $return ) ) {
				
			$return = $return["value"];
		} else {
			
			$return = null;
		}
		
		switch( $action ) {
			
			case( "exit" ):
				
				exit( $return );
			break;
			
			case( "return" ):
				
				return( $return );
			break;
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Save User Settings
	//////////////////////////////////////////////////////////////////
	
	public function Save() {
		
		global $sql;
		foreach( $this->settings as $option => $value ) {
			
			$this->update_option( $option, $value, $this->username );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Load User Settings
	//////////////////////////////////////////////////////////////////
	
	public function Load() {
		
		global $sql;
		$query = "SELECT DISTINCT * FROM user_options WHERE username=?;";
		$bind_variables = array(
			$this->username
		);
		
		$options = $sql->query( $query, $bind_variables, array() );
		
		if( ! empty( $options ) ) {
			
			echo formatJSEND( "success", $options );
		} else {
			
			echo formatJSEND( "error", "Error, Could not load user's settings." );
		}
	}
	
	public function update_option( $option, $value, $user_setting = true ) {
		
		global $sql;
		
		if( $user_setting == null ) {
			
			$query = "INSERT INTO options ( name, username, value ) VALUES ( ?, ? );";
			$bind_variables = array(
				$option,
				$value,
			);
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $result == 0 ) {
				
				$query = "UPDATE options SET value=? WHERE name=?;";
				$bind_variables = array(
					$value,
					$option,
				);
				$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			}
		} else {
			
			$query = "INSERT INTO user_options ( name, username, value ) VALUES ( ?, ?, ? );";
			$bind_variables = array(
				$option,
				$this->username,
				$value,
			);
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $result == 0 ) {
				
				$query = "UPDATE user_options SET value=? WHERE name=? AND username=?;";
				$bind_variables = array(
					$value,
					$option,
					$this->username,
				);
				$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			}
		}
		
		if( $result > 0 ) {
			
			echo formatJSEND( "success", null );
		} else {
			
			echo formatJSEND( "error", "Error, Could not update option $option" );
		}
	}
}
