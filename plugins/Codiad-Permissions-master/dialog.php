<!--
    Copyright (c) Codiad & Andr3as, distributed
    as-is and without warranty under the MIT License. 
    See [root]/license.md for more information. This information must remain intact.
-->
<form id="permissions_dialog">
    <?php
        error_reporting(0);
        
        require_once('class.util.php');
        require_once('../../common.php');
        checkSession();
        
        $path = util::getWorkspacePath($_GET['path']);
        if (is_dir($path)) {
            $perm = substr(decoct(fileperms($path)),2);
        } else {
            $perm = substr(decoct(fileperms($path)),3);
        }
        echo '
            <p>Change permissions</p>
            <input id="permissions" placeholder="'.$perm.'" type="text">
        ';
    ?>
    <div class="more_control"><span class="item-icon icon-right-open-big"> Easy setup</span></div>
    <div class="more">
		<table>
			<tr class="owner">
				<td>Owner</td>
				<td><input type="checkbox" class="owner r"> R</td>
				<td><input type="checkbox" class="owner w"> W</td>
				<td><input type="checkbox" class="owner x"> X</td>
			</tr>
			<tr class="group">
				<td>Group</td>
				<td><input type="checkbox" class="group r"> R</td>
				<td><input type="checkbox" class="group w"> W</td>
				<td><input type="checkbox" class="group x"> X</td>
			</tr>
			<tr class="other">
				<td>Other</td>
				<td><input type="checkbox" class="other r"> R</td>
				<td><input type="checkbox" class="other w"> W</td>
				<td><input type="checkbox" class="other x"> X</td>
			</tr>
		</table>
    </div>
    <button onclick="codiad.Permissions.change(); return false;">Change</button>
    <button onclick="codiad.modal.unload(); return false;">Close</button>
    <script>
        $('.more').hide();
        $('#permissions').focus();
    </script>
</form>