<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

class User
{

    //////////////////////////////////////////////////////////////////
    // PROPERTIES
    //////////////////////////////////////////////////////////////////

    public $username    = '';
    public $password    = '';
    public $project     = '';
    public $projects    = '';
    public $users       = '';
    public $actives     = '';
    public $lang        = '';
    public $theme       = '';

    //////////////////////////////////////////////////////////////////
    // METHODS
    //////////////////////////////////////////////////////////////////

    // -----------------------------||----------------------------- //

    //////////////////////////////////////////////////////////////////
    // Construct
    //////////////////////////////////////////////////////////////////

    public function __construct()
    {
        $this->users = getJSON('users.php');
        $this->actives = getJSON('active.php');
    }

    //////////////////////////////////////////////////////////////////
    // Authenticate
    //////////////////////////////////////////////////////////////////

    public function Authenticate()
    {

        $pass = false;
        $this->EncryptPassword();
        $users = getJSON('users.php');
        foreach ($users as $user) {
            if ($user['username']==$this->username && $user['password']==$this->password) {
				
                $pass = true;
                $_SESSION['id'] = SESSION_ID;
                $_SESSION['user'] = $this->username;
                $_SESSION['lang'] = $this->lang;
                $_SESSION['theme'] = $this->theme;
        		$_SESSION["login_session"] = true;
                
                if ($user['project']!='') {
                    $_SESSION['project'] = $user['project'];
                }
                
                $this->checkDuplicateSessions( $this->username );
            }
        }
		
        if ($pass) {
        	
            echo formatJSEND("success", array("username"=>$this->username));
        } else {
            echo formatJSEND("error", "Incorrect Username or Password");
        }
    }
    
    /**S
     * Check duplicate sessions
     * 
     * This function checks to see if the user is currently logged in
     * on any other machine and if they are then log them off.  This
     * will fix the issue with the new auto save attempting to save both
     * users at the same time.
     */
    
    public static function checkDuplicateSessions( $username ) {
    	
    	//ini_set('display_errors', 1);
		//ini_set('display_startup_errors', 1);
		//error_reporting(E_ALL);
    	session_write_close();
        $all_sessions = array();
        $sessions = glob( SESSIONS_PATH . "/*" );
        session_id( SESSION_ID );
        // session_save_path( SESSIONS_PATH );
        
        foreach( $sessions as $session ) {
        	
        	//echo var_dump( $session ) . "\n\n";
        	
        	if ( strpos( $session, "sess_") == false ) {
        		continue;
        	}
        	
        	$session = str_replace( "sess_", "", $session );
        	$session = str_replace( SESSIONS_PATH . "/", "", $session );
        	//This skips temp files that aren't sessions
        	if( strpos( $session, "." ) == false ) {
        		
        		session_id( $session );
        		session_start();
        		$_SESSION["id"] = $session;
        		array_push( $all_sessions, $_SESSION );
        		
        		if ( isset( $_SESSION["user"] ) && $_SESSION["user"] === $username && isset( $_SESSION["login_session"] ) && $_SESSION["login_session"] === true && SESSION_ID !== session_id() ) {
        		    
        		    session_destroy();
        		} else {
        			
        			session_abort();
        		}
        	}
        }
        //echo '{"status":"error","message":"' . print_r( $all_sessions ) . '"}';
        
        session_id( SESSION_ID );
        session_start();
    }

    //////////////////////////////////////////////////////////////////
    // Create Account
    //////////////////////////////////////////////////////////////////

    public function Create()
    {
        $this->EncryptPassword();
        $pass = $this->checkDuplicate();
        if ($pass) {
            $this->users[] = array("username"=>$this->username,"password"=>$this->password,"project"=>"");
            saveJSON('users.php', $this->users);
            echo formatJSEND("success", array("username"=>$this->username));
        } else {
            echo formatJSEND("error", "The Username is Already Taken");
        }
    }

