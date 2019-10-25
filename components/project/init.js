/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

( function( global, $ ) {
	
	var codiad = global.codiad;
	
	$( function() {
		
		codiad.project.init();
	});
	
	codiad.project = {
		
		controller: 'components/project/controller.php',
		dialog: 'components/project/dialog.php',
		
		init: function() {
			
			this.loadCurrent();
			this.loadSide();
			
			var _this = this;
			
			$( '#projects-create' ).click( function() {
				
				codiad.project.create('true');
			});
			
			$( '#projects-manage' ).click( function() {
				
				codiad.project.list();
			});
			
			$('#projects-collapse').click( function() {
				
				if ( ! _this._sideExpanded ) {
					
					_this.projectsExpand();
				} else {
					
					_this.projectsCollapse();
				}
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Add user access
		//////////////////////////////////////////////////////////////////
		
		add_user: function() {
			
			let _this = this;
			let id = $( '#modal-content form select[name="user_list"]' ).val();
			let project_path = $( '#modal-content form input[name="project_path"]' ).val();
			let project_id = $( '#modal-content form input[name="project_id"]' ).val();
			
			$.get( _this.controller + '?action=add_user&project_path=' + encodeURIComponent( project_path ) + '&project_id=' + encodeURIComponent( project_id ) + '&user_id=' + encodeURIComponent( id ) + '&access=delete', function( data ) {
				
				response = codiad.jsend.parse( data );
				console.log( response );
				if ( response != 'error' ) {
					
					codiad.project.manage_access( project_path );
				}
			});
		},
		
		change_access: function( e ) {
			
			let _this = codiad.project;
			let id = $( '#modal-content form select[name="user_list"]' ).val();
			let project_path = $( '#modal-content form input[name="project_path"]' ).val();
			let project_id = $( '#modal-content form input[name="project_id"]' ).val();
			let access = $( e.target ).children( "option:selected" ).val();
			
			console.log( access, id, project_path, project_id );
			
			$.get( _this.controller + '?action=add_user&project_path=' + encodeURIComponent( project_path ) + '&id=' + encodeURIComponent( project_id ) + '&user_id=' + encodeURIComponent( id ) + '&access=' + encodeURIComponent( access ), function( data ) {
				
				let response = codiad.jsend.parse( data );
				console.log( response );
				if ( response != 'error' ) {
					
					codiad.project.manage_access( project_path );
				}
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Create Project
		//////////////////////////////////////////////////////////////////
		
		create: function( close ) {
			
			var _this = this;
			create = true;
			codiad.modal.load( 500, this.dialog + '?action=create&close=' + close );
			$( '#modal-content form' )
			.live( 'submit', function( e ) {
				
				e.preventDefault();
				var projectName = $( '#modal-content form input[name="project_name"]' )
				.val(),
				projectPath = $( '#modal-content form input[name="project_path"]' )
				.val(),
				gitRepo = $( '#modal-content form input[name="git_repo"]' )
				.val(),
				gitBranch = $( '#modal-content form input[name="git_branch"]' )
				.val();
				public_project = $( '#modal-content form select[name="public_project"]' )
				.val();
				if( projectPath.indexOf( '/' ) == 0 ) {
					
					create = confirm( 'Do you really want to create project with absolute path "' + projectPath + '"?' );
				}
				if( create ) {
					
					$.get( _this.controller + '?action=create&project_name=' + encodeURIComponent( projectName ) + '&project_path=' + encodeURIComponent( projectPath ) + '&git_repo=' + gitRepo + '&git_branch=' + gitBranch + '&public_project=' + public_project, function( data ) {
						
						createResponse = codiad.jsend.parse( data );
						if ( createResponse != 'error' ) {
							
							_this.open( createResponse.path );
							codiad.modal.unload();
							_this.loadSide();
							/* Notify listeners. */
							amplify.publish( 'project.onCreate', {"name": projectName, "path": projectPath, "git_repo": gitRepo, "git_branch": gitBranch} );
						}
					});
				}
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Delete Project
		//////////////////////////////////////////////////////////////////
		
		delete: function( name, path ) {
			
			var _this = this;
			codiad.modal.load( 500, this.dialog + '?action=delete&name=' + encodeURIComponent( name ) + '&path=' + encodeURIComponent( path ) );
			$( '#modal-content form' )
			.live( 'submit', function( e ) {
				
				e.preventDefault();
				var projectPath = $( '#modal-content form input[name="project_path"]' )
				.val();
				var deletefiles = $( 'input:checkbox[name="delete"]:checked' ).val();
				var followlinks = $( 'input:checkbox[name="follow"]:checked' ).val();
				var action = '?action=delete';
				if( typeof deletefiles !== 'undefined' ) {
					
					if( typeof followlinks !== 'undefined' ) {
						
						action += '&follow=true&path=' + encodeURIComponent( projectPath );
					} else {
						
						action += '&path=' + encodeURIComponent( projectPath );
					}
				}
				$.get(codiad.filemanager.controller + action, function( d ) {
					
					$.get(_this.controller + '?action=delete&project_path=' + encodeURIComponent( projectPath ), function( data ) {
						
						deleteResponse = codiad.jsend.parse( data );
						if ( deleteResponse != 'error' ) {
							
							codiad.message.success( i18n( 'Project Deleted' ) );
							_this.list();
							_this.loadSide();
							// Remove any active files that may be open
							$( '#active-files a' )
							.each(function() {
								
								var curPath = $( this )
								.attr( 'data-path' );
								if ( curPath.indexOf( projectPath ) == 0 ) {
									
									codiad.active.remove( curPath );
								}
							});
							/* Notify listeners. */
							amplify.publish( 'project.onDelete', {"path": projectPath, "name": name} );
						}
					});
				});
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Get Access
		//////////////////////////////////////////////////////////////////
		
		get_access: function( path, generate_table = false ) {
			
			var _this = this;
			$.get( _this.controller + '?action=get_access&project_path=' + encodeURIComponent( path ), function( data ) {
					
				return codiad.jsend.parse( data );
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Get Current (Path)
		//////////////////////////////////////////////////////////////////
		
		getCurrent: function() {
			
			var _this = this;
			var currentResponse = null;
			$.ajax({
				
				url: _this.controller + '?action=current',
				async: false,
				success: function( data ) {
					
					currentResponse = codiad.jsend.parse( data );
				} 
			});
			return currentResponse;
		},
		
		//////////////////////////////////////////////////////////////////
		// Check Absolute Path
		//////////////////////////////////////////////////////////////////
		
		isAbsPath: function( path ) {
			
			if ( path.indexOf( "/" ) == 0 ) {
				
				return true;
			} else {
				
				return false;
			}
		},
		
		//////////////////////////////////////////////////////////////////
		// Open the project manager dialog
		//////////////////////////////////////////////////////////////////
		
		list: function() {
			
			$( '#modal-content form' ).die( 'submit' ); // Prevent form bubbling
			codiad.modal.load( 500, this.dialog + '?action=list' );
		},
		
		list_all: function() {
			
			$( '#modal-content form' ).die( 'submit' ); // Prevent form bubbling
			codiad.modal.load( 500, this.dialog + '?action=list&all=true' );
		},
		
		/**
		 * Turn the access array into a table.
		 */
		load_access: function() {
			
			var _this = this;
			var access = _this.get_access();
			
			//If the access is not null then build a table from the data.
			if( access !== '' ) {
				
				access = JSON.parse( access );
			}
		},
		
		//////////////////////////////////////////////////////////////////
		// Get Current Project
		//////////////////////////////////////////////////////////////////
		
		loadCurrent: function() {
			
			$.get( this.controller + '?action=get_current', function( data ) {
				
				var projectInfo = codiad.jsend.parse( data );
				
				if ( projectInfo != 'error' ) {
					
					$( '#file-manager' )
					.html( '' )
					.append( '<ul><li><a id="project-root" data-type="root" class="directory" data-path="' + projectInfo.path + '">' + projectInfo.name + '</a></li></ul>' );
					codiad.filemanager.index( projectInfo.path );
					codiad.user.project( projectInfo.path );
					codiad.message.success( i18n( 'Project %{projectName}% Loaded', {projectName:projectInfo.name} ) );
				}
			});
		},
		
		
		//////////////////////////////////////////////////////////////////
		// Load and list projects in the sidebar.
		//////////////////////////////////////////////////////////////////
		loadSide: async function() {
			
			this._sideExpanded = ( await codiad.settings.get_option( "codiad.projects.sideExpanded" ) == "true" );
			$( '.sb-projects-content' ).load( this.dialog + '?action=sidelist&trigger='+ await codiad.settings.get_option( 'codiad.editor.fileManagerTrigger' ) );
			
			if ( ! this._sideExpanded ) {
				
				this.projectsCollapse();
			}
		},
		
		//////////////////////////////////////////////////////////////////
		// Manage access
		//////////////////////////////////////////////////////////////////
		
		manage_access: function( path ) {
			
			var _this = this;
			
			$( '#modal-content form' )
			.die( 'submit' ); // Prevent form bubbling
			codiad.modal.load( 500, this.dialog + '?action=manage_access&path=' + path );
		},
		
		//////////////////////////////////////////////////////////////////
		// Open Project
		//////////////////////////////////////////////////////////////////
		
		open: function( path ) {
			
			var _this = this;
			codiad.finder.contractFinder();
			$.get( this.controller + '?action=open&path=' + encodeURIComponent( path ), function( data ) {
				
				console.log( data );
				var projectInfo = codiad.jsend.parse(data);
				if ( projectInfo != 'error' ) {
					
					_this.loadCurrent();
					codiad.modal.unload();
					codiad.user.project( path );
					localStorage.removeItem( "lastSearched" );
					/* Notify listeners. */
					amplify.publish( 'project.onOpen', path );
				}
			});
		},
		
		projectsExpand: function() {
			
			this._sideExpanded = true;
			codiad.settings.update_option( 'codiad.projects.sideExpanded', this._sideExpanded );
			$( '#side-projects' ).css( 'height', 276 + 'px' );
			$( '.project-list-title' ).css( 'right', 0 );
			$( '.sb-left-content' ).css( 'bottom', 276 + 'px' );
			$( '#projects-collapse' )
			.removeClass( 'icon-up-dir' )
			.addClass( 'icon-down-dir' );
		},
		
		projectsCollapse: function() {
			
			this._sideExpanded = false;
			codiad.settings.update_option( 'codiad.projects.sideExpanded', this._sideExpanded );
			$( '#side-projects' ).css( 'height', 33 + 'px' );
			$( '.project-list-title' ).css( 'right', 0 );
			$( '.sb-left-content' ).css( 'bottom', 33 + 'px' );
			$( '#projects-collapse' )
			.removeClass( 'icon-down-dir' )
			.addClass( 'icon-up-dir' );
		},
		
		//////////////////////////////////////////////////////////////////
		// Remove User access
		//////////////////////////////////////////////////////////////////
		
		remove_user: function( user ) {
			
			var _this = this;
			
			let project_path = $( '#modal-content form input[name="project_path"]' ).val();
			let project_id = $( '#modal-content form input[name="project_id"]' ).val();
			
			$.get( _this.controller + '?action=remove_user&project_path=' + encodeURIComponent( project_path ) + '&project_id=' + encodeURIComponent( project_id ) + '&username=' + encodeURIComponent( user ), function( data ) {
					
					response = codiad.jsend.parse( data );
					console.log( response );
					if ( response != 'error' ) {
						
						codiad.project.manage_access( project_path );
					}
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Rename Project
		//////////////////////////////////////////////////////////////////
		
		rename: function( path, name ) {
			
			var _this = this;
			codiad.modal.load( 500, this.dialog + '?action=rename&path=' + encodeURIComponent( path ) + '&name=' + name );
			$( '#modal-content form' )
			.live( 'submit', function( e ) {
				e.preventDefault();
				var projectPath = $( '#modal-content form input[name="project_path"]' )
				.val();
				var projectName = $( '#modal-content form input[name="project_name"]' )
				.val();    
				$.get( _this.controller + '?action=rename&project_path=' + encodeURIComponent( projectPath ) + '&project_name=' + encodeURIComponent( projectName ), function( data ) {
					
					renameResponse = codiad.jsend.parse( data );
					if ( renameResponse != 'error' ) {
						
						codiad.message.success( i18n( 'Project renamed' ) );
						_this.loadSide();
						$( '#file-manager a[data-type="root"]' ).html( projectName );
						codiad.modal.unload();
						/* Notify listeners. */
						amplify.publish( 'project.onRename', {"path": projectPath, "name": projectName} );
					}
				});
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Save User access
		//////////////////////////////////////////////////////////////////
		
		save_access: function() {
			
			$( '#modal-content form' ).live( 'submit', function( e ) {
			
				e.preventDefault();
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Search Users
		//////////////////////////////////////////////////////////////////
		
		search_users: function() {
			
			var _this = this;
			var current_response = null;
			var select_list = document.getElementById( 'user_list' );
			var search_box = document.getElementById( 'search_users' );
			var search_term = search_box.value;
			$.ajax({
				
				url: codiad.user.controller + '?action=search_users&search_term=' + search_term,
				async: false,
				success: function( data ) {
					
					console.log( data );
					current_response = codiad.jsend.parse( data );
				} 
			});
			
			select_list.innerHTML = ``;
			
			if ( current_response != 'error' ) {
			
				for( let i = current_response.length; i--; ) {
				
					let optionElement = document.createElement( 'option' );
					optionElement.innerText = current_response[i].username;
					select_list.appendChild( optionElement );
				}
			}
		},
	};
})( this, jQuery );
