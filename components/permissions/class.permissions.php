<?php
/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), and Isaac Brown (telaaedifex.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

class Permissions {
	
	const LEVELS = array(
		
		"admin" => 0,
		"owner" => 1,
		"manager" => 2,
		"delete" => 3,
		"create" => 4,
		"write" => 5,
		"read" => 6,
	);
	
	function __construct() {
		
		
	}
	
	public static function check_path( $level, $path ) {
		
		$project_path = $_SESSION["project"];
		$project_path = rtrim( $project_path, '/' ) . '/';
		
		if( ! in_array( $level, array_keys( self::LEVELS ) ) ) {
			
			exit( Common::formatJSEND( "error", "Access Level does not exist." ) );
		}
		
		if( strpos( $path, $project_path ) === 0 ) {
			
			exit( Common::formatJSEND( "error", "Error with path." ) );
		}
		
		global $sql;
		$pass = false;
		//$query = "SELECT * FROM projects WHERE LOCATE( path, ? ) > 0 LIMIT 1;";
		//$bind_variables = array( $path );
		//$result = $sql->query( $query, $bind_variables, array() )[0];
		/*$result = $sql->select(
			"projects",
			array(),
			array(
				array(
					"find",
					$path,
					array(
						"more than",
						0
					)
				),
				array(
					"limit",
					1
				)
			)
		);*/
		
		$query = "SELECT * FROM projects WHERE path=? LIMIT 1;";
		$bind_variables = array( $_SESSION["project"] );
		$result = $sql->query( $query, $bind_variables, array() )[0];
		
		if( ! empty( $result ) ) {
			
			$result = $result[0];
			try {
				
				$users = json_decode( $result["access"], true );
			} catch( exception $e ) {
				
				$users = array();
			}
			
			if( $result["owner"] == 'nobody' ) {
				
				$pass = true;
			} elseif( $result["owner"] == $_SESSION["user"] ) {
				
				$pass = true;
			} elseif( in_array( $_SESSION["user"], array_keys( $users ) ) && ! empty( $users ) ) {
				
				//Only allow the owner to delete the root dir / project
				if( $path == $result["path"] && self::LEVELS[$level] == self::LEVELS["delete"] ) {
					
					$level = "owner";
				}
				
				$is_assoc = ( array_keys( $users ) !== range( 0, count( $users ) - 1 ) );
				
				if( $is_assoc ) {
					
					$users_access = $users[$_SESSION["user"]];
				} else {
					
					$users_access = self::LEVELS["delete"];
				}
				
				echo var_dump( $path, $result, $users_access, $level, ( self::LEVELS[$level] >= $users_access ), self::LEVELS[$level] + " is more than or equal to {$users_access}" );
				
				if( self::LEVELS[$level] >= $users_access ) {
					
					$pass = true;
				}
			}
		}
		return( $pass );
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