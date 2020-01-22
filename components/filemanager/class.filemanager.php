<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), Telaaedifex distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once( '../../lib/diff_match_patch.php' );
require_once( '../../common.php' );
require_once( __DIR__ . '/class.archive.php' );

class Filemanager extends Common {
	
	const PATH_REGEX = '/[^\w\-\._@]/';
	
	//////////////////////////////////////////////////////////////////
	// PROPERTIES
	//////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Construct
	//////////////////////////////////////////////////////////////////
	
	public function __construct() {}
	
	//////////////////////////////////////////////////////////////////
	// Clean a path
	//////////////////////////////////////////////////////////////////
	
	public static function cleanPath( $path ) {
		
		// Prevent going out of the workspace
		while ( strpos( $path, '../' ) !== false ) {
			
			$path = str_replace( '../', '', $path );
		}
		
		if( self::isAbsPath( $path ) ) {
			
			$full_path = $path;
		} else {
			
			$full_path = WORKSPACE . "/" . $path;
		}
		
		/**
		 * If a file with an invalid character exists and the user is
		 * trying to rename or delete it, allow the actual file name.
		 */
		
		$invalid_characters = preg_match( '/[^A-Za-z0-9\-\._@\/\(\) ]/', $path );
		
		if( $invalid_characters && ! ( $_GET['action'] == "modify" || $_GET['action'] == "delete" ) ) {
			
			exit( formatJSEND( "error", "Error, the filename contains invalid characters, please either rename or delete it." ) );
		} elseif( $invalid_characters && ( $_GET['action'] == "modify" || $_GET['action'] == "delete" ) ) {
		} else {
			
			$path = preg_replace( '/[^A-Za-z0-9\-\._@\/\(\) ]/', '', $path );
		}
		return $path;
	}
	
	//////////////////////////////////////////////////////////////////
	// CREATE (Creates a new file or directory)
	//////////////////////////////////////////////////////////////////
	
	public function create( $path, $type, $content = "" ) {
		
		$response = array(
			"status" => "none",
			"message" => null,
		);
		$path = self::formatPath( $path );
		
		if( Permissions::has_create( $path ) ) {
			
			// Create file
			if( $type == "file" ) {
				
				if ( ! file_exists( $path ) ) {
					
					if ( $file = fopen( $path, 'w' ) ) {
						
						// Write content
						if ( $content ) {
							
							fwrite( $file, $content );
						}
						fclose( $file );
						
						$response["status"] = "success";
						$response["mtime"] = filemtime( $path );
					} else {
						
						$response["status"] = "error";
						$response["message"] = "Cannot Create File";
					}
				} else {
					
					$response["status"] = "error";
					$response["message"] = "File Already Exists";
				}
			} elseif( $type == "directory" ) {
				
				if ( ! is_dir( $path ) ) {
					
					mkdir( $path );
					$response["status"] = "success";
				} else {
					
					$response["status"] = "error";
					$response["message"] = "Directory Already Exists";
				}
			}
		} else {
			
			$response["status"] = "error";
			$response["message"] = "You do not have permission to create files in this project";
		}
		return $response;
	}
	
	//////////////////////////////////////////////////////////////////
	// DELETE (Deletes a file or directory (+contents or only contents))
	//////////////////////////////////////////////////////////////////
	
	public function delete( $path, $follow, $keep_parent = false ) {
		
		$response = array(
			"status" => "none",
			"message" => null,
		);
		
		if( ! Common::checkPath( $path ) ) {
			
			$response["status"] = "error";
			$response["message"] = "You do not have access to delete this file";
		} else {
			
			$path = self::formatPath( $path );
			if ( file_exists( $path ) ) {
				
				self::recursive_delete( $path, $follow, $keep_parent );
				$response["status"] = "success";
			} else {
				
				$response["status"] = "error";
				$response["message"] = "Path Does Not Exist ";
			}
		}
		return $response;
	}
	
	//////////////////////////////////////////////////////////////////
	// DUPLICATE (Creates a duplicate of the object - (cut/copy/paste)
	//////////////////////////////////////////////////////////////////
	
