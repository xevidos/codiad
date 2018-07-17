<?php
/*
 * Copyright (c) Codiad, Rafasashi, distributed
 * as-is and without warranty under the MIT License. 
 * See http://opensource.org/licenses/MIT for more information.
 * This information must remain intact.
 */

    function getWorkspacePath($path, $error='Invalid path'){
		
		//Security check
		if (!Common::checkPath($path)) {
			
			die($error);
		}
        if (strpos($path, "/") === 0) {
            //Unix absolute path
            return $path;
        }
        if (strpos($path, ":/") !== false) {
            //Windows absolute path
            return $path;
        }
        if (strpos($path, ":\\") !== false) {
            //Windows absolute path
            return $path;
        }
		
        return WORKSPACE . "/" . $path;
    }

	function getArchiveTree($level=1){
		
		$tree=[];
		
		if(isset($_GET['path'])){
			
			//TODO: Common::checkPath($path) return false...
			$source = getWorkspacePath($_GET['path']);
			
			//$source = WORKSPACE . "/" . $_GET['path'];

			if(file_exists($source)){
			
				$source_info=pathinfo($source);

				if(isset($source_info['extension'])&&!empty($source_info['extension'])){
					
					$des = dirname($source);
					
					if($source_info['extension']=='zip'){
						
						if(class_exists('ZipArchive') && $zip = new ZipArchive) {
		
							if($res = $zip->open($source)){

								for ($i = 0; $i < $zip->numFiles; $i++) {
									
									$name = $zip->getNameIndex($i);
									$path = $name;
									
									$count=substr_count($path, '/');
									
									if($count > $level){
										
										continue;
									}
									
									$tree[$name]=$path;
								}								
								
								$zip->close();
							  
							}
						}
					}
					elseif($source_info['extension']=='tar') {
						
						if(class_exists('PharData') && $tar = new PharData($source)) {

							//TODO: get tar tree
						}
					}
					elseif($source_info['extension']=='rar') {
						
						if(class_exists('rar_open') && $rar = new rar_open) {
		
							if($res = $rar->open($source)){
							
								$entries = rar_list($res);

								//TODO: get rar tree
								
								$rar->close();
							  
							} 
						}
					}
				}
			}
		}
		
		return $tree;
	}
	
?>
