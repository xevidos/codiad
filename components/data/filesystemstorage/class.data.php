<?php

namespace FileSystemStorage;

class Data {
	
	public $data = array();
	public $headers = array();
	private $increment = 0;
	private $incremental = false;
	protected static $instance = null;
	private $meta = array();
	private $uniques = array();
	
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
	
	public function get_data( $fields = array() ) {
		
		return $this->data;
	}
	
	public function set_data( $data ) {
		
		//Add checks to validate Data with headers
		//$this->data = $data;
		$return = \Common::get_default_return();
		
	}
	
	public function set_meta( $meta, $uniques = array() ) {
		
		$return = \Common::get_default_return();
		
		if( ! empty( $this->meta ) ) {
			
			$return["status"] = "error";
			$return["message"] = "The meta for this table is already set.";
			return $return;
		}
		
		$this->headers = array_keys( $meta );
		$this->meta = $meta;
		$this->uniques = $uniques;
	}
}

?>