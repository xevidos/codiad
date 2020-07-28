<?php

namespace FileSystemStorage;

class Data {
	
	private $data = array();
	private $headers = array();
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
	
	public function get_headers() {
		
		return $this->headers;
	}
	
	public function get_meta() {
		
		return $this->meta;
	}
	
	public function set_data( $data ) {
		
		//Add checks to validate Data with headers
		//Maybe we should stop the data from being added or removed totally
		//and instead being edited one "row" at a time?
		//$this->data = $data;
		$return = \Common::get_default_return();
		$return["value"] = $data;
		$pass = true;
		$check_uniques = ( ! empty( $this->uniques ) );
		$uniques = array();
		
		foreach( $data as $id => $row ) {
			
			foreach( $this->meta as $title => $m ) {
				
				if( $title === "id" && $this->incremental && ( ! isset( $data[$id]["id"] ) || ! $data[$id]["id"] ) ) {
					
					continue;
				}
				
				if( isset( $row["$title"] ) ) {
					
					if( ! is_callable( $m["typeof"] ) ) {
						
						$return["status"] = "error";
						$return["message"] = "$title typeof function is not callable {$m["type"]}.";
						$return["row"] = $row;
						break;
					}
					
					$type = call_user_func( $m["typeof"], $row["$title"] );
					
					if( ! $type ) {
						
						$pass = false;
						$return["status"] = "error";
						$return["message"] = "$title must be typeof {$m["type"]}.";
						$return["row"] = $row;
						break;
					}
					
					if( $m["type"] === "string" && $m["length"] !== null && strlen( $row["$title"] ) >= $m["length"] ) {
						
						$pass = false;
						$return["status"] = "error";
						$return["message"] = "$title must be a length less than or equal to {$m["length"]}.";
						$return["row"] = $row;
						break;
					}
					
					if( $m["null"] === false && $row["$title"] === null  ) {
						
						$pass = false;
						$return["status"] = "error";
						$return["message"] = "$title can not be null.";
						$return["row"] = $row;
						break;
					}
				} else {
					
					if( $m["null"] === false && $m["default"] === null ) {
						
						$pass = false;
						$return["status"] = "error";
						$return["message"] = "$title can not be null.";
						$return["row"] = $row;
						break;
					} elseif( $m["null"] === false && $m["default"] !== null ) {
						
						$data[$id][$title] = $m["default"];
					}
				}
			}
			
			if( $pass && $check_uniques ) {
				
				foreach( $this->uniques as $u ) {
					
					$unique_key = "";
					$unique_value = "";
					
					if( is_array( $u ) ) {
						
						foreach( $u as $i ) {
							
							$unique_key .= "$i-";
							$unique_value .= $row["$i"] . "-";
						}
					} else {
						
						$unique_key = $u;
						$unique_value = $row[$u];
					}
					
					if( ! isset( $uniques[$unique_key] ) ) {
						
						$uniques[$unique_key] = array();
					}
					
					if( in_array( $unique_value, $uniques[$unique_key], true ) ) {
						
						$return["status"] = "error";
						$return["message"] = "Duplicate entry for $unique_key";
						$return["value"] = $unique_value;
						$pass = false;
						break;
					} else {
						
						$uniques[$unique_key][] = $unique_value;
					}
				}
			}
			
			if( $pass ) {
				
				if( isset( $row["id"] ) && $this->incremental && ( ! isset( $data[$id]["id"] ) || ! $data[$id]["id"] ) ) {
					
					$data[$id]["id"] = $this->increment;
					$this->increment += 1;
					continue;
				}
			} else {
				
				break;
			}
		}
		
		if( $pass ) {
			
			$this->data = $data;
			$return["status"] = "success";
			$return["message"] = "Updated table.";
			$return["value"] = $data;
		}
		return $return;
	}
	
	public function set_meta( $meta, $uniques = array() ) {
		
		$pass = true;
		$return = \Common::get_default_return();
		$required_meta = array(
			"default",
			"length",
			"null",
			"type",
			"typeof",
		);
		
		if( ! empty( $this->meta ) ) {
			
			$return["status"] = "error";
			$return["message"] = "The meta for this table is already set.";
			$pass = false;
		}
		
		if( $pass ) {
			
			foreach( $meta as $title => $m ) {
				
				foreach( $required_meta as $r ) {
					
					if( ! isset( $m[$r] ) ) {
						
						$return["status"] = "error";
						$return["message"] = "The meta field $r is required but has not been defined for the $title field.";
						break;
					}
				}
				if( ! $pass ) {
					
					break;
				}
			}
		}
		
		if( $pass ) {
			
			$this->headers = array_keys( $meta );
			$this->meta = $meta;
			$this->uniques = $uniques;
			
			if( in_array( "id", $this->headers ) ) {
				
				$this->incremental = true;
			}
		}
		return $return;
	}
}

?>