<?php

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

class Initialize {
	
	/**
	 * A list of classes that are always included.
	 *
	 * @since	${current_version}
	 * @return	object	A single instance of this class.
	 */
	const BASES = array(
		"authentication",
		"common",
		"data",
		"events",
		"update",
	);
	const PATHS = array(
		"COMPONENTS",
		"DATA",
		"PLUGINS",
		"SESSIONS_PATH",
		"THEMES",
		"UPLOAD_CACHE",
	);
	
	protected static $instance = null;
	
	function __construct() {
		
		$config = realpath( __DIR__ . "/../../config.php" );
		
		if( ! file_exists( $config ) || ! is_readable( $config ) ) {
			
			$message = "Error loading config file.";
			
			if( ! file_exists( $config ) ) {
				
				$message = "Error, could not find config file.  If you have not installed Codiad please go to /install/ to complete the installation.";
			} elseif( ! is_readable( $config ) ) {
				
				$message = "Error config file is not readable, please check permissions.";
			}
			echo $message;
			exit();
		}
		
		require_once( $config );
		
		$this->register_constants();
		
		$bases = self::BASES;
		
		foreach( $bases as $base ) {
			
			$name = strtolower( $base );
			$class = ucfirst( $base );
			require_once( COMPONENTS . "/$name/class.$name.php" );
			
			if( class_exists( $class ) ) {
				
				$class::get_instance();
			}
		}
		
		$this->register_globals();
		$this->check_paths();
		
	}
	
	function check_paths() {
		
		$paths = self::PATHS;
		
		foreach( $paths as $path ) {
			
			if( ! is_dir( constant( $path ) ) ) {
				
				mkdir( constant( $path ) );
			}
		}
	}
	
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
	
	function register_constants() {
		
		if( ! defined( 'AUTH_TYPE' ) ) {
			
			define( 'AUTH_TYPE', "default" );
		}
		
		if( ! defined( 'BASE_PATH' ) ) {
			
			$base_path = rtrim( realpath( __DIR__ . "/../../" ), '/' );
			
			define( 'BASE_PATH', $base_path );
		}
		
		if( ! defined( 'COMPONENTS' ) ) {
			
			define( 'COMPONENTS', BASE_PATH . '/components' );
		}
		
		if( ! defined( 'DATA' ) ) {
			
			define( 'DATA', BASE_PATH . '/data' );
		}
		
		if( ! defined( 'PLUGINS' ) ) {
			
			define( 'PLUGINS', BASE_PATH . '/plugins' );
		}
		
		if( ! defined( 'SESSIONS_PATH' ) ) {
			
			define( 'SESSIONS_PATH', BASE_PATH . '/data/sessions' );
		}
		
		if( ! defined( 'SITE_NAME' ) ) {
			
			define( 'SITE_NAME', "Codiad" );
		}
		
		if( ! defined( 'THEMES' ) ) {
			
			define( "THEMES", BASE_PATH . "/themes" );
		}
		
		if( ! defined( 'UPLOAD_CACHE' ) ) {
			
			if( ! is_dir( sys_get_temp_dir() ) ) {
				
				define( "UPLOAD_CACHE", DATA . "/uploads" );
			} else {
				
				define( "UPLOAD_CACHE", rtrim( sys_get_temp_dir(), "/" ) );
			}
		}
	}
	
	function register_globals() {
		
		
	}
}

?>