<?php

/*
*  Copyright (c) Codiad & daeks, distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once('../../common.php');

class Macro extends Common {

    //////////////////////////////////////////////////////////////////
    // PROPERTIES
    //////////////////////////////////////////////////////////////////

    public $id = 0;
    public $path = '';

    //////////////////////////////////////////////////////////////////
    // METHODS
    //////////////////////////////////////////////////////////////////

    // -----------------------------||----------------------------- //

    //////////////////////////////////////////////////////////////////
    // Construct
    //////////////////////////////////////////////////////////////////

    public function __construct(){
        if(!file_exists(DATA."/config/".get_called_class().".php")) {
          @mkdir(DATA."/config");
          saveJSON("/config/".get_called_class().".php", array());
        }
    }

    //////////////////////////////////////////////////////////////////
    // Load Contextmenu Macros
    //////////////////////////////////////////////////////////////////

    public function Load() {
        return getJSON("/config/".get_called_class().".php");
    }

    //////////////////////////////////////////////////////////////////
    // Save Contextmenu Macros
    //////////////////////////////////////////////////////////////////

    public function Save() {
      $data = array();
      if(isset($_GET['n'])) {
        foreach ($_GET['n'] as $key => $name){
          $tmp['n'] = trim($name);
          $tmp['d'] = trim($_GET["d"][$key]);
          $tmp['a'] = trim($_GET["a"][$key]);
          $tmp['t'] = trim($_GET["t"][$key]);
          $tmp['i'] = trim($_GET["i"][$key]);
          $tmp['c'] = trim($_GET["c"][$key]);
          
          array_push($data,$tmp);
        }
			}
			saveJSON("/config/".get_called_class().".php", $data);
			echo formatJSEND("success",null);
    }
    
    //////////////////////////////////////////////////////////////////
    // Execute Macro
    //////////////////////////////////////////////////////////////////

    public function Execute() {
        $macrolist = $this->Load();
        $command = $macrolist[$this->id]['c'];
        
        if(!$this->isAbsPath($this->path)) {
          $this->path = WORKSPACE.'/'.$this->path;
        }
        if(is_file($this->path)) {
          $command = str_replace('%FILE%',$this->path,$command);
          $command = str_replace('%FOLDER%',dirname($this->path),$command);
          $command = str_replace('%NAME%',basename($this->path),$command);
        } else {
          $command = str_replace('%FOLDER%',$this->path,$command);
          $command = str_replace('%NAME%',basename($this->path),$command);
        }
        
        shell_exec($command);
        echo formatJSEND("success",null);
    }
    
}
