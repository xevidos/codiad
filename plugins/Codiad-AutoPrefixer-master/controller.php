<?php
/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License. 
 * See http://opensource.org/licenses/MIT for more information.
 * This information must remain intact.
 */

    require_once('../../common.php');
    
    checkSession();
    error_reporting(0);

    switch($_GET['action']) {

        /**
         * Compress a css file.
         *
         * @param {string} path The path of the file to compress
         * @param {string} content Prefixed content
         */
        case 'saveContent':
            if (isset($_GET['path']) && isset($_POST['content'])) {
                $path   = getWorkspacePath($_GET['path']);
                $nFile  = substr($path, 0, strrpos($path, ".css"));
                $nFile  = $nFile . ".pre.css";
                file_put_contents($nFile, $_POST['content']);
                echo '{"status":"success","message":"CSS prefixed!"}';
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;

        /**
         * Get file content
         *
         * @param {string} path The path of the file
         */
        case 'getContent':
            if (isset($_GET['path'])) {
                $content = file_get_contents(getWorkspacePath($_GET['path']));
                echo json_encode(array("status" => "success", "content" => $content));
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        default:
            echo '{"status":"error","message":"No Type"}';
            break;
    }
    
    function getWorkspacePath($path) {
		//Security check
		if (!Common::checkPath($path)) {
			die('{"status":"error","message":"Invalid path"}');
		}
        if (strpos($path, "/") === 0) {
            //Unix absolute path
            return $path;
        }
        if (strpos($path, ":/") !== false) {
            //Windows absolute path
            return $path;
        }
        if (strpos($path, ":\\") !== false) {
            //Windows absolute path
            return $path;
        }
        return WORKSPACE . "/".$path;
    }
?>