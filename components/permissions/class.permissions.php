<?php

class Permissions {
	
	const LEVELS = array(
		
		"none" => 0,
		"read" => 1,
		"write" => 2,
		"create" => 4,
		"delete" => 8,
		"manager" => 16,
		"owner" => 32,
		"admin" => 64,
	);
	
	const SYSTEM_LEVELS = array(
		
		"user" => 32,
		"admin" => 64,
	);
	
	function __construct() {}
}
