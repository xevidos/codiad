<?php

/*
 *  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
 *  as-is and without warranty under the MIT License. See 
 *  [root]/license.txt for more. This information must remain intact.
 */

require_once( '../../common.php' );
require_once( './class.archive.php' );

//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

checkSession();

$response = array(
	"status" => "none",
	"message" => null,
);

if( ! isset( $_GET["path"] ) && ! isset( $_POST["path"] ) ) {
	
	$response["status"] = "error";
	$response["message"] = "Missing path.";
	exit( json_encode( $response ) );
}

$path = ( isset( $_GET["path"] ) ) ? $_GET["path"] : $_POST["path"];
$full_path = "";

if( Common::isAbsPath( $path ) ) {
	
	$full_path = realpath( $path );
} else {
	
	$full_path = WORKSPACE . "/$path";
	$full_path = realpath( $full_path );
}

if( $full_path === false ) {
	
	$response["status"] = "error";
	$response["message"] = "Invalid path.";
	exit( json_encode( $response ) );
}

if( ! Permissions::has_read( $path ) ) {
	
	$response["status"] = "error";
	$response["message"] = "You do not have access to this path.";
	exit( json_encode( $response ) );
}

if( is_dir( $full_path ) ) {
	
	$temp_path = tempnam( sys_get_temp_dir(), 'codiad_download_' . date( "U" ) );
	$result = Archive::compress( $full_path, $temp_path, 'default' );
	$mime_type = mime_content_type( $temp_path );
	
	if( in_array( $mime_type, array_keys( Archive::MIME_TYPE_EXTENSIONS ) ) ) {
		
		$extension = Archive::MIME_TYPE_EXTENSIONS["$mime_type"];
	}
	
	if( $result ) {
		
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $full_path ) . ".$extension" . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $temp_path ) );
		
		if( ob_get_contents() ) {
			
			ob_end_clean();
		}
		
		flush();
		file_put_contents( "php://output", file_get_contents( $temp_path ) );
	} else {
		
		$response["status"] = "error";
		$response["message"] = "An archive could not be created.";
	}
	unlink( $temp_path );
} else {
	
	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename="' . basename( $full_path ) . '"' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . filesize( $full_path ) );
	
	if( ob_get_contents() ) {
		
		ob_end_clean();
	}
	
	flush();
	readfile( $full_path );
}