	public function duplicate( $source, $destination ) {
		
		$response = array(
			"status" => "none",
			"message" => null,
		);
		
		$source = self::formatPath( $source );
		$destination = self::formatPath( $destination );
		$new_destination = $destination;
		$path_parts = pathinfo( $destination );
		$i = 1;
		
		do {
			
			if( is_dir( $new_destination ) ) {
				
				$new_destination = rtrim( $destination, "/" ) . " $i/";
			} elseif( is_file( $new_destination ) ) {
				
				if( isset( $path_parts["extension"] ) ) {
					
					$new_destination = str_replace( ".{$path_parts["extension"]}", " {$i}.{$path_parts["extension"]}", $destination );
				} else {
					
					$new_destination = $destination . " $i";
				}
			}
			$i++;
		} while( ( is_file( $new_destination ) || is_dir( $new_destination ) ) );
		
		if( file_exists( $source ) ) {
			
			if( is_file( $source ) ) {
				
				copy( $source, $new_destination );
				$response["status"] = "success";
			} else {
				
				self::recursive_copy( $source, $new_destination );
				$response["status"] = "success";
			}
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Invalid Source";
		}
		return $response;
	}
	
	public function find( $path, $query, $options = array() ) {
		
		$response = array(
			"status" => "none",
			"message" => null,
		);
		$current_path = getcwd();
		$path = self::formatPath( $path );
		
		if ( ! function_exists( 'shell_exec' ) ) {
			
			$response["status"] = "error";
			$response["message"] = "Shell_exec() Command Not Enabled.";
		} else {
			
			chdir( $path );
			$input = str_replace( '"', '', $query );
			$cmd = 'find -L ';
			$strategy = '';
			if ( ! empty( $options ) && isset( $options["strategy"] ) ) {
				
				$strategy = $options["strategy"];
			}
			
			switch ( $strategy ) {
				
				case 'substring':
					
					$cmd = "$cmd -iname " . escapeshellarg( '*' . $input . '*' );
				break;
				case 'regexp':
					
					$cmd = "$cmd -regex " . escapeshellarg( $input );
				break;
				case 'left_prefix':
				default:
					$cmd = "$cmd -iname " . escapeshellarg( $input . '*');
				break;
			}
			$cmd = "$cmd  -printf \"%h/%f %y\n\"";
			$output = shell_exec( $cmd );
			$file_arr = explode( "\n", $output );
			$output_arr = array();
			
			error_reporting( 0 );
			
			foreach ( $file_arr as $i => $fentry ) {
				
				$farr = explode( " ", $fentry );
				$fname = trim( $farr[0] );
				if ( $farr[1] == 'f' ) {
					
					$ftype = 'file';
				} else {
					
					$ftype = 'directory';
				}
				if ( strlen( $fname ) != 0 ) {
					
					$fname = $path . substr( $fname, 2 );
					$f = array( 'path' => $fname, 'type' => $ftype );
					array_push( $output_arr, $f );
				}
			}
			
			if ( count( $output_arr ) == 0 ) {
				
				$response["status"] = "error";
				$response["message"] = "No Results Returned";
			} else {
				$response["status"] = "success";
				$response["index"] = $output_arr;
			}
		}
		return $response;
	}
	
	public static function formatPath( $path ) {
		
		if( self::isAbsPath( $path ) ) {
			
			$path = self::cleanPath( $path );
		} else {
			
			$path = WORKSPACE . "/" . self::cleanPath( $path );
		}
		
		if( is_dir( $path ) ) {
			
			$path = rtrim( $path, '/' ) . '/';
		}
		return( $path );
	}
	
	//////////////////////////////////////////////////////////////////
	// INDEX (Returns list of files and directories)
	//////////////////////////////////////////////////////////////////
	
	public function index( $path ) {
		
		$response = array(
			"status" => "none",
			"message" => null,
		);
		$relative_path = rtrim( self::cleanPath( $path ), '/' ) . '/';
		$path = self::formatPath( $path );
		
		if( file_exists( $path ) ) {
			
			$index = array();
			
			if( is_dir( $path ) ) {
				
				$files = $this->index_path( $path );
				
				$response["status"] = "success";
				$response["data"] = array( "index" => $files );
			} else {
				
				$response["status"] = "error";
				$response["message"] = "Not A Directory";
			}
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Path Does Not Exist";
		}
		return $response;
	}
	
