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
		
		"find" => array(
			
			"mysql" => "LOCATE( %string%, %substring% )",
			"pgsql" => "POSITION( %substring% in %string% )",
			"sqlite" => "INSTR( %string%, %substring% )",
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
			
			"mysql" => "NOT NULL AUTO_INCREMENT PRIMARY KEY",
			"pgsql" => "SERIAL PRIMARY KEY",
			"sqlite" => "SERIAL PRIMARY KEY",
		),
		
		"key" => array(
			
			"mysql" => "KEY",
			"pgsql" => "KEY",
			"sqlite" => "KEY",
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
		
		"unique" => array(
			
			"mysql" => "CONSTRAINT %constraint_name% UNIQUE ( %field_names% )",
			"pgsql" => "UNIQUE",
			"sqlite" => "UNIQUE",
		),
	);
	
	public $wraps = array(
		
		"close" => array(
			
			"mysql" => "`",
			"mssql" => "]",
			"pgsql" => "\"",
			"sqlite" => "\"",
		),
		
		"open" => array(
			
			"mysql" => "`",
			"mssql" => "[",
			"pgsql" => "\"",
			"sqlite" => "\"",
		),
	);
	
	public function find( $string, $substring ) {
		
		$dbtype = DBTYPE;
		$id_close = $this->wraps["close"][$dbtype];
		$id_open = $this->wraps["open"][$dbtype];
		$find_string = $this->actions["find"][$dbtype];
		$find_string = str_replace( "%string%", $string, $find_string );
		$find_string = str_replace( "%substring%", $substring, $find_string );
		
		return $find_string;
	}
	
	public function table( $table_name, $fields, $attributes ) {
		
		$dbtype = DBTYPE;
		$id_close = $this->wraps["close"][$dbtype];
		$id_open = $this->wraps["open"][$dbtype];
		
		$query = "{$this->actions["create"][$dbtype]} {$table_name} (";
		
		foreach( $fields as $id => $type ) {
			
			$query .= "{$id} {$this->data_types[$type][$dbtype]}";
			
			foreach( $attributes[$id] as $attribute ) {
				
				$attribute_string = $this->specials["$attribute"][$dbtype];
				
				if( $attribute == "unique" && $dbtype == "mysql" ) {
					
					continue;
				}
				
				if( ! strpos( $attribute_string, "%table_name%" ) === FALSE ) {
					
					$attribute_string = str_replace( "%table_name%", $table_name, $attribute_string );
				}
				
				if( ! strpos( $attribute_string, "%fields%" ) === FALSE ) {
					
					$fields_string = "";
					
					foreach( $fields as $field ) {
						
						$fields_string .= "{$id_open}field{$id_close},";
					}
					
					$fields_string = substr( $fields_string, 0, -1 );
					$attribute_string = str_replace( "%fields%", $fields_string, $attribute_string );
				}
				$query .= " {$attribute_string}";
			}
			$query .= ",";
		}
		
		if( $dbtype == "mysql" ) {
			
			$constraint_name = "";
			$id_close = $this->wraps["close"][$dbtype];
			$id_open = $this->wraps["open"][$dbtype];
			$fields_string = "";
			$unique_string = "";
			
			foreach( $attributes as $id => $attributes ) {
				
				if( in_array( "unique", $attributes ) ) {
					
					if( $unique_string == "" ) {
						
						$unique_string = $this->specials["unique"] . ",";
					}
					$fields_string .= "{$id_open}{$id}{$id_close},";
				}
			}
			$unique_string = str_replace( "%constraint_name%", $constraint_name, $unique_string );
			$unique_string = str_replace( "%field_names%", $fields_string, $unique_string );
			$query .= $unique_string;
		}
		
		$query = substr( $query, 0, -1 );
		$query .= ");";
		return( $query );
	}
	
	public function tables( $tables ) {
		
		$query = "";
		
		foreach( $tables as $table_name => $table_data ) {
			
			$query .= $this->table( $table_name, $table_data["fields"], $table_data["attributes"] );
		}
		return( $query );
	}
}

?>
