<?php

namespace FileSystemStorage;

class Data {
	
	protected static $instance = null;
	
	public $headers = array();
	public $data = array();
	
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
	
	public function query(  ) {
		
		
	}
}

?>