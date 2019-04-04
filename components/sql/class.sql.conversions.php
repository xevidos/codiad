<?php

class sql_conversions {
	
	public $actions = array(
		
		"create" => array(
			
			"mysql" => "CREATE TABLE IF NOT EXISTS",
			"pgsql" => "CREATE TABLE IF NOT EXISTS",
			"sqlite" => "CREATE TABLE IF NOT EXISTS",
		),
		"delete" => array(
			
			"mysql" => "DELETE",
			"pgsql" => "DELETE",
			"sqlite" => "DELETE",
		),
		"select" => array(
			
			"mysql" => "SELECT",
			"pgsql" => "SELECT",
			"sqlite" => "SELECT",
		),
		"update" => array(
			
			"mysql" => "UPDATE",
			"pgsql" => "UPDATE",
			"sqlite" => "UPDATE",
		),
	);
	
	public $comparisons = array(
		
		"equal" => array(
			
			"mysql" => "=",
			"pgsql" => "=",
			"sqlite" => "=",
		),
		"less than" => array(
			
			"mysql" => "<",
			"pgsql" => "<",
			"sqlite" => "<",
		),
		"more than" => array(
			
			"mysql" => ">",
			"pgsql" => ">",
			"sqlite" => ">",
		),
		"not" => array(
			
			"mysql" => "!",
			"pgsql" => "!",
			"sqlite" => "!",
		),
		"not equal" => array(
			
			"mysql" => "!=",
			"pgsql" => "!=",
			"sqlite" => "!=",
		),
	);
	
	public $data_types = array(
		
		"bool" => array(
			
			"mysql" => "BOOL",
			"pgsql" => "BOOL",
			"sqlite" => "BOOL",
		),
		"int" => array(
			
			"mysql" => "INT",
			"pgsql" => "INT",
			"sqlite" => "INT",
		),
		"string" => array(
			
			"mysql" => "VARCHAR",
			"pgsql" => "VARCHAR",
			"sqlite" => "VARCHAR",
		),
		"text" => array(
			
			"mysql" => "TEXT",
			"pgsql" => "TEXT",
			"sqlite" => "TEXT",
		),
	);
	
	public $specials = array(
		
		"id" => array(
			
			"mysql" => "PRIMARY KEY",
			"pgsql" => "PRIMARY KEY",
			"sqlite" => "PRIMARY KEY",
		),
		"key" => array(
			
			"mysql" => "CONSTRAINT %table_name% UNIQUE(%fields%)",
			"pgsql" => "CONSTRAINT %table_name% UNIQUE(%fields%)",
			"sqlite" => "CONSTRAINT %table_name% UNIQUE(%fields%)",
		),
		"auto increment" => array(
			
			"mysql" => "AUTO_INCREMENT",
			"pgsql" => "AUTO_INCREMENT",
			"sqlite" => "AUTO_INCREMENT",
		),
		"not null" => array(
			
			"mysql" => "NOT NULL",
			"pgsql" => "NOT NULL",
			"sqlite" => "NOT NULL",
		),
		"null" => array(
			
			"mysql" => "NULL",
			"pgsql" => "NULL",
			"sqlite" => "NULL",
		),
	);
}

?>
