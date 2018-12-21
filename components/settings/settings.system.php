<?php
    require_once('../../common.php');
?>
<label><span class="icon-doc-text big-icon"></span><?php i18n("System Settings"); ?></label>
<hr>
<label></label>
<table class="settings">
	
	<tr>
	
		<td><?php i18n("Auto Save"); ?></td>
		<td>
			<select class="setting" data-setting="codiad.settings.autosave">
				<option value="true"><?php i18n("True") ?></option>
				<option value="false" default><?php i18n("False") ?></option>
			</select>
		</td>
	
	</tr>
    
    <tr>
    
        <td><?php i18n("Right Sidebar Trigger"); ?></td>
        <td>
            <select class="setting" data-setting="codiad.editor.rightSidebarTrigger">
                <option value="false" default><?php i18n("Hover") ?></option>
                <option value="true"><?php i18n("Click") ?></option>
            </select>
        </td>

    </tr>
    
    <tr>
    
        <td><?php i18n("Filemanager Trigger"); ?></td>
        <td>
            <select class="setting" data-setting="codiad.editor.fileManagerTrigger">
                <option value="false" default><?php i18n("Double Click") ?></option>
                <option value="true"><?php i18n("Single Click") ?></option>
            </select>
        </td>

    </tr>
    
    <tr>
    
        <td><?php i18n("Persistent Modal"); ?></td>
        <td>
            <select class="setting" data-setting="codiad.editor.persistentModal">
                <option value="true" default><?php i18n("Yes") ?></option>
                <option value="false"><?php i18n("No") ?></option>
            </select>
        </td>

    </tr>
</table>