	function index_path( $path ) {
		
		$paths = array();
		
		if( is_dir( $path ) && $handle = opendir( $path ) ) {
			
			while( false !== ( $f = readdir( $handle ) ) ) {
				
				if ( "$f" != '.' && "$f" != '..' ) {
					
					$p = "$path" . DIRECTORY_SEPARATOR . "$f";
					$p = str_replace( "//", "/", $p );
					$rp = realpath( $p );
					$path_info = pathinfo( $p );
					
					if( is_dir( $p ) ) {
						
						$children = $this->is_empty( $p ) ? null : array();
						
						$paths[] = array(
							
							"basename" => $path_info["basename"],
							"children" => $children,
							"dirname" => str_replace( WORKSPACE . "/", "", $p ),
							"extension" => null,
							"filename" => $path_info["filename"],
							"full_dirname" => $path_info["dirname"],
							"full_path" => $p,
							"path" => str_replace( WORKSPACE . "/", "", $p ),
							"type" => "directory",
						);
					} else {
						
						$paths[] = array(
							"basename" => $path_info["basename"],
							"dirname" => str_replace( WORKSPACE . "/", "", $p ),
							"extension" => isset( $path_info["extension"] ) ? $path_info["extension"] : null,
							"filename" => $path_info["filename"],
							"path" => str_replace( WORKSPACE . "/", "", $p ),
							"type" => "file",
						);
					}
				}
			}
		}
		
		closedir( $handle );
		usort( $paths, array( $this, "sorter" ) );
		return $paths;
	}
	
	function is_empty( $dir ) {
		
		$pass = true;
		
		if( is_dir( $dir ) ) {
			
			$handle = opendir( $dir );
			while( false !== ( $entry = readdir( $handle ) ) ) {
				
				if( $entry != "." && $entry != ".." ) {
					
					$pass = false;
					break;
				}
			}
			closedir( $handle );
		}
		return $pass;
	}
	
	function sorter( $a, $b ) {
		
		$basename = strnatcmp( $a["basename"], $b["basename"] );
		$type = strnatcmp( $a["type"], $b["type"] );
		$result = 0;
		
		if( $type == 0 ) {
			
			$result = $basename;
		} else {
			
			$result = $type;
		}
		
		return $result;
	}

	
	//////////////////////////////////////////////////////////////////
	// MODIFY (Modifies a file name/contents or directory name)
	//////////////////////////////////////////////////////////////////
	
	public function modify( $path, $content, $patch = false, $mtime = 0 ) {
		
		// Change content
		$response = array(
			"status" => "none",
			"message" => null,
		);
		$path = self::formatPath( $path );
		
		if( $content == ' ' ) {
			
			$content = ''; // Blank out file
		}
		
		if( ! Permissions::has_write( $path ) ) {
			
			$response["status"] = "error";
			$response["message"] = "You do not have access to write to this file.";
			return $response;
		}
		
		if( $patch && ! $mtime ) {
			
			$response["status"] = "error";
			$response["message"] = "invalid mtime parameter not found";
			$response["mtime"] = $mtime;
			return $response;
		}
		
		if( is_file( $path ) ) {
			
			$serverMTime = filemtime( $path );
			$fileContents = file_get_contents( $path );
			
			if( $patch && $mtime != $serverMTime ) {
				
				$response["status"] = "error";
				$response["message"] = "Client is out of sync";
				//DEBUG : file_put_contents($this->path.".conflict", "SERVER MTIME :".$serverMTime.", CLIENT MTIME :".$this->mtime);
				return $response;
			} elseif( strlen( trim( $patch ) ) == 0 && ! $content ) {
				
				// Do nothing if the patch is empty and there is no content
				$response["status"] = "success";
				$response["data"] = array(
					"mtime" => $serverMTime
				);
				return $response;
			}
			
			if( $file = fopen( $path, 'w' ) ) {
				
				if( $patch ) {
					
					$dmp = new diff_match_patch();
					$p = $dmp->patch_apply( $dmp->patch_fromText( $patch ), $fileContents );
					$content = $p[0];
					//DEBUG : file_put_contents($this->path.".orig",$fileContents );
					//DEBUG : file_put_contents($this->path.".patch", $this->patch);
				}
				
				if( fwrite( $file, $content ) === false ) {
					
					$response["status"] = "error";
					$response["message"] = "could not write to file";
				} else {
					
					// Unless stat cache is cleared the pre-cached mtime will be
					// returned instead of new modification time after editing
					// the file.
					clearstatcache();
					$response["status"] = "success";
					$response["data"] = array(
						"mtime" => filemtime( $path )
					);
				}
				fclose( $file );
			} else {
				
				$response["status"] = "error";
				$response["message"] = "Cannot Write to File";
			}
			
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Not A File";
		}
		return $response;
	}
	
