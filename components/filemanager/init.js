/*
 *  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
 *  as-is and without warranty under the MIT License. See
 *  [root]/license.txt for more. This information must remain intact.
 */
( function( global, $ ) {
	
	let codiad = global.codiad;
	
	$( window ).load( function() {
		
		codiad.filemanager.init();
	});
	
	codiad.filemanager = {
		
		auto_reload: false,
		clipboard: '',
		controller: 'components/filemanager/controller.php',
		dialog: 'components/filemanager/dialog.php',
		filelist: {
			
			audio: [
				'aac',
				'aif',
				'mp3',
				'mp4',
				'wav',
				'ogg',
			],
			files: [
				'exe',
				'pdf',
				'zip',
				'tar',
				'tar.gz',
			],
			image: [
				'ico',
				'icon',
				'jpg',
				'jpeg',
				'png',
				'gif',
				'bmp',
			],
		},
		files: [],
		opened_folders: [],
		post_max_size: ( 1024*1024 ),
		preview: null,
		refresh_interval: null,
		selected: [],
		
		init: async function() {
			
			let _this = this;
			
			/* Reload the page when saving auto reload preview */
			amplify.subscribe( 'settings.save', async function() {
				
				let option = ( await codiad.settings.get_option( "codiad.filemanager.autoReloadPreview" ) == "true" );
				if( option != codiad.filemanager.auto_reload ) {
					
					window.location.reload( true );
				}
			});
			
			/* Subscribe to know when a file become active. */
			amplify.subscribe( 'active.onFocus', async function( path ) {
				
				let editor = codiad.editor.getActive();
				
				if( _this.auto_reload && editor !== null ) {
					
					codiad.editor.getActive().addEventListener( "change", _this.refreshPreview );
				}
			});
			
			/* 
				maybe we should have this calcualted as the file is being uploaded.
				this may allow for a more dynamic upload speed for faster
				connections and a more stable upload for slower connections.
			*/
			_this.calculate_upload_variables();
			
			_this.node_listeners();
		},
		
		archive: function( path ) {
			
			let _this = this;
			
			$.get( _this.controller + '?action=archive&path=' + encodeURIComponent( path ), function( data ) {
				
				console.log( data );
				let response = codiad.jsend.parse( data );
				parent = path.split( '/' );
				parent.pop();
				_this.rescan( parent.join( '/' ) );
				console.log( response );
			});
		},
		
		calculate_upload_variables: async function() {
			
			let _this = codiad.filemanager;
			let result = await codiad.system.get_ini_setting( 'post_max_size' );
			result = result.toLowerCase()
			
			console.log( result, result.includes( 'g' ), result.includes( 'm' ), result.includes( 'k' ) );
			
			if( result.includes( 'g' ) ) {
				
				let integer = result.replace( /^\D+/g, '' );
				
				console.log( integer, 1024*1024*1024*integer );
				result = 1024*1024*1024*integer;
			} else if( result.includes( 'm' ) ) {
				
				let integer = result.replace( /^\D+/g, '' );
				console.log( integer, 1024*1024*integer );
				result = 1024*1024*integer;
			} else if( result.includes( 'k' ) ) {
				
				let integer = result.replace( /^\D+/g, '' );
				console.log( integer, 1024*integer );
				result = 1024*integer;
			}
			
			_this.post_max_size = result;
			console.log( _this.post_max_size );
		},
		
		context_menu_track_mouse: function( e ) {
			
			let _this = codiad.filemanager;
			let offset = $( '#context-menu' ).offset();
			let bottom = offset.top + $( '#context-menu' ).outerHeight( true ) + 20;
			let left = offset.left - 20;
			let right = offset.left + $( '#context-menu' ).outerWidth( true ) + 20;
			let top = offset.top - 20;
			
			if( ( e.clientX > right || e.clientX < left ) || ( e.clientY > bottom || e.clientY < top ) ) {
				
				$( '#file-manager, #editor-region' ).off( 'mousemove', codiad.filemanager.context_menu_track_mouse );
				$( '#context-menu, #editor-region' ).off( 'paste', codiad.editor.paste );
				_this.hide_context_menu();
			}
		},
		
		//////////////////////////////////////////////////////////////////
		// Copy to Clipboard
		//////////////////////////////////////////////////////////////////
		
		copy_node: function( path ) {
			
			this.clipboard = path;
			codiad.message.success( i18n( 'Copied to Clipboard' ) );
		},
		
		create_node: function( path, type ) {
			
			codiad.modal.load( 250, this.dialog, {
				action: 'create',
				type: type,
				path: path
			});
			$( '#modal-content form' )
			.on( 'submit', function( e ) {
				
				e.preventDefault();
				let shortName = $( '#modal-content form input[name="object_name"]' ).val();
				let path = $( '#modal-content form input[name="path"]' ).val();
				let type = $( '#modal-content form input[name="type"]' ).val();
				let createPath = path + '/' + shortName;
				
				$.get( codiad.filemanager.controller + '?action=create&path=' + encodeURIComponent( createPath ) + '&type=' + type, function( data ) {
					
					let createResponse = codiad.jsend.parse( data );
					if( createResponse != 'error' ) {
						
						codiad.message.success( type.charAt( 0 )
						.toUpperCase() + type.slice( 1 ) + ' Created' );
						codiad.modal.unload();
						
						codiad.filemanager.rescan( path );
						
						if( type == 'file' ) {
							
							codiad.filemanager.openFile( createPath, true );
						}
						
						/* Notify listeners. */
						amplify.publish( 'filemanager.onCreate', {
							createPath: createPath,
							path: path,
							shortName: shortName,
							type: type
						});
					}
				});
			});
		},
		
		delete_node: function( path ) {
			
			let _this = this;
			codiad.modal.load( 400, this.dialog, {
				action: 'delete',
				path: path
			});
			$( '#modal-content form' )
			.live( 'submit', function( e ) {
				
				e.preventDefault();
				$.get( _this.controller + '?action=delete&path=' + encodeURIComponent( path ), function( data ) {
					
					
					let response = codiad.jsend.parse( data );
					if( response != 'error' ) {
						
						let node = $( '#file-manager a[data-path="' + path + '"]' );
						let parent_path = node.parent().parent().children( 'a' ).attr( 'data-path' );
						node.parent( 'li' ).remove();
						
						// Close any active files
						$( '#active-files a' ).each( function() {
							
							let curPath = $( this ).attr( 'data-path' );
							
							console.log( curPath, curPath.indexOf( path ) );
							
							if( curPath.indexOf( path ) == 0 ) {
								
								codiad.active.remove( curPath );
							}
						});
						
						/* Notify listeners. */
						amplify.publish( 'filemanager.onDelete', {
							deletePath: path,
							path: parent_path
						});
					}
					codiad.modal.unload();
				});
			});
		},
		
		delete_children_nodes: function( path ) {
			
			let _this = this;
			codiad.modal.load( 400, this.dialog, {
				action: 'delete',
				path: path
			});
			$( '#modal-content form' )
			.on( 'submit', function( e ) {
				
				e.preventDefault();
				$.get( _this.controller + '?action=delete_children&path=' + encodeURIComponent( path ), function( data ) {
					
					
					let response = codiad.jsend.parse( data );
					if( response != 'error' ) {
						
						let node = $( '#file-manager a[data-path="' + path + '"]' );
						let parent_path = node.parent().parent().prev().attr( 'data-path' );
						node.parent( 'li' ).remove();
						
						// Close any active files
						$( '#active-files a' ).each( function() {
							
							let curPath = $( this ).attr( 'data-path' );
							
							console.log( curPath, curPath.indexOf( path ) );
							
							if( path.indexOf( curPath ) == 0 ) {
								
								codiad.active.remove( curPath );
							}
						});
						
						/* Notify listeners. */
						amplify.publish( 'filemanager.onDelete', {
							path: path
						});
					}
					codiad.modal.unload();
				});
			});
		},
		
		display_context_menu: function( e, path, type, name ) {
			
			let _this = this;
			let top = e.pageY;
			
			$( '#context-menu a, #context-menu hr' ).hide();
			
			// Selective options
			switch ( type ) {
				
				case 'directory':
					
					$( '#context-menu .directory-only, #context-menu .non-root, #context-menu .both' ).show();
				break;
				
				case 'file':
					
					$( '#context-menu .file-only, #context-menu .non-root, #context-menu .both' ).show();
				break;
				
				case 'root':
					
					$( '#context-menu .directory-only, #context-menu .root-only' ).show();
					$( '#context-menu .non-root' ).hide();
				break;
				
				case 'editor':
					
					$( '#context-menu .editor-only' ).show();
				break;
			}
			
			if( codiad.project.isAbsPath( $( '#file-manager a[data-type="root"]' ).attr( 'data-path' ) ) ) {
				
				$( '#context-menu .no-external' ).hide();
			} else if( type == "editor" ) {
				
				$( '#context-menu .no-external' ).hide();
			} else {
				
				$( '#context-menu .no-external' ).show();
			}
			
			// Show menu
			
			if( top > $( window ).height() - $( '#context-menu' ).height() ) {
				
				top -= $( '#context-menu' ).height();
			}
			
			if( top < 10 ) {
				
				top = 10;
			}
			let max = $( window ).height() - top - 10;
			
			$( '#context-menu' )
			.css( {
				'top': top + 'px',
				'left': e.pageX + 'px',
				'max-height': max + 'px'
			})
			.fadeIn( 200 )
			.attr( 'data-path', path )
			.attr( 'data-type', type )
			.attr( 'data-name', name );
			
			// Show faded 'paste' if nothing in clipboard
			if( this.clipboard === '' ) {
				
				$( '#context-menu a[content="Paste"]' )
				.addClass( 'disabled' );
			} else {
				
				$( '#context-menu a[data-action="paste"]' )
				.removeClass( 'disabled' );
			}
			
			// Hide menu
			/**
			 * make sure that the user has moved their mouse far enough
			 * away from the context menu to warrant a close.
			 */
			$( '#file-manager, #editor-region' ).on( 'mousemove', codiad.filemanager.context_menu_track_mouse );
			$( '#context-menu, #editor-region' ).on( 'paste', codiad.editor.paste );
			$( '#context-menu, #editor-region' ).on( 'click', _this.hide_context_menu );
			
			/* Notify listeners. */
			amplify.publish( 'context-menu.onShow', {
				e: e,
				path: path,
				type: type
			});
			
			// Hide on click
			$( '#context-menu a' ).on( 'click', _this.hide_context_menu );
		},
		
		download: function( path ) {
			
			let type = this.getType( path );
			$( '#download' )
			.attr( 'src', 'components/filemanager/download.php?path=' + encodeURIComponent( path ) + '&type=' + type );
			
		},
		
		get_extension: function( path ) {
			
			return path.split( '.' ).pop();
		},
		
		get_index: function( path, files ) {
			
			let _this = codiad.filemanager;
			
			return new Promise( async function( resolve, reject ) {
				
				let index = {};
				let total = ( !!files ) ? files.length : 0;
				
				for( let i = 0;i < total;i++ ) {
					
					if( path == files[i].dirname ) {
						
						index = files[i];
						break;
					} else {
						
						if( files[i].children !== undefined ) {
							
							index = await _this.get_index( path, files[i].children );
							
							if( Object.keys( index ).length > 0 ) {
								
								break;
							}
						}
					}
				}
				resolve( index );
			});
		},
		
		get_indexes: async function( path ) {
			
			let r = await $.get( this.controller + '?action=index&path=' + encodeURIComponent( path ) );
			return r;
		},
		
		get_opened_indexes: async function( files ) {
			
			let _this = codiad.filemanager;
			
			for( let i = files.length;i--; ) {
				
				files[i].name = files[i].path;
				
				if( files[i].type == "directory" && _this.opened_folders.includes( files[i].path ) ) {
					
					files[i].opened = true;
					
					let data = await _this.get_indexes( files[i].path );
					let response = codiad.jsend.parse( data );
					let children = _this.get_opened_indexes( response );
					_this.set_children( path, children, response );
				}
			}
			return files;
		},
		
		get_short_name: function( path ) {
			
			return path.split( '/' ).pop();
		},
		
		get_type: function( path ) {
			
			if( path.match( /\\/g ) ) {
				
				path = path.replace( '\\', '\\\\' );
			}
			
			return $( '#file-manager a[data-path="' + path + '"]' ).attr( 'data-type' );
		},
		
		hide_context_menu: function() {
			
			$( '#context-menu' ).fadeOut( 200 );
			$( '#file-manager a' ).removeClass( 'context-menu-active' );
			
			/* Notify listeners. */
			amplify.publish( 'context-menu.onHide' );
		},
		
		index: async function( path, rescan = false ) {
			
			let _this = codiad.filemanager;
			
			let children = 0;
			let container = $( '<ul></ul>' );
			let files = [];
			let node = $( '#file-manager a[data-path="' + path + '"]' );
			let parentNode = node.parent();
			let root = false;
			let span = node.prev();
			let total_saved = _this.files.length;
			let file = await _this.get_index( path );
			rescan = !!rescan;
			
			node.addClass( 'loading' );
			
			if( Object.keys( file ).length == 0 ) {
				
				children = file.children;
			}
			
			console.log( file.children, file )
			if( rescan || total_saved == 0 || ! children ) {
				
				let data = await _this.get_indexes( path );
				let response = codiad.jsend.parse( data );
				let result = [];
				
				if( response != 'error' ) {
					
					result = response.index;
				}
				
				if( total_saved == 0 ) {
					
					_this.files = result;
					files = result;
				} else {
					
					_this.set_children( path, _this.files, result );
					files = result;
				}
			} else {
				
				files = file.children;
			}
			
			files = await _this.get_opened_indexes( files );
			_this.index_nodes(
				path,
				node,
				files,
				{},
				{
					directory: [_this.index_directory_callback],
					file: [_this.index_file_callback],
				}
			);
			
			/* Notify listener */
			amplify.publish( "filemanager.onIndex", {
				path: path,
				files: files
			});
			return true;
		},
		
		index_directory_callback: function( entry, container, i, files ) {
			
			let _this = codiad.filemanager;
			entry.children( 'a' ).droppable({
				accept: _this.object_accept,
				drop: _this.object_drop,
				over: _this.object_over,
				out: _this.object_out
			});
		},
		
		index_file_callback: function( entry, container, i, files ) {
			
			let _this = codiad.filemanager;
			entry.draggable({
				
				opacity: 0.85,
				revert: true,
				start: _this.object_start,
				stop: _this.object_stop,
				zIndex: 100
			});
		},
		
		index_nodes: function( path, node, files, filters, callbacks ) {
			
			let container = $( '<ul></ul>' );
			let total_files = files.length;
			
			let link = node.children( 'a' );
			let ul = node.parent( 'li' ).children( 'ul' );
			
			for( let i = 0;i < total_files;i++ ) {
				
				let v = files[i];
				let ext = '';
				let name = '';
				let node_class = 'none';
				let entry = $( "<li></li>" );
				let span = $( "<span></span>" );
				let link = $( "<a></a>" );
				let type = null;
				
				if( v.type == "file" ) {
					
					if( filters.type == "directory" ) {
						
						continue;
					}
					
					ext = "ext-" + v.extension;
					name = v.basename;
					type = 'file';
					link.addClass( ext );
					
				} else if( v.type == "directory" ) {
					
					if( filters.type == "file" ) {
						
						continue;
					}
					
					if( v.children ) {
						
						if( v.open ) {
							
							node_class = "minus";
							_this.index_nodes( v.path, link, v.children, filters, callbacks );
						} else {
							
							node_class = "plus";
						}
					}
					
					name = v.basename;
					type = 'directory';
				}
				
				console.log( v.path, v.type );
				
				span.addClass( node_class );
				link.addClass( type );
				link.attr( "data-type", type );
				link.attr( "data-path", v.path );
				link.text( name );
				
				entry.append( span, link );
				container.append( entry );
				
				if( typeof callbacks == "function" ) {
					
					callbacks( entry, container, v, files );
				} else if( Array.isArray( callbacks ) ) {
					
					let total_callbacks = callbacks.length;
					for( let j = 0;j < total_callbacks;j++ ) {
						
						callbacks[j]( entry, container, v, files );
					}
				} else if( callbacks === Object( callbacks ) ) {
					
					if( typeof callbacks[v.type] == "function" ) {
						
						callbacks[v.type]( entry, container, v, files );
					} else if( Array.isArray( callbacks[v.type] ) ) {
						
						let total_callbacks = callbacks[v.type].length;
						for( let j = 0;j < total_callbacks;j++ ) {
							
							callbacks[v.type][j]( entry, container, v, files );
						}
					}
				}
			}
			
			if( ul.length ) {
				
				ul.replaceWith( container );
			} else {
				
				container.insertAfter( node );
			}
			
			node.removeClass( 'loading' );
		},
		
		is_child: function( parent, child ) {
			
			if( child === parent ) {
				
				return false;
			}
			
			let parentTokens = parent.split( '/' ).filter( i => i.length );
			return parentTokens.every( ( t, i ) => child.split( '/' )[i] === t )
		},
		
		node_listeners: function() {
			
			let _this = this;
			
			$( '#file-manager' )
			.on( 'click', 'a', function() {
				
				// Open or Expand
				if( codiad.editor.settings.fileManagerTrigger ) {
					
					if( $( this ).hasClass( 'directory' ) ) {
						
						_this.toggle_directory( $( this ).attr( 'data-path' ) );
					} else {
						
						_this.open_file( $( this ).attr( 'data-path' ) );
					}
				}
			})
			.on( 'click', 'span', function() {
				
				// Open or Expand
				if( $( this ).parent().children( "a" ).attr( 'data-type' ) == 'directory' ) {
					
					_this.toggle_directory( $( this ).parent().children( "a" ).attr( 'data-path' ) );
				} else {
					
					_this.openFile( $( this ).parent().children( "a" ).attr( 'data-path' ) );
				}
			})
			.on( "contextmenu", 'a', function( e ) {
				
				// Context Menu
				e.preventDefault();
				_this.display_context_menu(
					e,
					$( this ).attr( 'data-path' ),
					$( this ).attr( 'data-type' ),
					$( this ).html()
				);
				$( this ).addClass( 'context-menu-active' );
			})
			.on( 'dblclick', 'a', function() {
				
				// Open or Expand
				if( ! codiad.editor.settings.fileManagerTrigger ) {
					
					if( $( this ).hasClass( 'directory' ) ) {
						
						_this.toggle_directory( $( this ).attr( 'data-path' ) );
					} else {
						
						_this.open_file( $( this ).attr( 'data-path' ) );
					}
				}
			})
			.on( 'selectstart', false );
		},
		
		open_file: function( path, focus = true ) {
			
			/* Notify listeners. */
			amplify.publish( 'filemanager.onFileWillOpen', {
				path: path
			});
			
			let _this = codiad.filemanager;
			let node = $( '#file-manager a[data-path="' + path + '"]' );
			let ext = _this.get_extension( path );
			
			if( $.inArray( ext.toLowerCase(), _this.noOpen ) < 0 ) {
				
				node.addClass( 'loading' );
				$.get( _this.controller + '?action=open&path=' + encodeURIComponent( path ), function( data ) {
					
					let openResponse = codiad.jsend.parse( data );
					if( openResponse != 'error' ) {
						
						node.removeClass( 'loading' );
						codiad.active.open( path, openResponse.content, openResponse.mtime, false, focus, openResponse.read_only );
					}
				});
			} else {
				
				if( ! codiad.project.isAbsPath( path ) ) {
					
					if( $.inArray( ext.toLowerCase(), _this.noBrowser ) < 0 ) {
						
						_this.download( path );
					} else {
						
						_this.openInModal( path );
					}
				} else {
					
					codiad.message.error( i18n( 'Unable to open file in Browser while using absolute path.' ) );
				}
			}
		},
		
		rename: function( path, new_path ) {
			
			return new Promise( function( resolve, reject ) {
				
				$.ajax({
					type: 'POST',
					url: _this.controller + '?action=rename',
					data: {
						
						path: path,
						destination: newPath
					},
					success: function( data ) {
						
						resolve( data );
					},
					error: function( data ) {
						
						reject( data );
					},
				});
			});
		},
		
		rename_node: function( path, new_path ) {
			
			let shortName = this.get_short_name( path );
			let type = this.getType( path );
			let _this = this;
			let project = codiad.project.getCurrent();
			
			codiad.modal.load( 250, this.dialog, {
				action: 'rename',
				path: path,
				short_name: shortName,
				type: type
			});
			
			$( '#modal-content form' )
			.on( 'submit', async function( e ) {
				
				e.preventDefault();
				let arr = path.split( '/' );
				let message = "Successfully Renamed."
				let newName = $( '#modal-content form input[name="object_name"]' ).val();
				let newParent = newPath.split( '/' );
				let parent = path.split( '/' );
				let temp = [];
				
				for( i = 0; i < arr.length - 1; i++ ) {
					temp.push( arr[i] )
				}
				
				
				let newPath = temp.join( '/' ) + '/' + newName;
				let result = codiad.jsend.parse( await _this.rename( path, newPath ) );
				codiad.modal.unload();
				
				if( result != 'error' ) {
					
					if( type !== undefined ) {
						
						let node = $( '#file-manager a[data-path="' + path + '"]' );
						
						
						node.attr( 'data-path', newPath ).html( newPath.split( "/" ).pop() );
						message = type.charAt( 0 ).toUpperCase() + type.slice( 1 ) + ' Renamed'
						codiad.message.success( message );
						
						// Change icons for file
						let current_class = 'ext-' + _this.get_extension( path );
						let new_class = 'ext-' + _this.get_extension( newPath );
						
						$( '#file-manager a[data-path="' + newPath + '"]' )
						.removeClass( current_class )
						.addClass( new_class );
						codiad.active.rename( path, newPath );
						
						parent = parent.pop();
						newParent = newParent.pop();
						
						codiad.filemanager.rescan( parent.join( '/' ) );
						codiad.filemanager.rescan( newParent.join( '/' ) );
						
						/* Notify listeners. */
						amplify.publish( 'filemanager.onRename', {
							path: path,
							newPath: newPath,
							project: project
						});
					}
				}
			});
		},
		
		preview_path: function( path ) {
			
			
		},
		
		rescan: function( path ) {
			
			let _this = codiad.filemanager;
			_this.index( path, true );
		},
		
		save_file: function() {},
		
		save_modifications: function() {},
		
		save_patch: function() {},
		
		set_children: function( path, files, children ) {
			
			let _this = this;
			let index = {};
			let total = ( !!files ) ? files.length : 0;
			
			for( let i = 0;i < total;i++ ) {
				
				if( path == files[i].dirname ) {
					
					files[i].children = children;
					index = files[i];
					break;
				} else {
					
					if( files[i].children !== undefined ) {
						
						index = _this.set_children( path, files[i].children, children );
						
						if( Object.keys( index ).length > 0 ) {
							
							break;
						}
					}
				}
			}
			return index;
		},
		
		set_index: function( path, files, data ) {
			
			let _this = codiad.filemanager;
			let index = false;
			let total = ( !!files ) ? files.length : 0;
			
			for( let i = 0;i < total;i++ ) {
				
				if( path == files[i].dirname ) {
					
					files[i] = data;
					index = files[i];
					break;
				} else {
					
					if( files[i].children !== undefined ) {
						
						index = _this.set_index( path, files[i].children, data );
						
						if( index ) {
							
							break;
						}
					}
				}
			}
			return index;
		},
		
		toggle_directory: async function( path, open_callback, close_callback ) {
			
			let _this = codiad.filemanager;
			let node = $( '#file-manager a[data-path="' + path + '"]' );
			
			node.addClass( "loading" );
			
			let i = await _this.get_index( path, _this.files );
			let span = node.parent().children( 'span' );
			let link = node.parent().children( 'a' );
			
			console.log( i );
			
			if( Object.keys( i ).length == 0 ) {
				
				let result = await _this.index( path );
				i = {
					open: false,
				}
			}
			
			if( i.open ) {
				
				node.parent().children( 'ul' )
				.slideUp( 300, function() {
					
					$( this ).remove();
					
					span.removeClass( 'minus' );
					node.removeClass( 'open' );
					
					span.addClass( 'plus' );
					
					if( typeof close_callback == "function" ) {
						
						close_callback();
					}
				});
				
				if( typeof open_callback == "function" ) {
					
					close_callback( node );
				}
			} else {
				
				span.removeClass( 'plus' );
				
				span.addClass( 'minus' );
				link.addClass( 'open' );
				
				_this.index( path );
			}
			
			i.open = !i.open
			_this.set_index( path, _this.files, i );
			
			console.log( i, await _this.get_index( path, _this.files ) );
			node.removeClass( "loading" );
		},
		
		unarchive: function( path ) {
			
			let _this = this;
			
			$.get( _this.controller + '?action=unarchive&path=' + encodeURIComponent( path ), function( data ) {
				
				console.log( data );
				let response = codiad.jsend.parse( data );
				console.log( response );
				parent = path.split( '/' );
				parent.pop();
				_this.rescan( parent.join( '/' ) );
			});
		},
		
		upload: function() {},
		
		//Compatibility functions
		
		copyNode: this.copy_node,
		createNode: function( path, type ) {return this.create_node( path, type )},
		deleteNode: this.delete_node,
		getExtension: function( path ) {return this.create_node( path )},
		getShortName: function( path ) {return this.get_short_name( path )},
		getType: function( path ) {return this.get_type( path )},
		openFile: function( path ) {return this.open_file( path )},
		openInBrowser: this.preview,
		pasteNode: this.paste_node,
		renameNode: this.rename_node,
		saveFile: this.save_file,
	};
})( this, jQuery );