<?php

class Events {
	
	protected static $instance = null;
	
	private $subscriptions = array();
	
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
	
	/**
	 * Publish an event that will run all functions that have been previously
	 * added to the subscription lists.
	 *
	 * @since	${current_version}
	 * @return	null
	 */
	public function publish( $publication, $object ) {
		
		if( ! is_array( $this->subscriptions[$publication] ) ) {
			
			$this->subscriptions[$publication] = array();
		}
		
		foreach( $this->subscriptions[$publication] as $s ) {
			
			$s( $object );
		}
		return null;
	}
	
	/**
	 * Subscribe to an event that will be published later.
	 *
	 * @since	${current_version}
	 * @return	function	A function that can be run at any time to remove the
	 * subscription related from the subscriptions list.
	 */
	public function subscribe( $publication, $function ) {
		
		$_ = $this;
		
		if( ! is_array( $_->subscriptions[$publication] ) ) {
			
			$_->subscriptions[$publication] = array();
		}
		
		$i = array_push( $_->subscriptions[$publication], $function );
		$remove = function() {
			
			return array_pop( $_->subscriptions[$publication], $i, 1 );
		};
		return $remove;
	}
}

?>