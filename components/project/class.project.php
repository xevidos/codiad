<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), Isaac Brown
*  distributed as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once( '../../common.php' );

class Project extends Common {
	
	//////////////////////////////////////////////////////////////////
	// PROPERTIES
	//////////////////////////////////////////////////////////////////
	
	public $access = Permissions::LEVELS["read"];
	public $name = '';
	public $path = '';
	public $gitrepo = false;
	public $gitbranch = '';
	public $projects = array();
	public $no_return = false;
	public $assigned = false;
	public $command_exec = '';
	public $public_project = false;
	public $user = '';
	
	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Construct
	//////////////////////////////////////////////////////////////////
	
	public function __construct() {
		
	}
	
	//////////////////////////////////////////////////////////////////
	// NEW METHODS
	//////////////////////////////////////////////////////////////////
	
	public function add_project( $project_name, $project_path, $owner = null ) {
		
		global $sql;
		if( $this->public_project ) {
			
			$owner = 'nobody';
		} else {
			
			$owner = $_SESSION["user"];
		}
		
		$query = "INSERT INTO projects( name, path, owner ) VALUES ( ?, ?, ? );";
		$bind_variables = array( $project_name, $project_path, $owner );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( ! ( $return > 0 ) ) {
			
			exit( formatJSEND( "error", "Error creating project $project_name" ) );
		}
	}
	
