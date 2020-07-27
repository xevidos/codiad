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
	
	public function update_option( $option, $value ) {}
}

?>