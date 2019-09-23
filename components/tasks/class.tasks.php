<?php

require_once('../../common.php');

class Tasks {
	
	function __construct() {}
	
	static function build_command( $command, $arguments = array() ) {
		
		$query = "";
		$query .= "& echo $!";
		return $query;
	}
	
	public static function create_task( $command, $arguments = array() ) {
		
		$return = array(
			"status" => "none",
			"message" => "",
		);
		$command = self::build_command( $command, $arguments );
		
		if( function_exists( "exec" ) ) {
			
			
		} elseif( function_exists( "shell_exec" ) ) {
			
			
		} elseif( function_exists( "system" ) ) {
			
			
		} else {
			
			$return["status"] = "error";
			$return["message"] = "Could not find an enabled shell execution function.";
		}
		
		return $return;
	}
	
	public static function get_task( $id ) {
		
		$return = array(
			"status" => "none",
			"message" => "",
		);
		
		if( is_numeric( $id ) ) {
			
			if( function_exists( "exec" ) ) {
				
				
			} elseif( function_exists( "shell_exec" ) ) {
				
				
			} elseif( function_exists( "system" ) ) {
				
				
			} else {
				
				$return["status"] = "error";
				$return["message"] = "Could not find an enabled shell execution function.";
			}
		} else {
			
			$return["status"] = "error";
			$return["message"] = "Invalid PID";
		}
		return $return;
	}
	
	public static function kill_task( $id ) {
		
		$return = array(
			"status" => "none",
			"message" => "",
		);
		
		if( is_numeric( $id ) ) {
			
			$command = "kill -9 {$id}";
			
			if( function_exists( "exec" ) ) {
				
				
			} elseif( function_exists( "shell_exec" ) ) {
				
				
			} elseif( function_exists( "system" ) ) {
				
				
			} else {
				
				$return["status"] = "error";
				$return["message"] = "Could not find an enabled shell execution function.";
			}
		} else {
			
			$return["status"] = "error";
			$return["message"] = "Invalid PID";
		}
		return $return;
	}
}

?>