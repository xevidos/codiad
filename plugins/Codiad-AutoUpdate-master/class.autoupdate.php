<?php

/*
*  Copyright (c) Codiad & daeks, distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

require_once('../../common.php');

class AutoUpdate extends Common {

    //////////////////////////////////////////////////////////////////
    // PROPERTIES
    //////////////////////////////////////////////////////////////////

    public $remote = "";
    public $commits = "";
    public $archive = "";
    public $type = "";

    //////////////////////////////////////////////////////////////////
    // METHODS
    //////////////////////////////////////////////////////////////////

    // -----------------------------||----------------------------- //

    //////////////////////////////////////////////////////////////////
    // Construct
    //////////////////////////////////////////////////////////////////

    public function __construct(){
        ini_set("user_agent" , "Codiad");
        $this->remote = "https://codiad.telaaedifex.com/update/?v={VER}&o={OS}&p={PHP}&w={WEB}&a={ACT}";
        $this->commits = "https://gitlab.telaaedifex.com/api/v4/projects/3/repository/commits/";
        $this->archive = "https://gitlab.telaaedifex.com/xevidos/codiad/-/archive/master/codiad-master.zip";
        $this->type = "";
    }

    //////////////////////////////////////////////////////////////////
    // Set Initial Version
    //////////////////////////////////////////////////////////////////

    public function Init() {
        $version = array();
        if(!file_exists(DATA ."/version.php")) {
            if(file_exists(BASE_PATH."/.git/HEAD")) {
                $remote = $this->getRemoteVersion("install_git", $this->type);
                $local = $this->getLocalVersion();
                $version[] = array("version"=>$local[0]['version'],"time"=>time(),"optout"=>"true","name"=>"");
                saveJSON('version.php',$version);
            } else {
                $remote = $this->getRemoteVersion("install_man", $this->type);
                $version[] = array("version"=>$remote[0]["commit"]["sha"],"time"=>time(),"optout"=>"true","name"=>"");
                saveJSON('version.php',$version);
            }
        } else {
            $local = $this->getLocalVersion();
                    
            if(file_exists(BASE_PATH."/.git/HEAD")) {
                $current = getJSON('version.php');
                if($local[0]['version'] != $current[0]['version']) {
                    $remote = $this->getRemoteVersion("update_git", $this->type, $local[0]['version']);
                    $version[] = array("version"=>$local[0]['version'],"time"=>time(),"optout"=>"true","name"=>"");
                    saveJSON('version.php',$version);
                }
            } else {
              if($local[0]['version'] == '' && $local[0]['name'] == $_SESSION['user']) {
                  $remote = $this->getRemoteVersion("update_man", $this->type, $local[0]['version']);
                  $version[] = array("version"=>$remote[0]["commit"]["sha"],"time"=>time(),"optout"=>"true","name"=>$_SESSION['user']);
                  saveJSON('version.php',$version);
              }
            }
            
            $local = $this->getLocalVersion();
            if(!isset($local[0]['optout'])) {
                $remote = $this->getRemoteVersion("optout", $this->type, $local[0]['version']);
                $this->OptOut();
            }   
        }
        
        if(!file_exists(DATA."/config/".get_called_class().".php")) {
          mkdir(DATA."/config");
          $settings = array("type"=>"stable");
          saveJSON("/config/".get_called_class().".php",$settings);
        }
    }

    //////////////////////////////////////////////////////////////////
    // Clear Version
    //////////////////////////////////////////////////////////////////

    public function Clear() {
        $version[] = array("version"=>"","time"=>time(),"optout"=>"true","name"=>$_SESSION['user']);
        saveJSON('version.php',$version);
    }
    
    //////////////////////////////////////////////////////////////////
    // Clear Version
    //////////////////////////////////////////////////////////////////

    public function OptOut() {
        $current = getJSON('version.php');
        $version[] = array("version"=>$current[0]['version'],"time"=>$current[0]['time'],"optout"=>"true","name"=>$current[0]['name']);
        saveJSON('version.php',$version);
    }

    //////////////////////////////////////////////////////////////////
    // Check Version
    //////////////////////////////////////////////////////////////////

    public function Check() {
    
        if($this->type == 'undefined' || $this->type == '') {
            $data = getJSON("/config/".get_called_class().".php");
            $this->type = $data['type'];
        }
    
        $local = $this->getLocalVersion();
        $remote = $this->getRemoteVersion("check", $this->type, $local[0]['version']);
        
        $settings = array("type"=>$this->type);
        saveJSON("/config/".get_called_class().".php",$settings);
        
        $nightly = true;
        $archive = Common::getConstant('ARCHIVEURL', $this->archive);
        $latestversion = '';
        $latestname = '';
        
        if(file_exists(BASE_PATH."/.git/FETCH_HEAD")) {
            $autoupdate = '-1';
        } else {
            if(is_writeable(BASE_PATH) && is_writeable(COMPONENTS) && is_writeable(THEMES)) {
                if(extension_loaded('zip') && extension_loaded('openssl') && ini_get('allow_url_fopen') == 1) {
                    $autoupdate = '1';
                } else {
                    $autoupdate = '-1';
                }
            } else {
                $autoupdate = '0';
            }
        }
        
        $local[0]['tag'] = $local[0]['version'];
        
        foreach($remote as $tag) {
            if($latestversion == '') {
                if($tag['name'] != 'latest') {
                    $latestname = $tag["name"];
                } else {
                  $latestname = 'Latest Commit from Repository';
                }
                $latestversion = $tag["commit"]["sha"];
                $archive = $tag["zipball_url"];
            }
            if($local[0]['version'] == $tag["commit"]["sha"]) {
                if($tag['name'] != 'latest') {
                  $local[0]['tag'] = $tag["name"];
                }
                $nightly = false;
                break;
            }
        }
                
        $search = array("\r\n", "\n", "\r");
        $replace = array(" ", " ", " ");

        $message = '';
        $merge = '';
        $commits = json_decode(file_get_contents(Common::getConstant('COMMITURL', $this->commits)),true);
        foreach($commits as $commit) {
            if($local[0]['version'] != $commit["sha"]) {
                if(strpos($commit["commit"]["message"],"Merge") === false) {
                    $message .= '- '.str_replace($search,$replace,$commit["commit"]["message"]).'<br/>';
                } else {
                    $merge .= '- '.str_replace($search,$replace,$commit["commit"]["message"]).'<br/>';
                }
            } else {
                break;
            }
        }

        if($message == '') {
            $message = $merge;
        }

        return "[".formatJSEND("success",array("currentname"=>$local[0]['tag'], "currentversion"=>$local[0]['version'],"remoteversion"=>$latestversion,"remotename"=>$latestname,"message"=>$message,"archive"=>$archive,"nightly"=>$nightly,"autoupdate"=>$autoupdate,"name"=>$local[0]['name']))."]";
    }
        
    //////////////////////////////////////////////////////////////////
    // Get Local Version
    //////////////////////////////////////////////////////////////////
    
    public function getLocalVersion() {
        if(file_exists(BASE_PATH."/.git/HEAD")) {
            $tmp = file_get_contents(BASE_PATH."/.git/HEAD");
            if (strpos($tmp,"ref:") === false) {
                $data[0]['version'] = trim($tmp);
            } else {
                $data[0]['version'] = trim(file_get_contents(BASE_PATH."/.git/".trim(str_replace('ref: ', '', $tmp))));
            }
            $data[0]['name'] = "";
        } else {
            $data = getJSON('version.php');
        }
        return $data;
    }
        
    //////////////////////////////////////////////////////////////////
    // Get Remote Version
    //////////////////////////////////////////////////////////////////
        
    public function getRemoteVersion($action, $type, $localversion = "") {
        $remoteurl = Common::getConstant('UPDATEURL', $this->remote);
        $remoteurl = str_replace("{OS}", PHP_OS, $remoteurl);
        $remoteurl = str_replace("{PHP}", phpversion(), $remoteurl);
        $remoteurl = str_replace("{VER}", $localversion, $remoteurl);
        $remoteurl = str_replace("{WEB}", urlencode($_SERVER['SERVER_SOFTWARE']), $remoteurl);
        $remoteurl = str_replace("{ACT}", $action, $remoteurl);
        
        if($type == 'latest') {
          $remoteurl = $remoteurl.'&l';
        }
        
        return json_decode(file_get_contents($remoteurl),true);
    }
    
    //////////////////////////////////////////////////////////////////
    // Download Version
    //////////////////////////////////////////////////////////////////
    
    public function Download() {
        if(file_exists('../../'.$this->commit.'.zip')) {
            unlink('../../'.$this->commit.'.zip');
        }
        file_put_contents('../../'.$this->commit.'.zip', fopen(str_replace('master', $this->commit, $this->archive), 'r'));

        $data = '<?php
  
$commit = "'.$this->commit.'";

function delTree($dir) { 
 $files = array_diff(scandir($dir), array(".","..")); 
  foreach ($files as $file) { 
    (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
  } 
  return rmdir($dir); 
} 

function cpy($source, $dest, $ign, $frc){
    if(is_dir($source)) {
        $dir_handle=opendir($source);
        while($file=readdir($dir_handle)){
            if(!in_array($file, array(".",".."))) {
              if(!in_array($file, $ign) || in_array($file, $frc)){
                  if(is_dir($source."/".$file)){
                      if(!file_exists($dest."/".$file)) { @mkdir($dest."/".$file); }
                      cpy($source."/".$file, $dest."/".$file, $ign, $frc);
                      rmdir($source."/".$file);
                  } else {
                      copy($source."/".$file, $dest."/".$file);
                      unlink($source."/".$file);
                  }
              } else {
                if(array_key_exists($file, $frc)) {
                  if(is_dir($source."/".$file)){
                    if(!file_exists($dest."/".$file)) { @mkdir($dest."/".$file); }
                    cpy($source."/".$file."/".$frc[$file], $dest."/".$file."/".$frc[$file], $ign, $frc);
                  } else {
                      copy($source."/".$file, $dest."/".$file);
                      unlink($source."/".$file);
                  }
                }
              }
            } 
        }
        closedir($dir_handle);
    } else {
        copy($source, $dest);
        unlink($source);
    }
}

// Getting current codiad path
$path = rtrim(str_replace($commit.".php", "", $_SERVER["SCRIPT_FILENAME"]),"/");
$ignore = array(".git", "config.json", "data", "workspace", "plugins", "themes", "backup", "config.php", $commit.".php",$commit.".zip", "Codiad-".$commit);
$force = array("themes" => "default", "themes" => "README.md");

$zip = new ZipArchive;
$res = $zip->open($path."/".$commit.".zip");
// open downloaded archive
if ($res === TRUE) {
  // extract archive
  if($zip->extractTo($path) === true) {
    // delete old files except some directories and files
    if(!file_exists($path."/backup")) { mkdir($path."/backup"); }
    cpy($path, $path."/backup", $ignore, $force);
    
    // move extracted files to path
    cpy($path."/Codiad-".$commit, $path, array(), array());

    // store current commit to version.json
    $version = array();
    $version[] = array("version"=>$commit,"optout"=>"true","name"=>"'.$_SESSION['user'].'","time"=>"'.time().'");
    file_put_contents($path."/data/version.php", "<?php/*|" . json_encode($version) . "|*/?>");  

    // cleanup and restart codiad
    @$zip->close();
    delTree($path."/backup");
    rmdir($path."/Codiad-".$commit);
    unlink($path."/".$commit.".zip");
    unlink($path."/".$commit.".php");
    header("Location: ".str_replace($commit.".php","",$_SERVER["SCRIPT_NAME"]));
  } else {
    echo "Unable to extract ".$path."/".$commit.".zip to path ".$path;
  }
  $zip->close();
} else {
    echo "Unable to open ".$path."/".$commit.".zip";
}

?>';
        $write = fopen('../../'.$this->commit.'.php', 'w') or die("can't open file");
        fwrite($write, $data);
        fclose($write);
        
        @session_unset(); @session_destroy(); session_start();
        echo formatJSEND("success",null);
    }

}
