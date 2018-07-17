<?php
/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License. 
 * See http://opensource.org/licenses/MIT for more information. 
 * This information must remain intact.
 */
    include_once('../../common.php');

    class settings {
        
        public function load() {
            $this->existDir();
            return json_encode(getJSON($this->getFileName(), "config"));
        }
        
        public function open() {
            $this->existDir();
            $msg            = array();
            $msg['content'] = json_encode(getJSON($this->getFileName(), "config"));
            $msg['name']    = basename($this->getFileName(), ".php").".json";
            $msg['mtime']   = filemtime($this->getFilePath());
            if ($msg['content'] !== false) {
                $msg['status'] = "success";
            } else {
                $msg['status'] = "error";
                $msg['message'] = "Failed to open file!";
            }
            return json_encode($msg);
        }
        
        public function save($content) {
            $this->existDir();
            saveJSON($this->getFileName(), json_decode($content), "config");
            return '{"status":"success","message":"Settings saved!"}';
        }
        
        public function existDir() {
            if(!file_exists($this->getFilePath())) {
                saveJSON($this->getFileName(), array(), "config");
            }
        }
        
        public function getFilePath() {
            return DATA."/config/".get_called_class().".".$_SESSION['user'].".php";
        }
        
        public function getFileName() {
            return basename($this->getFilePath());
        }
        
        static public function getWorkspacePath($path) {
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
            return "../../workspace/".$path;
        }
    }
?>