	public function move( $path, $new_path ) {
		
		$response = array(
			"status" => "none",
		);
		$path = self::formatPath( $path );
		$new_path = self::formatPath( $new_path );
		
		if ( ! file_exists( $new_path ) ) {
			
			if( rename( $path, $new_path ) ) {
				
				$response["status"] = "success";
			} else {
				
				$response["status"] = "error";
				$response["message"] = "Could Not Rename";
			}
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Path Already Exists";
		}
		return $response;
	}
	
	//////////////////////////////////////////////////////////////////
	// OPEN (Returns the contents of a file)
	//////////////////////////////////////////////////////////////////
	
	public function open( $path ) {
		
		$response = array(
			"status" => "none",
			"message" => null,
		);
		
		$relative_path = self::cleanPath( $path );
		$path = self::formatPath( $path );
		
		if ( is_file( $path ) ) {
			
			$output = file_get_contents( $path );
			
			if ( extension_loaded( 'mbstring' ) ) {
				
				if ( ! mb_check_encoding( $output, 'UTF-8' ) ) {
					
					if ( mb_check_encoding( $output, 'ISO-8859-1' ) ) {
						
						$output = utf8_encode( $output );
					} else {
						
						$output = mb_convert_encoding( $content, 'UTF-8' );
					}
				}
			}
			
			$response["status"] = "success";
			$response["data"] = array(
				"content" => $output,
				"mtime" => filemtime( $path ),
				"read_only" => ( ! Permissions::has_write( $path ) ),
			);
		} else {
			
			$response["status"] = "error";
			$response["message"] = "Error, {$path} is not a file.";
		}
		return $response;
	}
	
	//////////////////////////////////////////////////////////////////
	// OPEN IN BROWSER (Return URL)
	//////////////////////////////////////////////////////////////////
	
