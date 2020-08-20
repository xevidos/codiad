<?php

class Options {
	
	protected static $instance = null;
	
	function __construct() {}
	
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
	
	public function get_option( $option ) {
		
		global $data;
		$query = array(
			"default" => "SELECT * FROM user_options WHERE name=? AND user=?",
			"pgsql" => 'SELECT value FROM user_options WHERE name=? AND "user"=?;',
			"filesystem" => array(
				"options",
				"get_option",
				$option
			),
		);
		return $data->query( $query );
	}
	
	public function update_config( $option, $value ) {
		
		$dir = BASE_PATH;
		$config = $dir . "/config.php";
		$new_config = "$config.tmp";
		$return = Common::get_default_return();
		$replaced = false;
		
		if( is_file( $config ) ) {
			
			$handle = fopen( $config, "r+" );
			$new_handle = fopen( $new_config, "w" );
			$old_line = "";
			$new_line = "";
			
			if( flock( $handle, LOCK_EX ) ) {
				
				while( ! feof( $handle ) ) {
					
					$line = fgets( $handle );
					
					if( strpos( $line, "define( \"$option\"" ) !== false ) {
						
						$replaced = true;
						$old_line = $line;
						$line = 'define( "' . $option . '", ' . $value . ' );' . PHP_EOL;
						$new_line = $line;
					}
					
					fputs( $new_handle, $line );
				}
				
				fclose( $handle );
				fclose( $new_handle );
				
				if( $replaced ) {
					
					unlink( $config );
					rename( $new_config, $config );
					$return["status"] = "success";
					$return["new_line"] = $line;
					$return["new_line"] = $new_line;
				} else {
					
					unlink( $new_config );
					$return["status"] = "error";
					$return["message"] = "Could not find defined option.";
				}
			} else {
				
				$return["status"] = "error";
				$return["message"] = "Could not get exclusive lock on config file.";
			}
		} else {
			
			$return["status"] = "error";
			$return["message"] = "Could not find config file.";
		}
		
		return $return;
	}
	
	public function update_option( $option, $value ) {}
}

?>