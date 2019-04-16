<?php

/*
*  Copyright (c) Codiad & daeks (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

class Update {
	
	//////////////////////////////////////////////////////////////////
	// CONSTANTS
	//////////////////////////////////////////////////////////////////
	
	CONST VERSION = "v.2.9.3.5";
	
	//////////////////////////////////////////////////////////////////
	// PROPERTIES
	//////////////////////////////////////////////////////////////////
	
	public $remote = "";
	public $commits = "";
	public $tags = "";
	public $archive = "";
	public $version = "";
	public $protocol = "";
	public $update_file = "";
	public $development_archive = "";
	
	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////
	
	// -----------------------------||----------------------------- //
	
	//////////////////////////////////////////////////////////////////
	// Construct
	//////////////////////////////////////////////////////////////////
	
	public function __construct() {
		ini_set("user_agent", "Codiad");
		
		$this->archive = "https://gitlab.com/xevidos/codiad/-/archive/master/codiad-master.zip";
		$this->commits = "https://gitlab.com/api/v4/projects/8466613/repository/commits/";
		$this->development_archive = "https://gitlab.com/xevidos/codiad/-/archive/development/codiad-development.zip";
		$this->tags = "https://gitlab.com/api/v4/projects/8466613/repository/tags/";
		$this->update_file = "https://gitlab.com/xevidos/codiad/raw/master/components/update/update.php";
		$this->protocol = $this->CheckProtocol();
		
	}
	
	//////////////////////////////////////////////////////////////////
	// Set Initial Version
	//////////////////////////////////////////////////////////////////
	
	public function Init() {
		
		$version = array();
	}
	
	//////////////////////////////////////////////////////////////////
	// Clear Version
	//////////////////////////////////////////////////////////////////
	
	public function Clear() {
		$version[] = array("version"=>"","time"=>time(),"optout"=>"true","name"=>$_SESSION['user']);
		saveJSON('version.php', $version);
	}
	
	//////////////////////////////////////////////////////////////////
	// Clear Version
	//////////////////////////////////////////////////////////////////
	
	public function OptOut() {
		$current = getJSON('version.php');
		$version[] = array("version"=>$current[0]['version'],"time"=>$current[0]['time'],"optout"=>"true","name"=>$current[0]['name']);
		saveJSON('version.php', $version);
	}
	
	//////////////////////////////////////////////////////////////////
	// Check Version
	//////////////////////////////////////////////////////////////////
	
	public function Check() {
		/*
		$local = $this->getLocalVersion();
		$remote = $this->getRemoteVersion("check", $local[0]['version']);
		
		$nightly = true;
		$archive = Common::getConstant('ARCHIVEURL', $this->archive);
		$latest = '';
		
		foreach ($remote as $tag) {
			if ($latest == '') {
				$latest = $tag["name"];
				$archive = $tag["zipball_url"];
			}
			if ($local[0]['version'] == $tag["commit"]["sha"]) {
				$local[0]['version'] = $tag["name"];
				$nightly = false;
				break;
			}
		}
		
		$search = array("\r\n", "\n", "\r");
		$replace = array(" ", " ", " ");
		
		$message = '';
		$merge = '';
		$commits = json_decode(file_get_contents(Common::getConstant('COMMITURL', $this->commits)), true);
		foreach ($commits as $commit) {
			if ($local[0]['version'] != $commit["sha"]) {
				if (strpos($commit["commit"]["message"], "Merge") === false) {
					$message .= '- '.str_replace($search, $replace, $commit["commit"]["message"]).'<br/>';
				} else {
					$merge .= '- '.str_replace($search, $replace, $commit["commit"]["message"]).'<br/>';
				}
			} else {
				break;
			}
		}
		
		if ($message == '') {
			$message = $merge;
		}
		*/
		
		$archive = $this->archive;
		$current_version = self::VERSION;
		$nightly = false;
		$response = $this->getRemoteVersion("check");
		
		if ( $response["name"] > $current_version ) {
			
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $this->update_file);
			//curl_setopt($curl, CURLOPT_POSTFIELDS, "");
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13');
			$content = curl_exec($curl);
			curl_close($curl);
			
			unlink( "./update.php" );
			file_put_contents( "./update.php", $content );
		}
		
		
		//echo var_dump( $response );
		//return "[".formatJSEND("success", array("currentversion"=>$local[0]['version'],"remoteversion"=>$latest,"message"=>$message,"archive"=>$archive,"nightly"=>$nightly,"name"=>$local[0]['name']))."]";
		return "[".formatJSEND("success", array("currentversion"=>$current_version,"remoteversion"=>$response["name"],"message"=>$response["message"],"archive"=>$archive,"nightly"=>$nightly,"name"=>$response["commit"]["author_name"]))."]";
	}
	
	function CheckProtocol() {
		
		if( extension_loaded( 'curl' ) ) {
			
			//Curl is loaded
			return "curl";
		} elseif( ini_get('allow_url_fopen') ) {
			
			//Remote get file is enabled
			return "fopen";
		} else {
			
			//None are enabled exit.
			return "none";
		}
	}
	
	public function check_for_update() {
		
		$vars = json_decode( $this->Check(), true );
		
		if( $vars[0]['data']['currentversion'] < $vars[0]['data']['remoteversion'] ) {
			
			echo formatJSEND( "notice", "An update for Codiad is available" );
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// Get Local Version
	//////////////////////////////////////////////////////////////////
	
	public function Download(){
		
		
	}
	
	//////////////////////////////////////////////////////////////////
	// Get Local Version
	//////////////////////////////////////////////////////////////////
	
	public function getLocalVersion(){
		
		return getJSON( 'version.php' );
	}
	
	//////////////////////////////////////////////////////////////////
	// Get Remote Version
	//////////////////////////////////////////////////////////////////
	
	public function getRemoteVersion( $action="check", $localversion = "" ) {
		
		if ( $this->protocol === "none" ) {
			
			return;
		}
		
		switch( $this->protocol ) {
			
			case( "curl" ):
				
				$curl = curl_init();
				curl_setopt( $curl, CURLOPT_URL, $this->tags );
				//curl_setopt($curl, CURLOPT_POSTFIELDS, "");
				curl_setopt( $curl, CURLOPT_HEADER, 0 );
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );  
				curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13' );
				$content = curl_exec( $curl );
				curl_close( $curl );
				
				$response = json_decode( $content, true );
				//Return latest release
				return $response[0];
			break;
			
			case( "fopen" ):
				
			break;
		}
	}
}