	public function add_user() {
		
		global $sql;
		$query = "SELECT * FROM projects WHERE path=? AND owner=? LIMIT 1";
		$bind_variables = array( $this->path, $_SESSION["user"] );
		$project = $sql->query( $query, $bind_variables, array(), "fetch" );
		
		if( empty( $project ) ) {
			
			exit( formatJSEND( "error", "Error fetching projects." ) );
		}
		
		$user_id = get_user_id( $this->user );
		
		if( $user_id === false ) {
			
			exit( formatJSEND( "error", "Error fetching user information." ) );
		}
		
		$user = $sql->query( "SELECT * FROM access WHERE project = ? AND user = ?", array( $project["id"], $user_id ), array(), "fetch" );
		
		if( ! empty( $user ) ) {
			
			$query = "UPDATE access SET level=? WHERE project=? AND user=?;";
			$bind_variables = array( $this->access, $project["id"], $user_id );
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $result > 0 ) {
				
				echo formatJSEND( "success", "Successfully updated {$this->user}." );
			} else {
				
				echo formatJSEND( "error", "Error setting access for project." );
			}
		} else {
			
			$query = "INSERT INTO access ( project, user, level ) VALUES ( ?,?,? );";
			$bind_variables = array( $project["id"], $user_id, $this->access );
			$result = $sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $result > 0 ) {
				
				echo formatJSEND( "success", "Successfully added {$this->user}." );
			} else {
				
				echo formatJSEND( "error", "Error setting access for project." );
			}
		}
	}
	
	public function check_duplicate( $full_path ) {
		
		global $sql;
		$pass = true;
		$query = "SELECT id, path, owner FROM projects;";
		$result = $sql->query( $query, array(), array(), "fetchAll" );
		
		if( ! empty( $result ) ) {
			
			foreach( $result as $row => $project ) {
				
				$full_project_path = Common::isAbsPath( $project["path"] ) ? $project["path"] : WORKSPACE . "/{$project["path"]}";
				
				if( ! ( strpos( $full_path, $full_project_path ) === FALSE ) ) {
					
					$pass = false;
					break;
				}
			}
		}
		return $pass;
	}
	
	public function check_owner( $path = null, $exclude_public = false ) {
		
		if( $path === null ) {
			
			$path = $this->path;
		}
		global $sql;
		$query = "SELECT owner FROM projects WHERE path=?";
		$bind_variables = array( $path );
		$result = $sql->query( $query, $bind_variables, array(), "fetch" );
		$return = false;
		
		if( ! empty( $result ) ) {
			
			$owner = $result["owner"];
			if( $exclude_public ) {
				
				if( $owner == $_SESSION["user_id"] ) {
					
					$return = true;
				}
			} else {
				
				if( $owner == $_SESSION["user_id"] || $owner == 'nobody' ) {
					
					$return = true;
				}
			}
		}
		return( $return );
	}
	
	public function get_access( $project_id = null ) {
		
		global $sql;
		$query = "SELECT * FROM access WHERE project=?";
		$bind_variables = array( $project_id );
		$return = $sql->query( $query, $bind_variables, array() );
		return( $return );
	}
	
	public function get_owner( $path = null ) {
		
		if( $path === null ) {
			
			$path = $this->path;
		}
		global $sql;
		$query = "SELECT owner FROM projects WHERE path=?";
		$bind_variables = array( $path );
		$return = $sql->query( $query, $bind_variables, array(), "fetch" );
		
		if( ! empty( $return ) ) {
			
			$return = $return["owner"];
		} else {
			
			$return = formatJSEND( "error", "Error fetching project info." );
		}
		
		return( $return );
	}
	
	public function get_project( $project = null ) {
		
		if( $project === null ) {
			
			$project = $this->path;
		}
		
		global $sql;
		$query = "
			SELECT * FROM projects
			WHERE path = ?
			AND (
				owner=?
				OR owner=-1
				OR id IN ( SELECT project FROM access WHERE user = ? )
			) ORDER BY name;";
		$bind_variables = array( $project, $_SESSION["user_id"], $_SESSION["user_id"] );
		//$query = "SELECT * FROM projects WHERE path=? AND ( owner=? OR owner='nobody' ) ORDER BY name;";
		//$bind_variables = array( $project, $_SESSION["user"] );
		$return = $sql->query( $query, $bind_variables, array(), "fetch" );
		
		if( ! empty( $return ) ) {
			
		} else {
			
			$return = formatJSEND( "error", "No projects found." );
		}
		
		return( $return );
	}
	
	public function get_all_projects() {
		
		if( is_admin() ) {
			
			global $sql;
			$query = "SELECT * FROM projects";
			$bind_variables = array();
			$return = $sql->query( $query, $bind_variables, array() );
			
			if( empty( $return ) ) {
				
				$return = formatJSEND( "error", "No projects found." );
			}
		} else {
			
			$return = formatJSEND( "error", "Only admins are allowed to view all projects." );
		}
		return( $return );
	}
	
	public function get_projects() {
		
		global $sql;
		$query = "
			SELECT * FROM projects
			WHERE owner=?
			OR owner=-1
			OR id IN ( SELECT project FROM access WHERE user = ? );";
		$bind_variables = array( $_SESSION["user_id"], $_SESSION["user_id"] );
		$return = $sql->query( $query, $bind_variables, array() );
		return( $return );
	}
	
	public function remove_user() {
		
		global $sql;
		
		$user_id = get_user_id( $this->user );
		
		if( $user_id === false ) {
			
			return formatJSEND( "error", "Error fetching user information." );
		}
		
		$query = "DELETE FROM access WHERE project=? AND user=?;";
		$bind_variables = array( $this->project_id, $user_id );
		$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
		
		if( $return > 0 ) {
			
			echo( formatJSEND( "success", "Successfully removed {$this->user}." ) );
		} else {
			
			echo( formatJSEND( "error", "{$this->user} is not in the access list." ) );
		}
	}
	
	public function rename_project( $old_name, $new_name, $path ) {
		
		global $sql;
		$query = "SELECT * FROM projects WHERE name=? AND path=? AND ( owner=? OR owner=-1 );";
		$bind_variables = array( $old_name, $path, $_SESSION["user_id"] );
		$return = $sql->query( $query, $bind_variables, array() );
		$pass = false;
		
		if( ! empty( $return ) ) {
			
			$query = "UPDATE projects SET name=? WHERE name=? AND path=? AND ( owner=? OR owner=-1 );";
			$bind_variables = array( $new_name, $old_name, $path, $_SESSION["user_id"] );
			$return = $sql->query( $query, $bind_variables, 0, "rowCount");
			
			if( $return > 0 ) {
				
				echo( formatJSEND( "success", "Renamed  " . htmlentities( $old_name ) . " to " . htmlentities( $new_name ) ) );
				$pass = true;
			} else {
				
				exit( formatJSEND( "error", "Error renaming project." ) );
			}
		} else {
			
			echo( formatJSEND( "error", "Error renaming project, could not find specified project." ) );
		}
		
		return $pass;
	}
	
	//////////////////////////////////////////////////////////////////
	// OLD METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Get First (Default, none selected)
	//////////////////////////////////////////////////////////////////
	
	public function GetFirst() {
		
		if( ! is_array( $this->projects ) || empty( $this->projects ) ) {
			
			return null;
		}
		
		$this->name = $this->projects[0]['name'];
		$this->path = $this->projects[0]['path'];
		
		// Set Sessions
		$_SESSION['project'] = $this->path;
		return $this->projects[0];
	}
	
	//////////////////////////////////////////////////////////////////
	// Get Name From Path
	//////////////////////////////////////////////////////////////////
	
	public function GetName() {
		
		foreach ( $this->projects as $project => $data ) {
			
			if ( $data['path'] == $this->path ) {
				
				$this->name = $data['name'];
			}
		}
		return $this->name;
	}
	
	//////////////////////////////////////////////////////////////////
	// Open Project
	//////////////////////////////////////////////////////////////////
	
	public function Open() {
		
		global $sql;
		$query = "
			SELECT * FROM projects
			WHERE path = ? 
			AND (
				owner=?
				OR owner=-1
				OR id IN ( SELECT project FROM access WHERE user = ? )
			) ORDER BY name LIMIT 1;";
		$bind_variables = array( $this->path, $_SESSION["user_id"], $_SESSION["user_id"] );
		$return = $sql->query( $query, $bind_variables, array(), "fetch" );
		
		if( ! empty( $return ) ) {
			
			$query = "UPDATE users SET project=? WHERE username=?;";
			$bind_variables = array( $return["id"], $_SESSION["user"] );
			$sql->query( $query, $bind_variables, 0, "rowCount" );
			$this->name = $return['name'];
			$_SESSION['project'] = $return['path'];
			$_SESSION['project_id'] = $return['id'];
			
			echo formatJSEND( "success", array( "name" => $this->name, "path" => $this->path ) );
		} else {
			
			echo formatJSEND( "error", "Error Opening Project" );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Create
	//////////////////////////////////////////////////////////////////
	
	public function Create() {
		
		if ( $this->name != '' && $this->path != '' ) {
			
			$this->path = $this->cleanPath();
			$this->name = htmlspecialchars( $this->name );
			if ( ! $this->isAbsPath( $this->path ) ) {
				
				$this->path = $this->SanitizePath();
			}
			if ( $this->path != '' ) {
				
				$user_path = WORKSPACE . '/' . preg_replace( '/[^\w-]/', '', strtolower( $_SESSION["user"] ) );
				
				if( ! $this->isAbsPath( $this->path ) ) {
					
					$this->path =  $_SESSION["user"] . '/' . $this->path;
				}
				
				$pass = $this->check_duplicate( $this->path );
				if ( $pass ) {
					
					if( ! is_dir( $user_path ) ) {
						
						mkdir( $user_path, 0755, true );
					}
					
					if ( ! $this->isAbsPath( $this->path ) ) {
						
						if( ! is_dir( WORKSPACE . '/' . $this->path ) ) {
							
							mkdir( WORKSPACE . '/' . $this->path );
						}
					} else {
						
						if( ! is_admin() ) {
							
							die( formatJSEND( "error", "Absolute Paths are only allowed for admins" ) );
						}
						
						if ( defined( 'WHITEPATHS' ) ) {
							
							$allowed = false;
							foreach ( explode( ",", WHITEPATHS ) as $whitepath ) {
								
								if ( strpos( $this->path, $whitepath ) === 0 ) {
									
									$allowed = true;
								}
							}
							if ( ! $allowed ) {
								
								die( formatJSEND( "error", "Absolute Path Only Allowed for " . WHITEPATHS ) );
							}
						}
						if ( ! file_exists( $this->path ) ) {
							
							if ( ! mkdir( $this->path . '/', 0755, true ) ) {
								
								die( formatJSEND( "error", "Unable to create Absolute Path" ) );
							}
						} else {
							
							if ( ! is_writable( $this->path ) || ! is_readable( $this->path ) ) {
								
								die( formatJSEND( "error", "No Read/Write Permission" ) );
							}
						}
					}
					$this->projects[] = array( "name" => $this->name, "path" => $this->path );
					$this->add_project( $this->name, $this->path );
					
					// Pull from Git Repo?
					if ( $this->gitrepo && filter_var( $this->gitrepo, FILTER_VALIDATE_URL ) !== false ) {
						
						$this->gitbranch = $this->SanitizeGitBranch();
						if ( ! $this->isAbsPath( $this->path ) ) {
							
							$this->command_exec = "cd " . escapeshellarg( WORKSPACE . '/' . $this->path ) . " && git init && git remote add origin " . escapeshellarg( $this->gitrepo ) . " && git pull origin " . escapeshellarg( $this->gitbranch );
						} else {
							
							$this->command_exec = "cd " . escapeshellarg( $this->path ) . " && git init && git remote add origin " . escapeshellarg( $this->gitrepo ) . " && git pull origin " . escapeshellarg( $this->gitbranch );
						}
						$this->ExecuteCMD();
					}
					
					exit( formatJSEND( "success", array( "name" => $this->name, "path" => $this->path ) ) );
				} else {
					
					exit( formatJSEND( "error", "A Project With the Same Name or Path Exists" ) );
				}
			} else {
				
				exit( formatJSEND( "error", "Project Name/Folder not allowed" ) );
			}
		} else {
			
			exit( formatJSEND( "error", "Project Name/Folder is empty" ) );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Sanitize GitBranch
	//////////////////////////////////////////////////////////////////
	
	public function SanitizeGitBranch() {
		
		$sanitized = str_replace( array( "..", chr(40), chr(177), "~", "^", ":", "?", "*", "[", "@{", "\\" ), array( "" ), $this->gitbranch );
		return $sanitized;
	}
	
	//////////////////////////////////////////////////////////////////
	// Rename
	//////////////////////////////////////////////////////////////////
	
	public function Rename() {
		
		$revised_array = array();
		foreach ( $this->projects as $project => $data ) {
			
			if ( $data['path'] != $this->path ) {
				
				$revised_array[] = array( "name" => $data['name'], "path" => $data['path'] );
			} else {
				
				$rename = $this->rename_project( $data['name'], $_GET['project_name'], $data['path'] );
			}
		}
		
		$revised_array[] = $this->projects[] = array( "name" => $_GET['project_name'], "path" => $this->path );
	}
	
	//////////////////////////////////////////////////////////////////
	// Delete Project
	//////////////////////////////////////////////////////////////////
	
	public function Delete() {
		
		if( Permissions::has_owner( $this->path ) ) {
			
			global $sql;
			$query = "DELETE FROM projects WHERE path=?";
			$bind_variables = array( $this->path );
			$return = $sql->query( $query, $bind_variables, 0, "rowCount" );
			
			if( $return > 0 ) {
				
				exit( formatJSEND( "success", "Successfully deleted project." ) );
			} else {
				
				exit( formatJSEND( "error", "Error deleting project" ) );
			}
		} else {
			
			exit( formatJSEND( "error", "You do not have permission to delete this project" ) );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Sanitize Path
	//////////////////////////////////////////////////////////////////
	
	public function SanitizePath() {
		
		$sanitized = str_replace( " ", "_", $this->path );
		return preg_replace( '/[^\w-]/', '', strtolower( $sanitized ) );
	}
	
	//////////////////////////////////////////////////////////////////
	// Clean Path
	//////////////////////////////////////////////////////////////////
	
	public function cleanPath() {
		
		// prevent Poison Null Byte injections
		$path = str_replace( chr( 0 ), '', $this->path );
		
		// prevent go out of the workspace
		while( strpos( $path, '../' ) !== false ) {
			
			$path = str_replace( '../', '', $path );
		}
		
		return $path;
	}
	
	//////////////////////////////////////////////////////////////////
	// Execute Command
	//////////////////////////////////////////////////////////////////
	
	public function ExecuteCMD() {
		
		if ( function_exists( 'system' ) ) {
			
			ob_start();
			system( $this->command_exec );
			ob_end_clean();
		}  elseif( function_exists( 'passthru' ) ) {
			
			//passthru
			ob_start();
			passthru($this->command_exec);
			ob_end_clean();
		}  elseif ( function_exists( 'exec' ) ) {
			
			//exec
			exec( $this->command_exec, $this->output );
		} elseif ( function_exists( 'shell_exec' ) ) {
			
			//shell_exec
			shell_exec( $this->command_exec );
		}
	}
}