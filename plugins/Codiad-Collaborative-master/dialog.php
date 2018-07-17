<?php

    /*
    *  Copyright (c) Codiad, distributed
    *  as-is and without warranty under the MIT License. See
    *  [root]/license.txt for more. This information must remain intact.
    */

    require_once('../../common.php');

    //////////////////////////////////////////////////////////////////
    // Verify Session or Key
    //////////////////////////////////////////////////////////////////

    checkSession();

    switch($_GET['action']){

        //////////////////////////////////////////////////////////////////////
        // Show Warning
        //////////////////////////////////////////////////////////////////////

        case 'warn':

        ?>
        <form>
            <label>Codiad Collaborative</label>
            <pre>Not compatible with this version of Codiad</pre>
            <em class="note">Note: This plugin requires at least Codiad 2.x.</em><br><br>
            <button onclick="codiad.modal.unload(); return false;">Close</button>
        </form>
        <?php
        break;

    }

?>