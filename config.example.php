<?php

//////////////////////////////////////////////////////////////////
// CONFIG
//////////////////////////////////////////////////////////////////

// PATH TO CODIAD
define( "BASE_PATH", "absolute/path/to/codiad" );

// BASE URL TO CODIAD (without trailing slash)
define( "BASE_URL", "domain.tld" );

// THEME : default, modern or clear (look at /themes)
define( "THEME", "default" );

// ABSOLUTE PATH, this is used as whitelist for absolute path projects 
define( "WHITEPATHS", array( "/home" ) );

// Site Name
define( "SITE_NAME", "Codiad" );

// Data Storage info
define( "DBHOST", "localhost" );
define( "DBNAME", "database" );
define( "DBUSER", "username" );
define( "DBPASS", "password" );
define( "DBTYPE", "mysql" );

//////////////////////////////////////////////////////////////////
// ** DO NOT EDIT CONFIG BELOW **
//////////////////////////////////////////////////////////////////

// PATHS
define( "COMPONENTS", BASE_PATH . "/components" );
define( "DATA", BASE_PATH . "/data" );
define( "PLUGINS", BASE_PATH . "/plugins" );
define( "THEMES", BASE_PATH . "/themes" );
define( "WORKSPACE", BASE_PATH . "/workspace" );

// URLS
define( "WSURL", BASE_URL . "/workspace" );

?>
