<?php

require_once('./class.tasks.php');

if( isset( $_POST["action"] ) ) {
	
	$action = $_POST["action"];
} elseif( isset( $_GET["action"] ) ) {
	
	$action = $_GET["action"];
} else {
	
	exit( formatJSEND( "error", "No action was specified" ) );
}

switch( $action ) {
	
	case( "get_task" ):
		
		
	break;
	
	default:
		
		exit( formatJSEND( "error", "An invalid action was specified" ) );
	break;
}

?>