	public function openinbrowser( $path ) {
		
		$protocol = ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) || $_SERVER['SERVER_PORT'] == 443 ) ? "https://" : "http://";
		$domainName = $_SERVER['HTTP_HOST'];
		$url = $protocol . WSURL . '/' . self::cleanPath( $path );
		$response = array(
			"status" => "success",
			"data" => rtrim( $url, "/" ),
		);
		return $response;
	}
	
	static function recursive_copy( $source, $destination ) {
		
		$dir = opendir( $source );
		@mkdir( $source );
		
		if( is_dir( $source ) ) {
			
			@mkdir( $destination );
		} else {
			
			return;
		}
		
		while ( false !== ( $file = readdir( $dir ) ) ) {
			
			if ( ( $file != '.' ) && ( $file != '..' ) ) {
				
				if ( is_dir( $source . '/' . $file ) ) {
					
					self::recurse_copy( $source . '/' . $file, $destination . '/' . $file );
				} else {
					
					copy( $source . '/' . $file, $destination . '/' . $file );
				}
			}
		}
		closedir( $dir );
	}
	
	static function recursive_delete( $path, $follow, $keep_parent = false ) {
		
		if ( is_file( $path ) ) {
			
			unlink( $path );
		} else {
			
			$files = array_diff( scandir( $path ), array( '.', '..' ) );
			foreach ( $files as $file ) {
				
				if ( is_link( $path . "/" . $file ) ) {
					
					if ( $follow ) {
						
						self::recursive_delete( $path . "/" . $file, $follow, false);
					}
					unlink( $path . "/" . $file );
				} elseif ( is_dir( $path . "/" . $file ) ) {
					
					self::recursive_delete( $path . "/" . $file, $follow, false );
				} else {
					
					unlink( $path . "/" . $file );
				}
			}
			if( $keep_parent === false ) {
				
				rmdir( $path );
				return;
			} else {
				
				return;
			}
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// SEARCH
	//////////////////////////////////////////////////////////////////
	
	public function search( $path, $query, $options ) {
		
		$response = array(
			"status" => "none",
			"message" => null,
		);
		
		if( ! common::isAbsPath( $path ) ) {
			
			$path = WORKSPACE . "/$path";
		}
		
		if ( ! function_exists( 'shell_exec' ) ) {
			
			$response["status"] = "error";
			$response["message"] = "Shell_exec() Command Not Enabled.";
		} else {
			
			$return = array();
			$input = str_replace( '"', '', $query );
			$cmd = 'find -L ' . escapeshellarg( $path ) . ' -iregex  ' . escapeshellarg( '.*' . $options["filetype"] ) . ' -type f -print0 | xargs -0 grep -i -I -n -R -H ' . escapeshellarg( $input ) . '';
			$output = shell_exec( $cmd );
			$output_arr = explode( "\n", $output );
			foreach ( $output_arr as $line ) {
				
				$data = explode( ":", $line );
				$da = array();
				if ( count( $data ) > 2 ) {
					
					$da['line'] = $data[1];
					$da['file'] = str_replace( $path, '', $data[0] );
					$da['result'] = str_replace( WORKSPACE . '/', '', $data[0] );
					$da['string'] = str_replace( $data[0] . ":" . $data[1] . ':', '', $line );
					$return[] = $da;
				}
			}
			if ( count( $return ) == 0 ) {
				
				$response["status"] = "error";
				$response["message"] = "No Results Returned";
			} else {
				
				$response["status"] = "success";
				$response["data"] = array();
				$response["data"]["index"] = $return;
			}
		}
		return $response;
	}
	
	public function stitch( $path ) {
		
		$response = array(
			"status" => "none",
			"message" => "",
		);
		
		if( ! Permissions::has_write( $path ) ) {
			
			$response["status"] = "error";
			$response["message"] = "You do not have access to write to this file.";
			return $response;
		}
		
		if( ! common::isAbsPath( $path ) ) {
			
			$path = WORKSPACE . "/$path";
		}
		
		$path = $_POST["path"];
		$tmp = DATA . "tmp/$path/";
		$dir = dirname( $path );
		$name = basename( $path );
		$files = scandir( $tmp );
		
		if( ! is_dir( $dir ) ) {
			
			mkdir( $dir, 0755, true );
		}
		
		foreach( $files as $id => $file ) {
			
			if( $file !== "." && $file !== ".." ) {
				
				$data = file_get_contents( $cache_path . $file );
				$handle = fopen( $path, "a" );
				$status = fwrite( $handle, $data );
				fclose( $handle );
				unlink( $cache_path . $file );
			}
		}
		
		$tmp_array = explode( "/", $path );
		$remove = array();
		
		while( count( $tmp_array ) > 0 ) {
			
			$remove[] = DATA . "tmp/" . implode( "/", $tmp_array );
			array_pop( $tmp_array );
		}
		
		foreach( $tmp_array as $id => $i ) {
			
			rmdir( $i );
		}
		return $response;
	}
	
	//////////////////////////////////////////////////////////////////
	// UPLOAD (Handles uploads to the specified directory)
	//////////////////////////////////////////////////////////////////
	
	public function upload( $path, $blob ) {
		
		$response = array(
			"status" => "none",
			"message" => "",
		);
		
		// Check that the path is a directory
		if( ! Permissions::has_write( $path ) ) {
			
			$response["status"] = "error";
			$response["message"] = "You do not have access to write to this file.";
			return $response;
		}
		
		if( ! common::isAbsPath( $path ) ) {
			
			$path = WORKSPACE . "/$path";
		}
		
		$dirname = dirname( $path );
		$name = basename( $path );
		
		$blob = @file_get_contents( $_POST["data"] );
		$path = $_POST["path"];
		$index = $_POST["index"];
		$response = array(
			"status" => "none",
			"message" => "",
		);
		$tmp = DATA . "tmp/";
		
		if( ! is_dir( $tmp . $path ) ) {
			
			mkdir( $tmp . $path, 0755, true );
		}
		
		$handle = fopen( "$tmp$path/$index", "a" );
		$status = fwrite( $handle, $blob );
		fclose( $handle );
		
		if( $status === false ) {
			
			$response["status"] = "error";
			$response["message"] = "File could not be written to.";
		} else {
			
			$response["status"] = "success";
			$response["path"] = $path;
			$response["bytes"] = $status;
			$response["message"] = "$status bytes written to file.";
		}
		return $response;
	}
}
