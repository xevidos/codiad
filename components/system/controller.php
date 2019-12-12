<?php
require_once('../../common.php');

if ( ! isset( $_POST['action'] ) && ! isset( $_GET['action'] )  ) {
	
	die( formatJSEND( "error", "Missing parameter" ) );
} else {
	
	if( isset( $_POST["action"] ) ) {
		
		$action = $_POST["action"];
	} else {
		
		$action = $_GET["action"];
	}
}

//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

checkSession();

switch( $action ) {
	
	case( "create_default_tables" ):
		
		if( is_admin() ) {
			
			global $sql;
			$result = $sql->create_default_tables();
			
			//echo var_dump( $result );
			
			if( $result === true ) {
				
				exit( formatJSEND( "success", "Created tables." ) );
			} else {
				
				exit( formatJSEND( "error", array( "message" => "Could not create tables.", "result" => $result ) ) );
			}
		} else {
			
			exit( formatJSEND( "error", "Only admins can use this method." ) );
		}
	break;
	
	case( "get_ini_setting" ):
		
		if( isset( $_POST["option"] ) ) {
			
			$option = $_POST["option"];
		} else {
			
			$option = $_GET["option"];
		}
		
		exit( json_encode( array(
			"option" => $option,
			"value" => ini_get( $option ),
		)));
	break;
}