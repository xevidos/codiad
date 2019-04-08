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
		
		"where" => array(
			
			"mysql" => "WHERE",
			"pgsql" => "WHERE",
			"sqlite" => "WHERE",
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
	
	public $general = array(
		
		"from" => array(
			
			"mysql" => "FROM",
			"pgsql" => "FROM",
			"sqlite" => "FROM",
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
			
			"mysql" => "CONSTRAINT '%constraint_name%' UNIQUE ( %field_names% )",
			"pgsql" => "CONSTRAINT '%constraint_name%' UNIQUE ( %field_names% )",
			"sqlite" => "CONSTRAINT '%constraint_name%' UNIQUE ( %field_names% )",
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
	
	public function check_field( $needle, $haystack ) {
		
		$field = preg_replace_callback(
			// Matches parts to be replaced: '[field]'
			'/(\[.*?\])/',
			// Callback function. Use 'use()' or define arrays as 'global'
			function( $matches ) use ( $haystack ) {
				
				// Remove square brackets from the match
				// then use it as variable name
				$match = trim( $matches[1], "[]" );
				return $match;
			},
			// Input string to search in.
			$needle
		);
		
		if( $field === $needle ) {
			
			$field = false;
		}
		return $field;
	}
	
	public function find( $substring, $string ) {
		
		$dbtype = DBTYPE;
		$find_string = $this->actions["find"][$dbtype];
		$find_string = str_replace( "%string%", $string, $find_string );
		$find_string = str_replace( "%substring%", $substring, $find_string );
		
		return $find_string;
	}
	
	public function select( $table, $fields, $where ) {
		
		$dbtype = DBTYPE;
		$id_close = $this->wraps["close"][$dbtype];
		$id_open = $this->wraps["open"][$dbtype];
		$query = $this->actions["select"][$dbtype] . " ";
		$bind_vars = array();
		
		if( empty( $fields ) ) {
			
			$query .= " * ";
		}
		
		foreach( $fields as $field ) {
			
			$query .= $field . ",";
		}
		
		$query = substr( $query, 0, -1 );
		$query .= " {$this->general["from"][$dbtype]} {$table} ";
		
		if( ! empty( $where ) ) {
			
			$query .= " {$this->comparisons["where"][$dbtype]} ";
		}
		
		foreach( $where as $comparison ) {
			
			$comparison_string = "";
			
			//Put a replace of %% symbols with fields and open / close
			if( $comparison[0] == "find" ) {
				
				$c1 = $this->check_field( $comparison[1], $fields );
				$c2 = $this->check_field( $comparison[2], $fields );
				$c3 = $this->check_field( $comparison[3][1], $fields );
				
				if( ! $c1 === FALSE ) {
					
					$c1 = $id_open . $c1 . $id_close;
				} else {
					
					$c1 = "?";
					array_push( $bind_vars, $comparison[1] );
				}
				
				if( ! $c2 === FALSE ) {
					
					$c2 = $id_open . $c2 . $id_close;
				} else {
					
					$c2 = "?";
					array_push( $bind_vars, $comparison[2] );
				}
				
				if( ! $c3 === FALSE ) {
					
					$c3 = $id_open . $c3 . $id_close;
				} else {
					
					$c3 = "?";
					array_push( $bind_vars, $comparison[3][1] );
				}
				
				$c0 = $this->find( $c1, $c2 );
				$comparison_string .= "{$c0} {$this->comparisons[$comparison[3][0]][$dbtype]} {$c3}";
			} elseif( $comparison[0] == "in" ) {
				
				
			} elseif( $comparison[0] == "limit" ) {
				
				
			} else {
				
				if( in_array( $fields, $comparison[1] ) ) {
					
					$comparison[1] = $id_open . $comparison[1] . $id_close;
				}
				
				if( in_array( $fields, $comparison[3] ) ) {
					
					$comparison[3] = $id_open . $comparison[3] . $id_close;
				}
				
				$comparison_string .= "{$comparison[1]} {$this->$comparisons[$comparison[0]][$dbtype]} {$comparison[2]}";
			}
			
			$index = array_search( $comparison, $where );
			
			if( $index  ) {
				
			} else {
				
				$query .= "{$comparison_string} ";
			}
		}
		
		//$query = substr( $query, 0, -1 );
		$query .= ";";
		return array( $query, $bind_vars );
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
				
				if( $attribute == "unique" ) {
					
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
		
		$unique_string = str_replace( "%constraint_name%", $fields_string, $unique_string );
		$unique_string = str_replace( "%field_names%", $fields_string, $unique_string );
		$query .= $unique_string;
		
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
	
	public function update( $table, $fields, $where ) {
		
		
	}
}

?>
