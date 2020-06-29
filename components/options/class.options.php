<?php

class Options {
	
	protected static $instance = null;
	public $language = "en";
	
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
		
		$query = array(
			"*" => "SELECT * FROM user_options WHERE name=? AND user=?",
			"pgsql" => 'SELECT value FROM user_options WHERE name=? AND "user"=?;',
			"filesystem" => array(
				"options",
				"get_option",
				$option
			),
		);
		$Data = Data::get_instance();
		return $Data->query( $query );
	}
	
	public function update_option( $option, $value ) {}
}

?>