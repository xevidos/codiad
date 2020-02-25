<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See 
*  [root]/license.txt for more. This information must remain intact.
*/


require_once( '../../common.php' );
require_once( './class.project.php' );
require_once( '../user/class.user.php' );

//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

checkSession();
$Project = new Project;

switch( $_GET['action'] ) {
	
	//////////////////////////////////////////////////////////////
	// List Projects Mini Sidebar
	//////////////////////////////////////////////////////////////
	case 'sidelist':
		
		// Get projects data
		$projects = $Project->get_projects();
		?>
		<ul>
			<?php
			//natcasesort( $projects );
			foreach( $projects as $project => $data ) {
				
				if( $_GET['trigger'] == 'true' ) {
					
					?>
					<li onclick="codiad.project.open('<?php echo( $data['path'] );?>');"><div class="icon-archive icon"></div><?php echo( $data['name'] );?></li>
					<?php
				} else {
					
					?>
					<li ondblclick="codiad.project.open('<?php echo( $data['path'] );?>');"><div class="icon-archive icon"></div><?php echo( $data['name'] );?></li>
					<?php
				}
			} 
			?>
		</ul>
		<?php
	break;
	
	//////////////////////////////////////////////////////////////
	// List Projects
	//////////////////////////////////////////////////////////////
	
	case 'list':
		
		//Get projects data
		if( isset( $_GET["all"] ) ) {
			
			$projects = $Project->get_all_projects();
		} else {
			$projects = $Project->get_projects();
		}
		?>
		<label><?php i18n("Project List"); ?></label>
		<div id="project-list">
			<table width="100%">
				<tr>
					<th width="70"><?php i18n( "Open");?></th>
					<th width="150"><?php i18n( "Project Name" );?></th>
					<th width="250"><?php i18n( "Path" );?></th>
					<th width="70"><?php i18n( "Access" );?></th>
					<th width="70"><?php i18n( "Delete" );?></th>
				</tr>
			</table>
			<div class="project-wrapper">
				<table width="100%" style="word-wrap: break-word;word-break: break-all;">
					<?php
					if( is_array( $projects ) ) {
						
						foreach( $projects as $project => $data ) {
							
							$show = true;
							if( $show ) {
								
								?>
								<tr>
									<td width="70"><a onclick="codiad.project.open('<?php echo( $data['path'] );?>');" class="icon-folder bigger-icon"></a></td>
									<td width="150"><?php echo($data['name']);?></td>
									<td width="250"><?php echo($data['path']);?></td>
									<?php
									$owner = $Project->get_owner( $data['path'] );
									if( $owner == -1 ) {
										
										?>
										<td width="70"><a onclick="codiad.message.error(i18n('Public projects can not be managed'));" class="icon-block bigger-icon"></a></td>
										<?php
									} elseif( $owner !== $_SESSION["user_id"] ) {
										
										?>
										<td width="70"><a onclick="codiad.message.error(i18n('Projects owned by others can not be managed'));" class="icon-block bigger-icon"></a></td>
										<?php
									} else {
										
										?>
										<td width="70"><a onclick="codiad.project.manage_access( '<?php echo( $data['path'] );?>' );" class="icon-lock bigger-icon"></a></td>
										<?php
									}
									?>
									<?php
									if( $_SESSION['project'] == $data['path'] ) {
										
										?>
										<td width="70"><a onclick="codiad.message.error(i18n('Active Project Cannot Be Removed'));" class="icon-block bigger-icon"></a></td>
										<?php
									} elseif( $owner !== $_SESSION["user_id"] && $owner != -1 ) {
										
										?>
										<td width="70"><a onclick="codiad.message.error(i18n('Projects owned by others can not be deleted'));" class="icon-block bigger-icon"></a></td>
										<?php
									} else {
										
										?>
										<td width="70"><a onclick="codiad.project.delete('<?php echo($data['name']);?>','<?php echo($data['path']);?>');" class="icon-cancel-circled bigger-icon"></a></td>
										<?php
									}
									?>
								</tr>
								<?php
							}
						}
					} else {
						
						$error = json_decode( $projects, true );
						echo $error["message"];
					}
					?>
				</table>
			</div>
		</div>
		<button class="btn-left" onclick="codiad.project.create();"><?php i18n("New Project");?></button>
		<button class="btn-right" onclick="codiad.modal.unload();return false;"><?php i18n("Close");?></button>
		<?php
	break;
	
	//////////////////////////////////////////////////////////////////////
	// Create New Project
	//////////////////////////////////////////////////////////////////////
	
	case 'create':
		
		?>
		<form>
			<label><?php i18n( "Project Name" );?></label>
			<input name="project_name" autofocus="autofocus" autocomplete="off">
			<label><?php i18n( "Folder Name or Absolute Path" );?></label>
			<input name="project_path" autofocus="off" autocomplete="off">
			<label><?php i18n( "Public Project" );?></label>
			<select name="public_project">
				<option value="false">False</option>
				<option value="true">True</option>
			</select>
			<p><small><i>Note: Everyone will have full access to public projects.</i></small></p>
			<!-- Clone From GitHub -->
			<div style="width: 500px;">
				<table class="hide" id="git-clone">
					<tr>
						<td>
							<label><?php i18n( "Git Repository" );?></label>
							<input name="git_repo">
						</td>
						<td width="5%">&nbsp;</td>
						<td width="25%">
							<label><?php i18n( "Branch" );?></label>
							<input name="git_branch" value="master">
						</td>
					</tr>
					<tr>
						<td colspan="3" class="note"><?php i18n( "Note: This will only work if your Git repo DOES NOT require interactive authentication and your server has git installed." );?></td>
					</tr>
				</table>
			</div>
			<!-- /Clone From GitHub -->
			<?php
			$action = 'codiad.project.list();';
			if( $_GET['close'] == 'true' ) {
				
				$action = 'codiad.modal.unload();';
			} 
			?>           
			<button class="btn-left"><?php i18n( "Create Project" );?></button>
			<button onclick="$('#git-clone').slideDown(300); $(this).hide(); return false;" class="btn-mid"><?php i18n( "...From Git Repo" ); ?></button>
			<button class="btn-right" onclick="<?php echo $action;?>return false;"><?php i18n( "Cancel" );?></button>
		</form>
		<?php
	break;
	
	//////////////////////////////////////////////////////////////
	// Manage Project Access
	//////////////////////////////////////////////////////////////
	case 'manage_access':
		
		/**
		 * Check and see if the path of the project is set.  Also check and
		 * see if the project the user is attempting to view permissions for
		 * is one that they own.
		 */
		if( ! isset( $_GET["path"] ) || ! $Project->check_owner( $_GET["path"], true ) ) {
			?>
			<p>Error, you either do not own this project or it is a public project.</p>
			<button class="btn-right" onclick="codiad.project.list();return false;"><?php i18n( "Back" );?></button>
			<?php
			return;
		}
		
		// Get projects data
		$User = new User();
		$path = $_GET['path'];
		$project = $Project->get_project( $path );
		$access = $Project->get_access( $project["id"] );
		$users = get_users( "return", true );
		$user = $User->get_user( $_SESSION["user"] );
		
		if( isset( $users["status"] ) && $users["status"] == "error" ) {
			
			?>
			<p>Error, could not fetch users information.</p>
			<button class="btn-left" onclick="codiad.project.list();return false;"><?php i18n( "Back" );?></button>
			<?php
			exit();
		} else if( empty( $users ) ) {
			
			?>
			<p>Error, You must have more than one user registered in your Codiad instance to manage permissions.</p>
			<button class="btn-left" onclick="codiad.project.list();return false;"><?php i18n( "Back" );?></button>
			<?php
			exit();
		}
		
		?>
		<form onSubmit="event.preventDefault();">
			<input type="hidden" name="project_path" value="<?php echo $path;?>">
			<input type="hidden" name="project_id" value="<?php echo $project["id"];?>">
			<label><span class="icon-pencil"></span><?php i18n( "Add Users" );?></label>
			<input id="search_users" type="text" onkeyup="codiad.project.search_users();" />
			<select id="user_list" name="user_list">
				<?php
				foreach( $users as $i ) {
					
					?>
					<option value="<?php echo htmlentities( $i["id"] );?>"><?php echo htmlentities( $i["username"] );?></option>
					<?php
				}
				?>
			</select>
			<button class="btn-left" onclick="codiad.project.add_user();">Add User</button>
			<?php
			if( $access == null || empty( $access ) ) {
				
				?>
				<p>No users have been given access.</p>
				<?php
			} else {
				
				?>
				<table id="access_list">
				<?php
				
				foreach( $access as $row => $user_permissions ) {
					
					$i = null;
					
					foreach( $users as $r => $current_user ) {
						
						if( $current_user["id"] == $user_permissions["user"] ) {
							
							$i = $current_user;
							break;
						}
					}
					
					if( ! $i ) {
						
						continue;
					}
					
					?>
					<tr>
						<td>
							<p><?php echo htmlentities( $i["username"] );?></p>
						</td>
						<td>
							<select onchange="codiad.project.change_access( event );">
								<?php
								foreach( Permissions::LEVELS as $level => $id ) {
									
									if( $id == $user_permissions["level"] ) {
										
										$selected = "selected='selected'";
									} else {
										
										$selected = "";
									}
									?><option value="<?php echo $level;?>" <?php echo $selected;?>><?php echo ucfirst( $level );?></option><?php
								}
								?>
							</select>
							<button class="btn-left" onclick="codiad.project.remove_user( '<?php echo htmlentities( $i["id"] );?>' );">Remove Access</button>
						</td>
					</tr>
					<?php
				}
				?>
				</table>
				<?php
			}
			?>
			<button class="btn-left" onclick="codiad.project.list();return false;"><?php i18n( "Back" );?></button>
			<button class="btn-right" onclick="codiad.modal.unload();return false;"><?php i18n( "Done" );?></button>
		<form>
		<?php
	break;
	
	//////////////////////////////////////////////////////////////////
	// Rename
	//////////////////////////////////////////////////////////////////
	case 'rename':
		
		?>
		<form>
			<input type="hidden" name="project_path" value="<?php echo( $_GET['path'] );?>">
			<label><span class="icon-pencil"></span><?php i18n( "Rename Project" );?></label>    
			<input type="text" name="project_name" autofocus="autofocus" autocomplete="off" value="<?php echo( $_GET['name'] );?>">  
			<button class="btn-left"><?php i18n( "Rename" );?></button>&nbsp;<button class="btn-right" onclick="codiad.modal.unload(); return false;"><?php i18n( "Cancel" );?></button>
		<form>
		<?php
	break;       
	
	//////////////////////////////////////////////////////////////////////
	// Delete Project
	//////////////////////////////////////////////////////////////////////
	
	case 'delete':
		
		?>
		<form>
			<input type="hidden" name="project_path" value="<?php echo( $_GET['path'] );?>">
			<label><?php i18n( "Confirm Project Deletion" );?></label>
			<pre><?php i18n( "Name:" );?> <?php echo( $_GET['name'] );?>, <?php i18n( "Path:" )?> <?php echo( $_GET['path'] );?></pre>
			<table>
				<tr>
					<td width="5">
						<input type="checkbox" name="delete" id="delete" value="true"></td>
						<td><?php i18n( "Delete Project Files" ); ?></td>
				</tr>
				<tr>
					<td width="5"><input type="checkbox" name="follow" id="follow" value="true"></td>
					<td><?php i18n( "Follow Symbolic Links " );?></td>
				</tr>
			</table>
			<button class="btn-left"><?php i18n( "Confirm" );?></button><button class="btn-right" onclick="codiad.project.list();return false;"><?php i18n( "Cancel" );?></button>
		<?php
	break;
}
?>