    //////////////////////////////////////////////////////////////////
    // Delete Account
    //////////////////////////////////////////////////////////////////

    public function Delete()
    {
        // Remove User
        $revised_array = array();
        foreach ($this->users as $user => $data) {
            if ($data['username']!=$this->username) {
                $revised_array[] = array("username"=>$data['username'],"password"=>$data['password'],"project"=>$data['project']);
            }
        }
        // Save array back to JSON
        saveJSON('users.php', $revised_array);

        // Remove any active files
        foreach ($this->actives as $active => $data) {
            if ($this->username==$data['username']) {
                unset($this->actives[$active]);
            }
        }
        saveJSON('active.php', $this->actives);

        // Remove access control list (if exists)
        if (file_exists(BASE_PATH . "/data/" . $this->username . '_acl.php')) {
            unlink(BASE_PATH . "/data/" . $this->username . '_acl.php');
        }

        // Response
        echo formatJSEND("success", null);
    }

    //////////////////////////////////////////////////////////////////
    // Change Password
    //////////////////////////////////////////////////////////////////

    public function Password()
    {
        $this->EncryptPassword();
        $revised_array = array();
        foreach ($this->users as $user => $data) {
            if ($data['username']==$this->username) {
                $revised_array[] = array("username"=>$data['username'],"password"=>$this->password,"project"=>$data['project']);
            } else {
                $revised_array[] = array("username"=>$data['username'],"password"=>$data['password'],"project"=>$data['project']);
            }
        }
        // Save array back to JSON
        saveJSON('users.php', $revised_array);
        // Response
        echo formatJSEND("success", null);
    }

    //////////////////////////////////////////////////////////////////
    // Set Project Access
    //////////////////////////////////////////////////////////////////

    public function Project_Access()
    {
        // Access set to all projects
        if ($this->projects==0) {
            if (file_exists(BASE_PATH . "/data/" . $this->username . '_acl.php')) {
                unlink(BASE_PATH . "/data/" . $this->username . '_acl.php');
            }
        // Access set to restricted list
        } else {
            // Save array back to JSON
            saveJSON($this->username . '_acl.php', $this->projects);
        }
        // Response
        echo formatJSEND("success", null);
    }

    //////////////////////////////////////////////////////////////////
    // Set Current Project
    //////////////////////////////////////////////////////////////////

    public function Project()
    {
        $revised_array = array();
        foreach ($this->users as $user => $data) {
            if ($this->username==$data['username']) {
                $revised_array[] = array("username"=>$data['username'],"password"=>$data['password'],"project"=>$this->project);
            } else {
                $revised_array[] = array("username"=>$data['username'],"password"=>$data['password'],"project"=>$data['project']);
            }
        }
        // Save array back to JSON
        saveJSON('users.php', $revised_array);
        // Response
        echo formatJSEND("success", null);
    }

    //////////////////////////////////////////////////////////////////
    // Check Duplicate
    //////////////////////////////////////////////////////////////////

    public function CheckDuplicate()
    {
        $pass = true;
        foreach ($this->users as $user => $data) {
            if ($data['username']==$this->username) {
                $pass = false;
            }
        }
        return $pass;
    }

    //////////////////////////////////////////////////////////////////
    // Verify Account Exists
    //////////////////////////////////////////////////////////////////

    public function Verify()
    {
        $pass = 'false';
        foreach ($this->users as $user => $data) {
            if ($this->username==$data['username']) {
                $pass = 'true';
            }
        }
        echo($pass);
    }

    //////////////////////////////////////////////////////////////////
    // Encrypt Password
    //////////////////////////////////////////////////////////////////

    private function EncryptPassword()
    {
        $this->password = sha1(md5($this->password));
    }

    //////////////////////////////////////////////////////////////////
    // Clean username
    //////////////////////////////////////////////////////////////////

    public static function CleanUsername($username)
    {
        return preg_replace('#[^A-Za-z0-9'.preg_quote('-_@. ').']#', '', $username);
    }
}