<!--
    Copyright (c) Codiad & Rafasashi, distributed
    as-is and without warranty under the MIT License. 
    See http://opensource.org/licenses/MIT for more information.
    This information must remain intact.
-->
<?php 
	
    require_once('../../common.php');
    checkSession();
	
	require_once('./functions.php');
	
	echo'<form>';

		echo'<label>Extract contents</label>';
		
		//echo'<input type="text" id="extract_name" value="'. $_GET['name']'.">';
		
		echo'<select id="extract_path">';
			
			echo'<option value="">'.basename($_GET['path']).'</option>';
			
			//---------------fetch ArchiveTree-------------------------
			
			$tree = getArchiveTree(1);
			
			foreach($tree as $name => $path){
				
				echo'<option value="'.$path.'">'.htmlentities('├').' '.$name.'</option>';
			}
			
		echo'</select>';
		
		echo'<button onclick="codiad.Extract.extract(); return false;">Extract here</button>';
		
		echo'<button onclick="codiad.modal.unload(); return false;">Close</button>';
		
	echo'</form>';

?>
