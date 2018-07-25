<?php

/*
*  Copyright (c) Codiad & daeks (codiad.com), distributed
*  as-is and without warranty under the MIT License. See 
*  [root]/license.txt for more. This information must remain intact.
*/



//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

require_once('../../common.php');
checkSession();

switch($_GET['action']){
	
	//////////////////////////////////////////////////////////////////////
	// Update
	//////////////////////////////////////////////////////////////////////
	
	case 'check':
	
	if( ! checkAccess() ){
	
		?>
		<label><?php i18n("Restricted"); ?></label>
		<pre><?php i18n("You can not check for updates"); ?></pre>
		<button onclick="codiad.modal.unload();return false;"><?php i18n("Close"); ?></button>
		<?php
		
	} else {
		
		require_once('./class.update.php');
		$update = new Update();
		$vars = json_decode($update->Check(), true);
		
		?>
		<form>
		<input type="hidden" name="archive" value="<?php echo $vars[0]['data']['archive']; ?>">
		<a id="update" href="<?php echo Common::getConstant('BASE_URL') . "/components/update/update.php"; ?>" target="_blank" style="display: none;"></a>
		<input type="hidden" name="remoteversion" value="<?php echo $vars[0]['data']['remoteversion']; ?>">
		<label><?php i18n("Update Check"); ?></label>
		<br><table>
		<tr><td width="40%"><?php i18n("Your Version"); ?></td><td><?php echo $vars[0]['data']['currentversion']; ?></td></tr>
		<tr><td width="40%"><?php i18n("Latest Version"); ?></td><td><?php echo $vars[0]['data']['remoteversion']; ?></td></tr>
		</table>
		<?php
		
		if( $vars[0]['data']['currentversion'] < $vars[0]['data']['remoteversion'] ) {
			?>
			<br><label><?php i18n("Changes on Codiad"); ?></label>
			<pre style="overflow: auto; max-height: 200px; max-width: 510px;"><?php echo $vars[0]['data']['message']; ?></pre>
			<?php
		} elseif( $vars[0]['data']['remoteversion'] === "" || $vars[0]['data']['remoteversion'] === null ) {
			
			?>
			<br><br><b><label>
				<?php echo htmlentities("Error, could not check for updates.  Please try again later ..."); ?><br>
				<?php echo htmlentities("If this problem persists, then please contact the web administrator."); ?>
			</label></b>
			<?php
		} else {
			?>
			<br><br><b><label><?php echo htmlentities("Your current version of Codiad is up to date."); ?></label></b>
			<?php
			if( $vars[0]['data']['name'] != '' ) {
				?>
				<em><?php i18n("Last update was done by "); ?><?php echo $vars[0]['data']['name']; ?>.</em>
				<?php
			}
		}
		?>
		<?php
		if( $vars[0]['data']['nightly'] ) {
			?>
			<br><em class="note"><?php i18n("Note: Your installation is a nightly build. Codiad might be unstable."); ?></em><br>
			<?php
		}
		?>
		<br>
		<?php
		if( $vars[0]['data']['currentversion'] < $vars[0]['data']['remoteversion'] ) {
			echo '<p>The update will open a new tab. Please allow popups from Codiad.</p>';
			echo '<button class="btn-left" onclick="event.preventDefault();codiad.update.update();return false;">'.get_i18n("Update Codiad").'</button>&nbsp;';
			echo '<button class="btn-left" onclick="codiad.update.download();return false;">'.get_i18n("Download Codiad").'</button>&nbsp;';
		}
		?>
		<button class="btn-right" onclick="codiad.modal.unload();return false;"><?php i18n("Cancel"); ?></button>
		<form>
		<?php
	}
	break;
}

?>
