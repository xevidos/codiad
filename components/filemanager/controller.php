<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once( '../../common.php' );
require_once( 'class.filemanager.php' );

//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

checkSession();

//////////////////////////////////////////////////////////////////
// Get Action
//////////////////////////////////////////////////////////////////

$response = array(
	"status" => "none",
);

if( ! empty($_GET['action'] ) ) {
	
	$action = $_GET['action'];
} else {
	
	$response["status"] = "error";
	$response["data"] = array(
		"error" => "No action specified"
	);
	exit( json_encode( $response ) );
}

//////////////////////////////////////////////////////////////////
// Ensure Project Has Been Loaded
//////////////////////////////////////////////////////////////////

if( ! isset( $_SESSION['project'] ) ) {
	
	$_GET['action'] = 'get_current';
	$_GET['no_return'] = 'true';
	require_once('../project/controller.php');
}

if( isset( $_GET["path"] ) || isset( $_POST["path"] ) ) {
	
	$path = isset( $_GET["path"] ) ? $_GET["path"] : $_POST["path"];
} else {
	
	$response["status"] = "error";
	$response["message"] = "Missing path.";
	$response["GET"] = $_GET;
	$response["POST"] = $_POST;
	exit( json_encode( $response ) );
}

//////////////////////////////////////////////////////////////////
// Security Check
//////////////////////////////////////////////////////////////////

$access = Permissions::get_access( $path );

if ( ! Permissions::check_access( "read", $access ) ) {
	
	$response["status"] = "error";
	$response["message"] = "Invalid access to path";
	exit( json_encode( $response ) );
}

if( isset( $_GET["destination"] ) || isset( $_POST["destination"] ) ) {
	
	$destination = isset( $_GET["destination"] ) ? $_GET["destination"] : $_POST["destination"];

	if ( ! checkPath( $destination ) ) {
		
		$response["status"] = "error";
		$response["message"] = "Invalid destination";
		exit( json_encode( $response ) );
	}
}

//////////////////////////////////////////////////////////////////
// Handle Action
//////////////////////////////////////////////////////////////////

$Filemanager = new Filemanager();

switch( $action ) {
	
	case 'archive':
		
		if( ! isset( $path ) ) {
			
			exit( formatJSEND( "error", "No path specified." ) );
		}
		
		if( ! Permissions::check_access( "create", $access ) ) {
			
			exit( formatJSEND( "error", "Invalid access to create archive." ) );
		}
		
		$Archive = new Archive();
		$path = $Filemanager->formatPath( $path );
		$result = $Archive->compress( $path );
		
		if( $result ) {
			
			$response = formatJSEND( "success", null );
		} else {
			
			$response = formatJSEND( "error", "Could not create archive." );
		}
		
		exit( $response );
	break;
	
	case( 'copy' ):
		
		if( isset( $_POST["replace"] ) ) {
			
			$replace = $_POST["replace"];
		} else {
			
			$replace = false;
		}
		
		$response = $Filemanager->copy( $path, $destination, $replace );
	break;
	
	case 'create':
		
		if( isset( $_GET["type"] ) ) {
			
			$type = $_GET["type"];
			$response = $Filemanager->create( $path, $type );
		} else {
			
			$response["status"] = "error";
			$response["message"] = "No filetype set";
		}
	break;
	
	case 'delete':
		
		$response = $Filemanager->delete( $path, true );
	break;
	
	case 'delete_children':
		
		$response = $Filemanager->delete( $path, true, true );
	break;
	
	case 'find':
		
		if( ! isset( $_GET["query"] ) ) {
			
			$response["status"] = "error";
			$response["message"] = "Missing search query.";
		} else {
			
			$query = $_GET["query"];
			if( isset( $_GET["options"] ) ) {
				
				$options = $_GET["options"];
			}
			
			$response = $Filemanager->find( $path, $query, @$options );
		}
	break;
	
	case 'index':
		
		$response = $Filemanager->index( $path );
	break;
	
	case 'modify':
		
		if( isset( $_POST["data"] ) ) {
			
			$data = json_decode( $_POST["data"], true );
			
			if( json_last_error() !== JSON_ERROR_NONE ) {
				
				$data = json_decode( stripslashes( $_POST["data"] ), true );
			}
			
			if( json_last_error() !== JSON_ERROR_NONE ) {
				
				$data = array();
			}
			
			if( isset( $data["content"] ) || isset( $data["patch"] ) ) {
				
				$content = isset( $data["content"] ) ? $data["content"] : "";
				$patch = isset( $data["patch"] ) ? $data["patch"] : false;
				$mtime = isset( $data["mtime"] ) ? $data["mtime"] : 0;
				
				$response = $Filemanager->modify( $path, $content, $patch, $mtime );
			} else {
				
				$response["status"] = "error";
				$response["message"] = "Missing modification content";
			}
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Missing save data";
		}
	break;
	
	case 'move':
		
		if( isset( $destination ) ) {
			
			$response = $Filemanager->move( $path, $destination );
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Missing destination";
		}
	break;
	
	case 'open':
		
		$response = $Filemanager->open( $path );
	break;
	
	case 'preview':
		
		$response = $Filemanager->preview( $path );
	break;
	
	case 'rename':
		
		if( isset( $destination ) ) {
			
			$response = $Filemanager->move( $path, $destination );
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Missing destination";
		}
	break;
	
	case 'search':
		
		if( isset( $path ) && isset( $_POST["query"] ) ) {
			
			$query = $_POST["query"];
			
			if( isset( $_POST["options"] ) ) {
				
				$options = json_decode( $_POST["options"], true );
			} else {
				
				$options = array();
			}
			
			$response = $Filemanager->search( $path, $query, $options );
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Missing search query.";
		}
	break;
	
	case( "stitch" ):
		
		$response = $Filemanager->stitch( $path );
	break;
	
	case 'unarchive':
		
		if( ! isset( $path ) ) {
			
			exit( formatJSEND( "error", "No path specified." ) );
		}
		
		if( ! Permissions::check_access( "create", $access ) ) {
			
			exit( formatJSEND( "error", "Invalid access to unzip archive." ) );
		}
		
		$Archive = new Archive();
		$path = $Filemanager->formatPath( $path );
		$result = $Archive->decompress( $path );
		
		if( $result && $result["status"] == "success" ) {
			
			$response = formatJSEND( "success", $result );
		} else {
			
			$response = formatJSEND( "error", $result["message"] );
		}
		
		exit( $response );
	break;
	
	case 'upload_blob':
		
		if( ! isset( $_POST["data"] ) ) {
			
			$response["status"] = "error";
			$response["data"] = array(
				"error" => "No blob given"
			);
			exit( json_encode( $response ) );
		}
		
		if( ! isset( $_POST["path"] ) ) {
			
			$response["status"] = "error";
			$response["data"] = array(
				"error" => "No path given"
			);
			exit( json_encode( $response ) );
		}
		
		if( ! isset( $_POST["index"] ) ) {
			
			$response["status"] = "error";
			$response["data"] = array(
				"error" => "No index given"
			);
			exit( json_encode( $response ) );
		}
		
		$blob = @file_get_contents( $_POST["data"] );
		$path = $_POST["path"];
		$index = $_POST["index"];
		
		$response = $Filemanager->upload_blob( $path, $index, $blob );
	break;
	
	case 'upload_stitch':
		
		$Filemanager->upload_stitch( $path );
	break;
	
	default:
		
		$response["status"] = "error";
		$response["data"] = array(
			"error" => "Unknown action"
		);
	break;
}

exit( json_encode( $response ) );
