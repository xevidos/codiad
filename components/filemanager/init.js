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
		file_preview_list: {
			
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
		node: {
			
			accept: function( e, i ) {
				
				return true;
			},
			
			drag: function( e, i ) {
				
			},
			
			drop: function( e, i ) {
				
				let _this = codiad.filemanager;
				let drag = i.helper[0];
				let drop = e.target;
				
				$( drop ).removeClass( "drag_over" );
				
				if( ! $( drop ).attr( "data-path" ) ) {
					
					drop = $( drop ).children( 'a' );
				}
				
				console.log( drop );
				console.log( drag );
				
				let drop_path = $( drop ).attr( "data-path" );
				let drag_path = $( drag ).children( "a" ).attr( "data-path" );
				let path = drag_path;
				let newPath = `${drop_path}/` + path.split( "/" ).pop();
				
				console.log( path, newPath );
				_this.rename_node( path, newPath );
			},
			
			out: function( e, i ) {
				
				let drag = i.helper[0];
				let drop = e.target;
				
				$( drop ).removeClass( "drag_over" );
			},
			
			over: function( e, i ) {
				
				let drag = i.helper[0];
				let drop = e.target;
				
				$( drop ).addClass( "drag_over" );
			},
			
			start: function( e, i ) {
				
				let drag = i.helper[0];
				$( drag ).addClass( "drag_start" );
				$( drag ).children( 'a' ).removeClass( "a:hover" );
			},
			
			stop: function( e, i ) {
				
				let drag = i.helper[0];
				$( drag ).removeClass( "drag_start" );
				//$( drag ).removeClass( "hover" );
			},
		},
		opened_folders: [],
		post_max_size: ( 1024*1024 ),
		preview_window: null,
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
					
					codiad.editor.getActive().addEventListener( "change", _this.refresh_preview );
				}
			});
			
			/* 
				maybe we should have this calcualted as the file is being uploaded.
				this may allow for a more dynamic upload speed for faster
				connections and a more stable upload for slower connections.
			*/
			_this.calculate_upload_variables();
			_this.node_listeners();
			
			$( document ).on( 'dragenter', function( e ) {
				
				console.log( e );
				console.log( e.originalEvent.dataTransfer );
			});
			
			
			$( document ).on( 'drag dragstart dragend dragover dragenter dragleave drop', function( e ) {
				
				//e.preventDefault();
				//e.stopPropagation();
				console.log( 'drag dragstart dragend dragover dragenter dragleave drop', e );
				console.log( e.originalEvent.dataTransfer );
			})
			.on( 'dragover dragenter', function( e ) {
				
				console.log( 'dragover dragenter', e );
				console.log( e.originalEvent.dataTransfer );
			})
			.on( 'dragleave dragend drop', function( e ) {
				
				//$( '.drop-overlay' ).css( 'display', 'none' );
				console.log( 'dragleave dragend drop', e );
				console.log( e.originalEvent.dataTransfer );
			})
			.on( 'drop', function( e ) {
				
				//e.preventDefault();
				//e.stopPropagation();
				//codiad.filemanager.upload_drop( e );
				console.log( 'drop', e );
				console.log( e.originalEvent.dataTransfer );
			});
			
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
			})
			.then( function( container ) {
				
				$( '#modal-content form' )
				.on( 'submit', function( e ) {
					
					e.preventDefault();
					e.stopPropagation();
					
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
							
							if( type == 'file' ) {
								
								codiad.filemanager.openFile( createPath, true );
							}
							codiad.filemanager.rescan( path );
							
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
			});
		},
		
		delete_node: function( path ) {
			
			let _this = this;
			codiad.modal.load( 400, this.dialog, {
				action: 'delete',
				path: path
			})
			.then( function( container ) {
				
				$( '#modal-content form' )
				.on( 'submit', function( e ) {
					
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
			})
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
			
			if( ! files ) {
				
				files = _this.files;
			}
			
			return new Promise( async function( resolve, reject ) {
				
				let index = {};
				let total = ( !!files ) ? files.length : 0;
				
				for( let i = 0;i < total;i++ ) {
					
					if( path == files[i].dirname ) {
						
						index = files[i];
						break;
					} else {
						
						if( files[i].children !== undefined && files[i].children !== null ) {
							
							console.log( path );
							console.log( files[i] );
							
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
				
				if( files[i].type == "directory" ) {
					
					let existing_data = await _this.get_index( files[i].path );
					
					console.log( "opened?", existing_data, files[i] );
					
					if( existing_data.open ) {
						
						files[i].open = true;
						let data = await _this.get_indexes( files[i].path );
						let response = codiad.jsend.parse( data );
						_this.set_children( files[i].path, files, response.index );
						
						let children = await _this.get_opened_indexes( response.index );
					} else {
						
						files[i].open = false;
					}
				}
			}
			return files;
		},
		
		get_parent: function( path ) {
			
			let parent = path.split( '/' );
			parent.pop();
			return parent.join( '/' );
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
		
		index: async function( path, rescan = false, node = null, filters = {}, callbacks = {} ) {
			
			let _this = codiad.filemanager;
			let children = 0;
			let container = $( '<ul></ul>' );
			let files = [];
			let root = false;
			let total_saved = _this.files.length;
			let file = await _this.get_index( path );
			rescan = !!rescan;
			
			if( node === null ) {
				
				node = $( '#file-manager a[data-path="' + path + '"]' );
			} else if( node == 'create' ) {
				
				node = $( "<a></a>" )
				.attr( "data-path", path )
				.attr( "data-type", "directory" );
			}
			
			let parentNode = node.parent();
			let span = node.prev();
			
			if( node.attr( 'data-type' ) == "root" ) {
				
				node.droppable({
					accept: _this.node.accept,
					drop: _this.node.drop,
					over: _this.node.over,
					out: _this.node.out
				});
			}
			
			if( ! callbacks.directory ) {
				
				callbacks.directory = [_this.index_directory_callback];
			} 
			
			if( ! callbacks.file ) {
				
				callbacks.file = [_this.index_file_callback];
			}
			
			node.addClass( 'loading' );
			
			if( Object.keys( file ).length == 0 ) {
				
				children = file.children;
			}
			
			console.log( file.children, file )
			if( rescan || total_saved == 0 || ! children ) {
				
				let data = await _this.get_indexes( path );
				
				console.log( data );
				
				let response = codiad.jsend.parse( data );
				let result = [];
				
				if( response != 'error' ) {
					
					result = response.index;
				}
				
				files = result;
			} else {
				
				files = file.children;
			}
			
			files = await _this.get_opened_indexes( files );
			
			if( total_saved == 0 ) {
				
				_this.files = files;
			} else {
				
				_this.set_children( path, _this.files, files );
			}
			
			console.log( _this.files, files )
			
			_this.index_nodes(
				path,
				node,
				files,
				filters,
				callbacks,
			);
			
			/* Notify listener */
			amplify.publish( "filemanager.onIndex", {
				path: path,
				files: files
			});
			return files;
		},
		
		index_directory_callback: function( entry, container, i, files ) {
			
			let _this = codiad.filemanager;
			entry.children( 'a' ).droppable({
				accept: _this.node.accept,
				drop: _this.node.drop,
				over: _this.node.over,
				out: _this.node.out,
				
				start: _this.node.start,
				stop: _this.node.stop,
			});
			entry.draggable({
				opacity: 0.85,
				revert: true,
				start: _this.node.start,
				stop: _this.node.stop,
				zIndex: 100,
			});
		},
		
		index_file_callback: function( entry, container, i, files ) {
			
			let _this = codiad.filemanager;
			entry.draggable({
				opacity: 0.85,
				revert: true,
				start: _this.node.start,
				stop: _this.node.stop,
				zIndex: 100,
			});
		},
		
		index_nodes: function( path, node, files, filters, callbacks ) {
			
			let _this = codiad.filemanager;
			let container = $( '<ul></ul>' );
			let total_files = files.length;
			let parent = node.parent();
			let ul = parent.children( 'ul' );
			
			for( let i = 0;i < total_files;i++ ) {
				
				let v = files[i];
				let ext = '';
				let name = '';
				let node_class = 'none';
				let entry = $( "<li></li>" );
				let span = $( "<span></span>" );
				let link = $( "<a></a>" );
				let type = null;
				
				entry.append( span, link );
				
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
							console.log( "loading children", v.path, link, v.children, filters, callbacks, link.parent() );
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
						
						_this.toggle_directory( $( this ) );
					} else {
						
						_this.open_file( $( this ).attr( 'data-path' ) );
					}
				}
			})
			.on( 'click', 'span', function() {
				
				// Open or Expand
				if( $( this ).parent().children( "a" ).attr( 'data-type' ) == 'directory' ) {
					
					_this.toggle_directory( $( this ).parent().children( "a" ) );
				} else {
					
					_this.open_file( $( this ).parent().children( "a" ).attr( 'data-path' ) );
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
						
						_this.toggle_directory( $( this ) );
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
			let preview = [];
			
			$.each( _this.file_preview_list, function( id, value ) {
				
				preview.concat( value );
			});
			
			console.log( ext, preview );
			
			if( $.inArray( ext.toLowerCase(), preview ) < 0 ) {
				
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
					
					let download = [];
					download.concat( files );
					
					if( $.inArray( ext.toLowerCase(), download ) < 0 ) {
						
						_this.download( path );
					} else {
						
						_this.open_in_modal( path );
					}
				} else {
					
					codiad.message.error( i18n( 'Unable to open file in Browser while using absolute path.' ) );
				}
			}
		},
		
		open_file_selector: function( path, filters = {}, limit = 1, callbacks ) {
			
			let _this = this;
			return new Promise( function( resolve, reject ) {
				
				codiad.modal.load(
					300,
					_this.dialog,
					{
						action: 'selector',
					}
				)
				.then( async function( container ) {
					
					let _this = codiad.filemanager;
					let div = $( "<div></div>" );
					let select = $( '<button class="btn-left">Select</button>' );
					let cancel = $( '<button class="btn-right">Cancel</button>' );
					
					if( ! path ) {
						
						path = codiad.project.getCurrent();
					}
					
					if( Object.keys( filters ).length == 0 ) {
						
						filters = {
							
							type: 'directory',
						}
					}
					
					_this.selector_listeners( container, filters, callbacks );
					
					let result = await _this.index(
						path,
						false,
						"create",
						filters,
						directory_callbacks,
						file_callbacks,
					);
					
					container.html( div );
					container.append( select );
					container.append( cancel );
					
					select.on( 'click', function( e ) {
						
						codiad.modal.unload();
						resolve( _this.selected );
					});
					cancel.on( 'click', function( e ) {
						
						codiad.modal.unload();
						reject({
							status: "error",
							message: "User canceled action."
						});
					});
				})
				.error( reject );
			});
		},
		
		open_in_modal: function( path ) {
			
			let type = "";
			let ext = this.getExtension( path ).toLowerCase();
			
			if( this.file_preview_list.images.includes( ext ) ) {
				
				type = 'music_preview';
			} else if( this.file_preview_list.images.includes( ext ) ) {
				
				type = 'preview';
			}
			
			codiad.modal.load( 250, this.dialog, {
				action: type,
				path: path
			});
		},
		
		open_rename: function( path ) {
			
			let _this = codiad.filemanager;
			let shortName = this.get_short_name( path );
			let type = this.getType( path );
			
			codiad.modal.load( 250, this.dialog, {
				action: 'rename',
				path: path,
				short_name: shortName,
				type: type
			})
			.then( function( content ) {
				
				$( content ).find( 'form' )
				.on( 'submit', function( e ) {
					
					e.preventDefault();
					
					let new_name = $( '#modal-content form input[name="object_name"]' ).val();
					let arr = path.split( '/' );
					let temp = new Array();
					for( i = 0; i < arr.length - 1; i++ ) {
						temp.push( arr[i] )
					}
					let new_path = temp.join( '/' ) + '/' + new_name;
					_this.rename_node( path, new_path );
					codiad.modal.unload();
				});
			});
		},
		
		paste_node: function( path ) {
			
			let _this = this;
			let replace = false;
			let clipboard = this.clipboard;
			console.log( path, clipboard )
			
			if( clipboard == '' ) {
				
				codiad.message.error( i18n( 'Nothing in Your Clipboard' ) );
			} else if( path == clipboard ) {
				
				codiad.message.error( i18n( 'Cannot Paste Directory Into Itself' ) );
			} else {
				
				let short_name = _this.get_short_name( clipboard );
				let new_path = path + '/' + short_name
				let existing_node = $( '#file-manager a[data-path="' + new_path + '"]' );
				
				console.log( existing_node );
				
				if( existing_node.length ) {
					
					// Confirm replace?
					codiad.modal.load( 400, this.dialog, {
						action: 'replace',
						path: new_path
					})
					.then( function( container ) {
						
						$( '#modal-content form' )
						.on( 'submit', function( e ) {
							
							e.preventDefault();
							codiad.modal.unload();
							
							if( $( '#modal-content form select[name="or_action"]' ).val() == 1 ) {
								
								replace = true;
							}
							
							$.ajax({
								type: 'POST',
								url: _this.controller + '?action=copy',
								data: {
									
									path: clipboard,
									destination: new_path,
									replace: replace,
								},
								success: async function( data ) {
									
									
									amplify.publish( 'filemanager.onPaste', {
										path: path,
										shortName: shortName,
										duplicate: duplicate
									});
									
									let dir = await _this.is_dir( new_path );
									
									if( dir ) {
										
										_this.rescan( new_path );
									} else {
										
										_this.rescan( _this.get_parent( new_path ) );
									}
									
									if( path !== new_path ) {
										
										_this.rescan( path );
									}
								},
								error: function( data ) {
									
									console.log( data );
								},
							});
						});
					});
				} else {
					
					$.ajax({
						type: 'POST',
						url: _this.controller + '?action=copy',
						data: {
							
							path: clipboard,
							destination: new_path,
							replace: replace,
						},
						success: async function( data ) {
							
							
							amplify.publish( 'filemanager.onPaste', {
								path: path,
								shortName: shortName,
								duplicate: duplicate
							});
							
							let dir = await _this.is_dir( new_path );
							
							if( dir ) {
								
								_this.rescan( new_path );
							} else {
								
								_this.rescan( _this.get_parent( new_path ) );
							}
							
							if( path !== new_path ) {
								
								_this.rescan( path );
							}
						},
						error: function( data ) {
							
							console.log( data );
						},
					});
				}
			}
		},
		
		refresh_preview: function( event ) {
			
			let _this = codiad.filemanager;
			
			/**
			 * When reloading after every change, we encounter performance issues
			 * in the editor.  Therefore, we implement the same logic as the
			 * auto_save module where we only reload after the user has finished
			 * changing their document.
			 */
			
			if( _this.refresh_interval !== null ) {
				
				clearTimeout( _this.refresh_interval );
				_this.refresh_interval = null;
			}
			_this.refresh_interval = setTimeout( function() {
				
				if( _this.preview_window == null ) {
					
					return;
				}
				
				try {
					
					if( ( typeof _this.preview_window.location.reload ) == "undefined" ) {
						
						_this.preview_window = null;
						codiad.editor.getActive().removeEventListener( "change", _this.refresh_preview );
						return;
					}
					_this.preview_window.location.reload( true );
				} catch ( e ) {
					
					console.log( e );
					codiad.message.error( 'Please close your previously opened preview window.' );
					_this.preview_window = null;
					codiad.editor.getActive().removeEventListener( "change", _this.refresh_preview );
				}
			}, 1000 );
		},
		
		rename: function( path, new_path ) {
			
			let _this = codiad.filemanager;
			
			return new Promise( function( resolve, reject ) {
				
				$.ajax({
					type: 'POST',
					url: _this.controller + '?action=rename',
					data: {
						
						path: path,
						destination: new_path
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
		
		rename_node: async function( path, new_path ) {
			
			let short_name = this.get_short_name( path );
			let type = this.getType( path );
			let _this = this;
			let project = codiad.project.getCurrent();
			
			let arr = path.split( '/' );
			let message = "Successfully Renamed."
			let new_parent = new_path.split( '/' );
			let parent = path.split( '/' );
			let temp = [];
			
			parent.pop();
			new_parent.pop();
			
			for( i = 0; i < arr.length - 1; i++ ) {
				
				temp.push( arr[i] )
			}
			
			let result = codiad.jsend.parse( await _this.rename( path, new_path ) );
			
			if( result != 'error' ) {
				
				if( type !== undefined ) {
					
					let node = $( '#file-manager a[data-path="' + path + '"]' );
					
					node.attr( 'data-path', new_path ).html( new_path.split( "/" ).pop() );
					message = type.charAt( 0 ).toUpperCase() + type.slice( 1 ) + ' Renamed'
					codiad.message.success( message );
					
					// Change icons for file
					let current_class = 'ext-' + _this.get_extension( path );
					let new_class = 'ext-' + _this.get_extension( new_path );
					
					$( '#file-manager a[data-path="' + new_path + '"]' )
					.removeClass( current_class )
					.addClass( new_class );
					codiad.active.rename( path, new_path );
					
					codiad.filemanager.rescan( parent.join( '/' ) );
					
					if( parent !== new_parent ) {
						
						console.log( parent, new_parent );
						codiad.filemanager.rescan( new_parent.join( '/' ) );
					}
					
					/* Notify listeners. */
					amplify.publish( 'filemanager.onRename', {
						path: path,
						newPath: new_path,
						project: project
					});
				}
			}
		},
		
		preview: function( path ) {
			
			let _this = this;
			
			$.ajax({
				type: 'GET',
				url: _this.controller + '?action=preview&path=' + encodeURIComponent( path ),
				success: function( data ) {
					
					console.log( data );
					let r = JSON.parse( data );
					
					if( r.status === "success" ) {
						
						_this.preview = window.open( r.data, '_newtab' );
						
						let editor = codiad.editor.getActive();
						
						if( _this.auto_reload && editor !== null ) {
							
							editor.addEventListener( "change", _this.refresh_preview );
						}
					} else {
						
						codiad.message.error( i18n( r.message ) );
					}
				},
				error: function( data ) {
					
					codiad.message.error( i18n( r.message ) );
				},
			});
		},
		
		rescan: function( path ) {
			
			let _this = codiad.filemanager;
			_this.index( path, true );
		},
		
		save_file: function( path, data, display_messages = true ) {
			
			return new Promise( function( resolve, reject ) {
				
				let _this = codiad.filemanager;
				
				$.ajax({
					type: 'POST',
					url: _this.controller + '?action=modify',
					data: {
						
						path: path,
						data: JSON.stringify( data ),
					},
					success: function( data ) {
						
						console.log( data );
						let r = JSON.parse( data );
						
						if( r.status === "success" ) {
							
							if( display_messages === true ) {
								
								codiad.message.success( i18n( 'File saved' ) );
							}
							resolve( r );
						} else if( r.message == 'Client is out of sync' ) {
							
							let reload = confirm(
								"Server has a more updated copy of the file. Would " +
								"you like to refresh the contents ? Pressing no will " +
								"cause your changes to override the server's copy upon " +
								"next save."
							);
							if( reload ) {
								
								codiad.active.close( path );
								codiad.active.removeDraft( path );
								_this.openFile( path );
							} else {
								
								let session = codiad.editor.getActive().getSession();
								session.serverMTime = null;
								session.untainted = null;
							}
							resolve( r );
						} else {
							
							codiad.message.error( i18n( r.message ) );
							reject( data );
						}
					},
					error: function( data ) {
						
						codiad.message.error( i18n( 'File could not be saved' ) );
						reject( data );
					},
				});
			});
		},
		
		search: async function( path ) {
			
			let _this = this;
			let container = await codiad.modal.load( 500, this.dialog, {
				action: 'search',
				path: path
			});
			codiad.modal.hideOverlay();
			$( '#modal-content form' )
			.on( 'submit', function( e ) {
				$( '#filemanager-search-processing' ).show();
				e.preventDefault();
				searchString = $( '#modal-content form input[name="search_string"]' ).val();
				fileExtensions = $( '#modal-content form input[name="search_file_type"]' ).val();
				searchFileType = $.trim( fileExtensions );
				if( searchFileType != '' ) {
					//season the string to use in find command
					searchFileType = "\\(" + searchFileType.replace( /\s+/g, "\\|" ) + "\\)";
				}
				searchType = $( '#modal-content form select[name="search_type"]' )
				.val();
				let options = {
					filetype: fileExtensions,
				};
				$.post( _this.controller + '?action=search', {
					path: path,
					query: searchString,
					options: JSON.stringify( options )
				}, function( data ) {
					
					let searchResponse = codiad.jsend.parse( data );
					let results = '';
					
					console.log( data );
					console.log( searchResponse );
					
					if( searchResponse != 'error' ) {
						$.each( searchResponse.index, function( key, val ) {
							// Cleanup file format
							if( val['file'].substr( -1 ) == '/' ) {
								val['file'] = val['file'].substr( 0, str.length - 1 );
							}
							val['file'] = val['file'].replace( '//', '/' );
							// Add result
							results += '<div><a onclick="codiad.filemanager.openFile(\'' + val['result'] + '\');setTimeout( function() { codiad.active.gotoLine(' + val['line'] + '); }, 500);codiad.modal.unload();">Line ' + val['line'] + ': ' + val['file'] + '</a></div>';
						});
						$( '#filemanager-search-results' )
						.slideDown()
						.html( results );
					} else {
						$( '#filemanager-search-results' )
						.slideUp();
					}
					$( '#filemanager-search-processing' )
					.hide();
				});
			});
		},
		
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
		
		selector_listeners: function( node, filters, callbacks ) {
			
			let _this = codiad.filemanager;
			
			$( node )
			.on( 'click', 'a', async function( e ) {
				
				let i = $( e.target );
				
				// Select or Expand
				if( codiad.editor.settings.fileManagerTrigger ) {
					
					_this.toggle_directory( $( i ), filters, callbacks );
				} else {
					
					_this.toggle_select_node( $( e.target ), limit );
				}
			})
			.on( 'click', 'span', async function( e ) {
				
				let i = $( e.target ).parent().children( 'a' );
				_this.toggle_directory( $( i ), {type: 'directory'} );
			})
			.on( 'dblclick', 'a', async function( e ) {
				
				let i = $( e.target );
				
				if( ! codiad.editor.settings.fileManagerTrigger ) {
					
					_this.toggle_directory( $( i ), {type: 'directory'} );
				} else {
					
					_this.toggle_select_node( $( e.target ), limit );
				}
			})
			.on( 'selectstart', false );
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
		
		toggle_directory: async function( node, filters, callbacks, open ) {
			
			let _this = codiad.filemanager;
			
			node.addClass( "loading" );
			let path = node.attr( "data-path" );
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
			
			if( i.open || open ) {
				
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
		
		upload_to_node: function( path ) {
			
			let _this = codiad.filemanager;
			codiad.modal.load(
				500,
				this.dialog, {
					action: 'upload',
				}
			)
			.then( function( container ) {
					
					let text = $( '<p style="max-width: 40vw;"></p>' );
					let input = $( '<input type="file" multiple style="display: none;">' );
					
					text.html( `<h2>Drag and drop files or folders anywhere or click here to upload a file!</h2>` );
					
					input.on( 'change', function( e ) {
						
						console.log( e );
						
						let items = e.target.files;
						_this.upload( items, path );
					});
					text.on( 'click', function( e ) {
						
						input.click();
					});
					
					container.html( '' );
					container.append( text );
					container.append( input );
			});
		},
		
		//Compatibility functions
		//these may be needed more after updating the new functions to newer standards
		
		copyNode: function( path ) {return this.copy_node( path )},
		createNode: function( path, type ) {return this.create_node( path, type )},
		deleteNode: function( path ) {return this.delete_node( path )},
		getExtension: function( path ) {return this.get_extension( path )},
		getShortName: function( path ) {return this.get_short_name( path )},
		getType: function( path ) {return this.get_type( path )},
		openFile: function( path ) {return this.open_file( path )},
		openInBrowser: function( path ) {return this.preview( path )},
		pasteNode: function( path ) {return this.paste_node( path )},
		renameNode: function( path ) {return this.rename_node( path )},
		saveFile: function( path, data, display_messages ) {return this.save_file( path, data, display_messages )},
		uploadToNode: function( path ) {return this.upload_to_node( path )},
	};
})( this, jQuery );