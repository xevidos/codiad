<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/


require_once('../../common.php');
require_once('./class.project.php');

//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

checkSession();

$Project = new Project();
$Project->projects = $Project->get_projects();

if( $_GET['action'] == 'add_user' ) {
	
	$invalid_users = array(
		"",
		"null",
		"undefined"
	);
	
	if( ! in_array( $_GET['username'], $invalid_users ) ) {
		
		$Project->user = $_GET['username'];
	} else {
		
		echo formatJSEND( "error", "No username set." );
		return;
	}
	
	if( $_GET['project_path'] != '' ) {
		
		$Project->path = $_GET['project_path'];
	} else {
		
		echo formatJSEND( "error", "No project path set." );
		return;
	}
	
	if( $Project->check_owner( $_GET["project_path"], true ) ) {
		
		$Project->add_user();
	} else {
		
		echo formatJSEND( "error", "You can not manage this project." );
	}
}


//////////////////////////////////////////////////////////////////
// Create Project
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'create' ) {
		
	$Project->name = $_GET['project_name'];
	
	if( $_GET['public_project'] == 'true' ) {
		
		$Project->public_project = true;
	}
	
	if( $_GET['project_path'] != '' ) {
		
		$Project->path = $_GET['project_path'];
	} else {
		
		$Project->path = $_GET['project_name'];
	}
	// Git Clone?
	if( ! empty( $_GET['git_repo'] ) ) {
		
		$Project->gitrepo = $_GET['git_repo'];
		$Project->gitbranch = $_GET['git_branch'];
	}
	$Project->Create();
}

//////////////////////////////////////////////////////////////////
// Return Current
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'current' ) {
	
	if( isset( $_SESSION['project'] ) ) {
		
		echo formatJSEND( "success", $_SESSION['project'] );
	} else {
		
		echo formatJSEND( "error", "No Project Returned" );
	}
}

//////////////////////////////////////////////////////////////////
// Delete Project
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'delete' ) {
	
	if( checkPath( $_GET['project_path'] ) ) {
		
		$Project->path = $_GET['project_path'];
		$Project->Delete();
	}
}

//////////////////////////////////////////////////////////////////
// Get Project Access
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'get_access' ) {
	
	$Project->path = $_GET['project_path'];
	$access = $Project->get_access( $_GET['project_path'] );
	echo formatJSEND( "success", $access );
}

//////////////////////////////////////////////////////////////////
// Get Current Project
//////////////////////////////////////////////////////////////////

$no_return = false;
if( isset( $_GET['no_return'] ) ) {
	
	$no_return = true;
}

if( $_GET['action'] == 'get_current' ) {

	if( ! isset( $_SESSION['project'] ) ) {
		
		// Load default/first project
		if( $no_return ) {
			
			$Project->no_return = true;
		}
		$Project->GetFirst();
	} else {
		
		// Load current
		$Project->path = $_SESSION['project'];
		$project_name = $Project->GetName();
		if( ! $no_return ) {
			
			echo formatJSEND( "success", array( "name" => $project_name, "path" => $_SESSION['project'] ) );
		}
	}
}

//////////////////////////////////////////////////////////////////
// Check Project Owner
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'get_owner' ) {
	
	$Project->path = $_GET['project_path'];
	$owner = $Project->get_owner();
	try {
		
		$return = json_decode( $owner );
		exit( formatJSEND( "error", null ) );
	} catch( exception $e ) {
		
		exit( formatJSEND( "success", array( "owner" => $owner ) ) );
	}
}

//////////////////////////////////////////////////////////////////
// Open Project
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'open' ) {
	
	if( ! checkPath( $_GET['path'] ) ) {
		
		die( formatJSEND( "error", "No Access to path " . $_GET['path'] ) );
	}
	$Project->path = $_GET['path'];
	$Project->Open();
}

if( $_GET['action'] == 'remove_user' ) {
	
	$invalid = array(
		"",
		"null",
		"undefined"
	);
	
	if( ! in_array( $_GET['username'], $invalid ) ) {
		
		$Project->user = $_GET['username'];
	} else {
		
		echo formatJSEND( "error", "No username set." );
		return;
	}
	
	if(	! in_array( $_GET['project_path'], $invalid ) ) {
		
		$Project->path = $_GET['project_path'];
	} else {
		
		echo formatJSEND( "error", "No project path set." );
		return;
	}
	
	if( $Project->check_owner( $_GET["project_path"], true ) ) {
		
		$Project->remove_user();
	} else {
		
		echo formatJSEND( "error", "You can not manage this project." );
	}
}

//////////////////////////////////////////////////////////////////
// Rename Project
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'rename' ) {
	
	if( ! checkPath( $_GET['project_path'] ) ) {
		
		die( formatJSEND( "error", "No Access" ) );
	}
	$Project->path = $_GET['project_path'];
	$Project->Rename();
}

