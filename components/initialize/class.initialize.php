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
	const EXTENSIONS = array(
		"curl",
		"json",
		"mbstring",
	);
	const PATHS = array(
		"BASE_PATH",
		"COMPONENTS",
		"DATA",
		"PLUGINS",
		"SESSIONS_PATH",
		"THEMES",
		"UPLOAD_CACHE",
		"WORKSPACE",
	);

	protected static $instance = null;
	
	function __construct() {
		
		$config = realpath( dirname( __FILE__ ) . "/../../config.php" );
		$installing = self::is_installing();
		
		if( ! $installing ) {
			
			if( ( ! file_exists( $config ) || ! is_readable( $config ) ) ) {
				
				$message = "Error loading config file.";
				
				if( ! file_exists( $config ) ) {
					
					$message = "Error, could not find config file.  If you have not installed Codiad please go to <a href='./install'>/install/</a> to complete the installation.";
				} elseif( ! is_readable( $config ) ) {
					
					$message = "Error config file is not readable, please check permissions.";
				}
				echo $message;
				exit();
			} else {
				
				require_once( $config );
			}
		}
		
		$bases = self::BASES;
		
		$this->register_constants();
		
		foreach( $bases as $base ) {
			
			$name = strtolower( $base );
			$class = ucfirst( $base );
			require_once( COMPONENTS . "/$name/class.$name.php" );
		}
		
		$this->register_globals();
		
		if( ! $installing ) {
			
			$this->check_extensions();
			$this->check_paths();
		}
	}
	
	public static function check_extensions() {
		
		$extensions = self::EXTENSIONS;
		$pass = true;
		
		foreach( $extensions as $extension ) {
			
			if( extension_loaded( $extension ) ) {
				
				
			} else {
				
				$pass = false;
				break;
			}
		}
		return $pass;
	}
	
	public static function check_paths() {
		
		$paths = self::PATHS;
		$pass = true;
		
		foreach( $paths as $path ) {
			
			if( is_dir( constant( $path ) ) ) {
				
				if( ! is_writable( constant( $path ) ) ) {
					
					$pass = false;
					break;
				}
			} else {
				
				mkdir( constant( $path ) );
			}
		}
		return $pass;
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
	
	public static function is_installing() {
		
		return ! ( strpos( $_SERVER["SCRIPT_NAME"], "install" ) === false );
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
		
		if( ! defined( 'THEME' ) ) {
			
			define( "THEME", "default" );
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
		
		if( ! defined( 'WORKSPACE' ) ) {
			
			define( "WORKSPACE", BASE_PATH . "/workspace" );
		}
	}
	
	function register_globals() {
		
		global $data;
		
		$data = Data::get_instance();
	}
}

?>