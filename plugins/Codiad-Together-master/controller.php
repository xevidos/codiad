<?php

    /*
    *  Copyright (c) Codiad & daeks, distributed
    *  as-is and without warranty under the MIT License. See
    *  [root]/license.txt for more. This information must remain intact.
    */
    

    require_once('../../common.php');

    //////////////////////////////////////////////////////////////////
    // Verify Session or Key
    //////////////////////////////////////////////////////////////////

    if(isset($_GET['action']) && $_GET['action']!='authenticate'){ checkSession(); }
    
    //////////////////////////////////////////////////////////////////
    // Get Username
    //////////////////////////////////////////////////////////////////

    if($_GET['action']=='username'){
        echo $_SESSION['user'];
    }
    
?>
