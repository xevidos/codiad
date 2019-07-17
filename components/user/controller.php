<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once('../../common.php');
require_once('class.user.php');

if( ! isset( $_GET['action'] ) ) {
	
	die( formatJSEND( "error", "Missing parameter" ) );
}

//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

if( $_GET['action'] != 'authenticate' ) {
	
	checkSession();
}

$User = new User();

//////////////////////////////////////////////////////////////////
// Authenticate
//////////////////////////////////////////////////////////////////

if($_GET['action']=='authenticate') {
	
	if( ! isset( $_POST['username'] ) || ! isset( $_POST['password'] ) ) {
		
		die( formatJSEND( "error", "Missing username or password" ) );
	}
	
	$User->username = User::CleanUsername( $_POST['username'] );
	$User->password = $_POST['password'];
	
	// check if the asked languages exist and is registered in languages/code.php
	require_once '../../languages/code.php';
	if( isset( $languages[$_POST['language']] ) ) {
		
		$User->lang = $_POST['language'];
	} else {
		
		$User->lang = 'en';
	}
	
	// theme
	$User->theme = $_POST['theme'];
	$User->Authenticate();
}

//////////////////////////////////////////////////////////////////
// Logout
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'logout' ) {
	
	logout();
}

//////////////////////////////////////////////////////////////////
// Create User
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'create' ) {
	
	if( checkAccess() ) {
		
		if ( ! isset( $_POST['username'] ) || ! isset( $_POST['password'] ) ) {
			
			exit( formatJSEND( "error", "Missing username or password" ) );
		}
		
		if ( ! ( $_POST['password'] === $_POST['password2'] ) ) {
			
			exit( formatJSEND( "error", "Passwords do not match" ) );
		}
		
		if ( preg_match( '/[^\w\-\._@]/', $_POST['username'] ) ) {
			
			exit( formatJSEND( "error", "Invalid characters in username" ) );
		}
		
		$User->username = User::CleanUsername( $_POST['username'] );
		$User->password = $_POST['password'];
		$User->Create();
	}
}

//////////////////////////////////////////////////////////////////
// Delete User
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'delete' ) {
	
	if( checkAccess() ) {
		
		if( ! isset( $_GET['username'] ) ) {
			
			die( formatJSEND( "error", "Missing username" ) );
		}
		
		$User->username = User::CleanUsername( $_GET['username'] );
		$User->Delete();
	}
}

//////////////////////////////////////////////////////////////////
// Change Password
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'password' ) {
	
	if( ! isset( $_POST['username']) || ! isset( $_POST['password'] ) ) {
		
		die( formatJSEND( "error", "Missing username or password" ) );
	}
	
	if( $_POST['username'] == $_SESSION['user'] || is_admin() ) {
		
		$User->username = User::CleanUsername( $_POST['username'] );
		$User->password = $_POST['password'];
		$User->Password();
	}
}

//////////////////////////////////////////////////////////////////
// Change Project
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'project' ) {
	
	if( ! isset( $_GET['project'] ) ) {
		
		die( formatJSEND( "error", "Missing project" ) );
	}
	
	$User->username = $_SESSION['user'];
	$User->project = $_GET['project'];
	$User->Project();
}

//////////////////////////////////////////////////////////////////
// Search Users
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'search_users' ) {
	
	if( ! isset( $_GET['search_term'] ) ) {
		
		die( formatJSEND( "error", "Missing search term" ) );
	}
	
	search_users( $_GET['search_term'], "exit", true );
}

//////////////////////////////////////////////////////////////////
// Verify User Account
//////////////////////////////////////////////////////////////////

if( $_GET['action'] == 'verify' ) {

	$User->username = $_SESSION['user'];
	checkSession();
}


if( $_GET['action'] == 'update_access' ) {

	checkSession();
	
	if( ! isset( $_GET['access'] ) || ! isset( $_GET['username'] ) ) {
		
		die( formatJSEND( "error", "Could not update access." ) );
	}
	
	if( ! is_admin() ) {
		
		die( formatJSEND( "error", "You do not have permission to update user's access." ) );
	}
	
	$User->username = $_GET["username"];
	$User->access  = $_GET["access"];
	$User->update_access();
}
