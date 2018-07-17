<?php

    /*
    *  Copyright (c) Codiad & daeks (codiad.com), distributed
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
        // Update
        //////////////////////////////////////////////////////////////////////
        
        case 'check':
        
            if(!checkAccess()){ 
            ?>
            <label><?php i18n("Restricted"); ?></label>
            <pre><?php i18n("You can not check for updates"); ?></pre>
            <button onclick="codiad.modal.unload();return false;"><?php i18n("Close"); ?></button>
            <?php } else {
                require_once('class.autoupdate.php');
                $update = new AutoUpdate();
                if(isset($_GET['type'])) {
                  $update->type = $_GET['type'];
                }
                $vars = json_decode($update->Check(), true);            
            ?>
            <form>
            <input type="hidden" name="archive" value="<?php echo $vars[0]['data']['archive']; ?>">
            <input type="hidden" name="remoteversion" value="<?php echo $vars[0]['data']['remoteversion']; ?>">
            <input type="hidden" name="remotename" value="<?php echo $vars[0]['data']['remotename']; ?>">
            <label><?php i18n("Auto Update Check"); ?></label>
            <?php if($update->remote == Common::getConstant('UPDATEURL', $update->remote)) { ?>
            <table>
                <tr>
                  <td width="40%"><?php i18n("Update Channel"); ?></td>
                  <td><select id="type" name="type" onchange="codiad.autoupdate.check(this.value);">
                      <option value="stable" <?php if($update->type == 'stable') { echo "selected"; }?>>Stable Version</option>
                      <option value="latest" <?php if($update->type == 'latest') { echo "selected"; }?>>Latest Version</option>
                      </select>
                  </td>
              </tr>
            </table>
            <?php } ?> 
            <br><table>
                <tr><td width="40%"><?php i18n("Your Version"); ?></td><td><?php echo $vars[0]['data']['currentname']; ?></td></tr>
                <tr><td width="40%"><?php i18n("Latest Version"); ?></td><td><?php echo $vars[0]['data']['remotename']; ?></td></tr>
            </table>
            <?php if($vars[0]['data']['currentversion'] != $vars[0]['data']['remoteversion']) { ?>
            <br><label><?php i18n("Changes on Codiad"); ?></label>
            <pre style="overflow: auto; max-height: 200px; max-width: 510px;"><?php echo $vars[0]['data']['message']; ?></pre>
            <?php } else { ?>
            <br><br><b><label><?php i18n("Congratulation, your system is up to date."); ?></label></b>
            <?php if($vars[0]['data']['name'] != '') { ?>
            <em class="note"><?php i18n("Last update was done by "); ?><?php echo $vars[0]['data']['name']; ?>.</em><br>
            <?php } 
                if($vars[0]['data']['autoupdate'] == '1') {
                    echo '<div align="right"><a href="#" style="color:white; text-decoration: none;" onclick="codiad.autoupdate.update();return false;">Force Update Codiad</a></div>';
                }
            } ?>
            <?php if($vars[0]['data']['nightly']) { ?>
            <br><em class="note">Note: Your installation is a nightly build. Codiad might be unstable.</em><br>
            <?php } ?>
            <br><?php
                if($vars[0]['data']['currentversion'] != $vars[0]['data']['remoteversion']) {
                    if($vars[0]['data']['autoupdate'] == '1') {
                        echo '<button class="btn-left" onclick="codiad.autoupdate.update();return false;">Update Codiad</button>&nbsp;<button class="btn-left" onclick="codiad.autoupdate.download();return false;">Download Codiad</button>&nbsp;';
                    } else {
                        if($vars[0]['data']['autoupdate'] == '-1') {
                            echo '<button class="btn-left" onclick="codiad.autoupdate.download();return false;">Download Codiad</button>&nbsp;';
                        } else {
                            echo '<button class="btn-left" onclick="codiad.autoupdate.check();return false;">Test Permission</button>&nbsp;<button class="btn-left" onclick="codiad.autoupdate.download();return false;">Download Codiad</button>&nbsp;';
                        }
                    }
                }
            ?><button class="btn-right" onclick="codiad.modal.unload();return false;"><?php i18n("Cancel"); ?></button>
            <form>
            <?php }
            break;
            
        //////////////////////////////////////////////////////////////////
        // Update
        //////////////////////////////////////////////////////////////////
        
        case 'update':
            ?>
            <form>
            <input type="hidden" name="remoteversion" value="<?php echo($_GET['remoteversion']); ?>">
            <label><?php i18n("Confirm Update"); ?></label>
            <pre><?php i18n("Update:"); ?> <?php echo($_GET['remotename']); ?></pre>
            <button class="btn-left"><?php i18n("Confirm"); ?></button>&nbsp;<button class="btn-right" onclick="codiad.modal.unload(); return false;"><?php i18n("Cancel"); ?></button>
            <form>
            <?php
            break;
            
    }
    
?>
