<?php
/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), and Isaac Brown (telaaedifex.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Permissions {
	
	const LEVELS = array(
		
		"none" => 0,
		"read" => 1,
		"write" => 2,
		"create" => 4,
		"delete" => 8,
		"manager" => 16,
		"owner" => 32,
		"admin" => 64,
	);
	
	const SYSTEM_LEVELS = array(
		
		"user" => 32,
		"admin" => 64,
	);
	
	function __construct() {
		
		
	}
	
	public static function check_access( $level, $user_level ) {
		
		if( ! is_integer( $level ) ) {
			
			if( in_array( $level, array_keys( self::LEVELS ) ) ) {
				
				$level = self::LEVELS[$level];
			} else {
				
				exit( formatJSEND( "error", "Access Level does not exist." ) );
			}
		} else {
			
			if( ! in_array( $level, self::LEVELS ) ) {
				
				exit( formatJSEND( "error", "Access Level does not exist." ) );
			}
		}
		
		return ( $user_level >= $level );
	}
	
	public static function check_path( $level, $path ) {
		
		$user_level = self::get_access( $path );
		return self::check_access( $level, $user_level );
	}
	
	public static function get_access( $path ) {
		
		global $sql;
		$full_path = Common::isAbsPath( $path ) ? $path : WORKSPACE . "/{$path}";
		$access = 0;
		//$query = "SELECT id, path, owner FROM projects WHERE path LIKE ?;";
		//$bind_variables = array( "{$path}%" );
		$query = "SELECT id, path, owner FROM projects;";
		$bind_variables = array();
		$projects = $sql->query( $query, $bind_variables, array() );
		
		if( ! empty( $projects ) ) {
			
			foreach( $projects as $row => $data ) {
				
				$full_project_path = Common::isAbsPath( $data["path"] ) ? $data["path"] : WORKSPACE . "/{$data["path"]}";
				$path_postition = strpos( $full_path, $full_project_path );
				
				if( $path_postition === false ) {
					
					continue;
				}
				
				if( $data["owner"] == -1 ) {
					
					$access = self::LEVELS["owner"];
				} elseif( $data["owner"] == $_SESSION["user_id"] ) {
					
					$access = self::LEVELS["owner"];
				} else {
					
					$user = $sql->query( array(
						"*" => "SELECT * FROM access WHERE project = ? AND user = ? LIMIT 1",
						"pgsql" => 'SELECT * FROM access WHERE project = ? AND "user" = ? LIMIT 1',
					), array( $data["id"], $_SESSION["user_id"] ), array(), "fetch" );
					
					if( ! empty( $user ) ) {
						
						$access = $user["level"];
					}
				}
				
				//echo var_dump( $full_path, $full_project_path, $path_postition, $user["level"], $data["owner"], $_SESSION["user"] );
				if( $access > 0 ) {
					
					break;
				}
			}
		}
		return $access;
	}
	
	public static function get_level( $i ) {
		
		$level = 0;
		if( is_integer( $i ) ) {
			
			$level = array_search( $i, self::LEVELS );
		} else {
			
			if( in_array( $i, array_keys( self::LEVELS ) ) ) {
				
				$level = self::LEVELS[$i];
			} else {
				
				exit( formatJSEND( "error", "Access Level does not exist." ) );
			}
		}
		
		return $level;
	}
	
	public static function has_owner( $path ) {
		
		return self::check_path( "owner", $path );
	}
	
	public static function has_manager( $path ) {
		
		return self::check_path( "manager", $path );
	}
	
	public static function has_delete( $path ) {
		
		return self::check_path( "delete", $path );
	}
	
	public static function has_create( $path ) {
		
		return self::check_path( "create", $path );
	}
	
	public static function has_write( $path ) {
		
		return self::check_path( "write", $path );
	}
	
	public static function has_read( $path ) {
		
		return self::check_path( "read", $path );
	}
}

?>