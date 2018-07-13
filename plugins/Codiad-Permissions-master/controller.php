<?php
/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License. 
 * See [root]/license.md for more information. This information must remain intact.
 */
    error_reporting(0);

    require_once('class.util.php');
    require_once('../../common.php');
    checkSession();
    
    switch($_GET['action']) {
        
        case 'changePermission':
            if (isset($_GET['path']) && isset($_GET['mode'])) {
                $path = util::getWorkspacePath($_GET['path']);
                $mode = $_GET['mode'];
                if ($mode[0] != '0') {
                    $mode = '0'.$mode;
                }
                $mode   = intval($mode, 8);
                if (chmod_R($path, $mode)) {
                    echo '{"status":"success","message":"Permissions Changed"}';
                } else {
                    echo '{"status":"error","message":"Failed To Change Permissions"}';
                }
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        default:
            echo '{"status":"error","message":"No Type"}';
            break;
    }
    
    function chmod_R($path, $mode) {
        if (is_dir($path)) {
            if (chmod($path, $mode) == false) {
                return false;
            }
            $files = scandir($path);
            foreach ($files as $file) {
                if($file != '.' && $file != '..') {
                    $fullpath = $path.'/'.$file;
                    if(!chmod_R($fullpath, $mode)) {
                        return false;
                    }
                }
            }
            return true;
        } else {
            return chmod($path, $mode);
        }
    }
?>