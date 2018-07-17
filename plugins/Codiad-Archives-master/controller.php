<?php
/*
 * Copyright (c) Codiad, Rafasashi & beli3ver, distributed
 * as-is and without warranty under the MIT License. 
 * See http://opensource.org/licenses/MIT for more information.
 * This information must remain intact.
 */
    error_reporting(0);

    require_once('../../common.php');
	
    checkSession();
	
	require_once('./functions.php');

    //need for rar filelist check
    $error = false;
    
    switch($_GET['action']) {
        
        case 'extract':

			if(isset($_GET['path'])){
			
				$source = getWorkspacePath($_GET['path']);
				
				$source_info=pathinfo($source);

				if(!isset($source_info['extension'])||empty($source_info['extension'])){
					
					echo '{"status":"error","message":"Not an archive"}';
				}
				else{
					
					$des = dirname($source);
					
					if($source_info['extension']=='zip') {
						
						if(class_exists('ZipArchive') && $zip = new ZipArchive) {

							if($res = $zip->open($source)){
								
								$epath = '';
								
								if(isset($_GET['epath'])){
									
									$epath = trim($_GET['epath']);
								}
								
								if($epath==''||$epath=='/'||$epath==$source_info['basename']){
									
									// extract all archive to the path we determined above
									
									if($zip->extractTo($des)){
										
										echo '{"status":"success","message":"Archive extracted"}';
									}
									else{
										
										echo '{"status":"error","message":"Failed to extract contents"}';
									}
								}
								else{
									
									// extract epath to the path we determined above
									
									if(substr($epath, -1)!=='/'){
										
										//extract single file
										
										if(copy("zip://".$source."#".$epath, $des.'/'.basename($epath)) === TRUE){
											
											echo '{"status":"success","message":"Sub contents extracted"}';
										}
										else{
											
											echo '{"status":"error","message":"Failed to extract sub contents"}';
										}								
									}
									else{
										
										//extract sub directory recursively
										
										$response='{"status":"success","message":"Sub contents extracted"}';
										
										for($i = 0; $i < $zip->numFiles; $i++){
											
											$info = $zip->statIndex($i);
											
											$entry = $info['name'];
											
											$is_dir=false;
											
											if($info['crc'] == 0 && substr($entry, -1)=='/'){
												
												$is_dir=true;
											}
											
											if(strpos($entry,$epath)===0){
												
												//get branche path from targeted directory
												
												$branche=explode($epath,$entry,2);
												$branche=$branche[1];												
												
												if($is_dir){
													
													if($branche!=''&&!is_dir($des.'/'.$branche)&&!mkdir($des.'/'.$branche,0755,true)){
														
														$response= '{"status":"error","message":"Failed to create sub directory"}';
														break;
													}
												}
												elseif(!copy("zip://".$source."#".$entry, $des.'/'.$branche)){
														
													$response= '{"status":"error","message":"Failed to copy sub file"}';
													break;
												}
											}										
										}
										
										echo $response;
									}
								}

								$zip->close();
							  
							} 
							else {
								
								echo '{"status":"error","message":"Could not open zip archive"}';
							}
						}
					}
					elseif($source_info['extension']=='tar') {
						
						if(class_exists('PharData') && $tar = new PharData($source)) {

							if($tar->extractTo($des)){
								
								echo '{"status":"success","message":"File extracted"}';
							}
							else{
								
								echo '{"status":"error","message":"Failed to extract contents"}';
							}
							
						}
						else {
							
							echo '{"status":"error","message":"PharData extension missing or cloud not open tar archive"}';
						}
					}
					elseif($source_info['extension']=='gz') {
						
						if(class_exists('PharData') && $gz = new PharData($source)) {
							
							if($tar_gzip_files = $gz->decompress()) {
								
								$tar_gzip = new PharData($tar_gzip_files);
								
								if($$tar_gzip->extractTo($des)){
									echo '{"status":"success","message":"File extracted"}';
								}
								else {
									echo '{"status":"error","message":"Failed to extract the files"}';
								}
							}
							else {
								echo '{"status":"error","message":"Failed to extract the .gzip file"}';
							}
							

						}
						else {
							
							echo '{"status":"error","message":"PharData extension missing or cloud not open tar archive"}';
						}
					}
					elseif($source_info['extension']=='bz2') {
						
						if(class_exists('PharData') && $bz = new PharData($source)) {
							
							if($tar_bzip2_files = $bz->decompress()) {
								
								$tar_bzip2 = new PharData($tar_bzip2_files);
								
								if($$tar_bzip2->extractTo($des)){
									echo '{"status":"success","message":"File extracted"}';
								}
								else {
									echo '{"status":"error","message":"Failed to extract the files"}';
								}
							}
							else {
								echo '{"status":"error","message":"Failed to extract the .bzip2 file"}';
							}
							

						}
						else {
							
							echo '{"status":"error","message":"PharData extension missing or cloud not open tar archive"}';
						}
					}
					elseif($source_info['extension']=='rar') {
						
						if(class_exists('rar_open') && $rar = new rar_open) {
		
							if($res = $rar->open($source)){
							
								$entries = rar_list($res);
								try {
									foreach ($entries as $entry) {
									    $entry->extract($des);
									}
								} catch (Exception $e) {
								    $error = true;
								}
								
								// extract it to the path we determined above
								if($error === false){
									
									echo '{"status":"success","message":"File extracted"}';
								}
								else{
									
									echo '{"status":"error","message":"Failed to extract contents"}';
								}
								
								$rar->close();
							  
							} 
							else {
								
								echo '{"status":"error","message":"Could not open rar archive"}';
							}
						}
						else {
							
							echo '{"status":"error","message":"Cloud not open rar archive"}';
						}
					}
					else {
						
						echo '{"status":"error","message":"Looks like a .'.$source_info['extension'].'"}';
					}
				}
			} 
			else {
				
                echo '{"status":"error","message":"Missing Parameter"}';
            }
            break;
        
        default:
            echo '{"status":"error","message":"No Type"}';
            break;
    }
?>
