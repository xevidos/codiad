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
		post_max_size: ( 1024*1024 ),
		preview: null,
		refresh_interval: null,
		
		init: async function() {
			
			this.noAudio = [
				//Audio
				'aac',
				'aif',
				'mp3',
				'mp4',
				'wav',
				'ogg',
			];
			this.noFiles = [
				//Files
				'exe',
				'pdf',
				'zip',
				'tar',
				'tar.gz',
			];
			this.noImages = [
				//Images
				'ico',
				'icon',
				'jpg',
				'jpeg',
				'png',
				'gif',
				'bmp',
			];
			
			
			this.noOpen = this.noAudio.concat( this.noFiles, this.noImages ),
			this.noBrowser = this.noAudio.concat( this.noImages ),
			
			// Initialize node listener
			this.nodeListener();
			this.auto_reload = ( await codiad.settings.get_option( "codiad.filemanager.autoReloadPreview" ) == "true" );
			
			amplify.subscribe( 'settings.save', async function() {
				
				let option = ( await codiad.settings.get_option( "codiad.filemanager.autoReloadPreview" ) == "true" );
				if( option != codiad.filemanager.auto_reload ) {
					
					//codiad.auto_save.reload_interval();
					window.location.reload( true );
				}
			});
			
			/* Subscribe to know when a file become active. */
			amplify.subscribe( 'active.onFocus', async function( path ) {
				
				let _this = codiad.filemanager;
				let editor = codiad.editor.getActive();
				
				if( _this.auto_reload && editor !== null ) {
					
					codiad.editor.getActive().addEventListener( "change", _this.refreshPreview );
				}
			});
			
			// Load uploader
			$.loadScript( "components/filemanager/upload_scripts/jquery.ui.widget.js", true );
			$.loadScript( "components/filemanager/upload_scripts/jquery.iframe-transport.js", true );
			
			$( document ).on( 'dragenter', function( e ) {
				
				$( '.drop-overlay' ).css( 'display', 'block' );
				$( document ).on( 'keypress', codiad.filemanager.upload_overlay_off );
			});
			
			$( '.drop-overlay' ).on( 'drag dragstart dragend dragover dragenter dragleave drop', function( e ) {
				
				e.preventDefault();
				e.stopPropagation();
			})
			.on( 'dragover dragenter', function() {
				
				
			})
			.on( 'dragleave dragend drop', function() {
				
				//$( '.drop-overlay' ).css( 'display', 'none' );
			})
			.on( 'drop', function( e ) {
				
				e.preventDefault();
				e.stopPropagation();
				codiad.filemanager.upload_drop( e );
			});
			
			
			( async function( global, $ ) {
				
				let _this = codiad.filemanager;
				let result = await codiad.system.get_ini_setting( 'post_max_size' );
				result = result.toLowerCase()
				
				console.log( result, result.includes( 'g' ), result.includes( 'm' ), result.includes( 'k' ) );
				
				if( result.includes( 'g' ) ) {
					
					let integer = result.replace( /\D/g, '' );
					
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
			})( this, jQuery );
		},
		
		//////////////////////////////////////////////////////////////////
		// Context Menu
		//////////////////////////////////////////////////////////////////
		
		contextMenuShow: function( e, path, type, name ) {
			let _this = this;
			
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
			let top = e.pageY;
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
			$( '#file-manager, #editor-region' ).on( 'mousemove', codiad.filemanager.contextCheckMouse );
			$( '#context-menu, #editor-region' ).on( 'paste', codiad.editor.paste );
			
			/* Notify listeners. */
			amplify.publish( 'context-menu.onShow', {
				e: e,
				path: path,
				type: type
			});
			// Hide on click
			$( '#context-menu a' )
			.click( function() {
				_this.contextMenuHide();
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
		
		contextCheckMouse: function( e ) {
			
			let offset = $( '#context-menu' ).offset();
			let bottom = offset.top + $( '#context-menu' ).outerHeight( true ) + 20;
			let left = offset.left - 20;
			let right = offset.left + $( '#context-menu' ).outerWidth( true ) + 20;
			let top = offset.top - 20;
			
			if( ( e.clientX > right || e.clientX < left ) || ( e.clientY > bottom || e.clientY < top ) ) {
				
				$( '#file-manager, #editor-region' ).off( 'mousemove', codiad.filemanager.contextCheckMouse );
				$( '#context-menu, #editor-region' ).off( 'paste', codiad.editor.paste );
				codiad.filemanager.contextMenuHide();
			}
		},
		
		contextMenuHide: function() {
			$( '#context-menu' )
			.fadeOut( 200 );
			$( '#file-manager a' )
			.removeClass( 'context-menu-active' );
			/* Notify listeners. */
			amplify.publish( 'context-menu.onHide' );
		},
		
		//////////////////////////////////////////////////////////////////
		// Copy to Clipboard
		//////////////////////////////////////////////////////////////////
		
		copyNode: function( path ) {
			this.clipboard = path;
			codiad.message.success( i18n( 'Copied to Clipboard' ) );
		},
		
		//////////////////////////////////////////////////////////////////
		// Create Object
		//////////////////////////////////////////////////////////////////
		
		createNode: function( path, type ) {
			codiad.modal.load( 250, this.dialog, {
				action: 'create',
				type: type,
				path: path
			});
			$( '#modal-content form' )
			.live( 'submit', function( e ) {
				e.preventDefault();
				let shortName = $( '#modal-content form input[name="object_name"]' )
				.val();
				let path = $( '#modal-content form input[name="path"]' )
				.val();
				let type = $( '#modal-content form input[name="type"]' )
				.val();
				let createPath = path + '/' + shortName;
				$.get( codiad.filemanager.controller + '?action=create&path=' + encodeURIComponent( createPath ) + '&type=' + type, function( data ) {
					let createResponse = codiad.jsend.parse( data );
					if( createResponse != 'error' ) {
						codiad.message.success( type.charAt( 0 )
						.toUpperCase() + type.slice( 1 ) + ' Created' );
						codiad.modal.unload();
						// Add new element to filemanager screen
						codiad.filemanager.createObject( path, createPath, type );
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
		},
		
		//////////////////////////////////////////////////////////////////
		// Create node in file tree
		//////////////////////////////////////////////////////////////////
		
		createObject: function( parent, path, type ) {
			// NODE FORMAT: <li><a class="{type} {ext-file_extension}" data-type="{type}" data-path="{path}">{short_name}</a></li>
			let parentNode = $( '#file-manager a[data-path="' + parent + '"]' );
			let appendage = null;
			if( !$( '#file-manager a[data-path="' + path + '"]' )
			.length ) { // Doesn't already exist
				if( parentNode.hasClass( 'open' ) && parentNode.hasClass( 'directory' ) ) { // Only append node if parent is open (and a directory)
					let shortName = this.getShortName( path );
					if( type == 'directory' ) {
						appendage = '<li><span class="none"></span><a class="directory" data-type="directory" data-path="' + path + '">' + shortName + '</a></li>';
					} else {
						appendage = '<li><span class="none"></span><a class="file ext-' +
							this.getExtension( shortName ) +
							'" data-type="file" data-path="' +
							path + '">' + shortName + '</a></li>';
					}
					if( parentNode.siblings( 'ul' )
					.length ) { // UL exists, other children to play with
						parentNode.siblings( 'ul' )
						.append( appendage );
					} else {
						$( '<ul>' + appendage + '</ul>' )
						.insertAfter( parentNode );
					}
				} else {
					parentNode.parent().children( 'span' ).removeClass( 'none' );
					parentNode.parent().children( 'span' ).addClass( 'plus' );
				}
			}
		},
		
		//////////////////////////////////////////////////////////////////
		// Delete
		//////////////////////////////////////////////////////////////////
		
		deleteNode: function( path ) {
			let _this = this;
			codiad.modal.load( 400, this.dialog, {
				action: 'delete',
				path: path
			});
			$( '#modal-content form' )
			.live( 'submit', function( e ) {
				e.preventDefault();
				$.get( _this.controller + '?action=delete&path=' + encodeURIComponent( path ), function( data ) {
					
					console.log( data );
					
					let deleteResponse = codiad.jsend.parse( data );
					if( deleteResponse != 'error' ) {
						let node = $( '#file-manager a[data-path="' + path + '"]' );
						let parent_path = node.parent().parent().prev().attr( 'data-path' );
						node.parent( 'li' ).remove();
						// Close any active files
						$( '#active-files a' )
						.each( function() {
							let curPath = $( this )
							.attr( 'data-path' );
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
		
		deleteInnerNode: function( path ) {
			let _this = this;
			codiad.modal.load( 400, this.dialog, {
				action: 'delete',
				path: path
			});
			$( '#modal-content form' )
			.live( 'submit', function( e ) {
				e.preventDefault();
				$.get( _this.controller + '?action=deleteInner&path=' + encodeURIComponent( path ), function( data ) {
					let deleteResponse = codiad.jsend.parse( data );
					if( deleteResponse != 'error' ) {
						let node = $( '#file-manager a[data-path="' + path + '"]' ).parent( 'ul' ).remove();
						
						// Close any active files
						$( '#active-files a' )
						.each( function() {
							let curPath = $( this )
							.attr( 'data-path' );
							if( curPath.indexOf( path ) == 0 ) {
								codiad.active.remove( curPath );
							}
						});
						
						//Rescan Folder
						node.parent()
						.find( 'a.open' )
						.each( function() {
							_this.rescanChildren.push( $( this )
							.attr( 'data-path' ) );
						});
						
						/* Notify listeners. */
						amplify.publish( 'filemanager.onDelete', {
							deletePath: path + "/*",
							path: path
						});
					}
					codiad.modal.unload();
				});
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Download
		//////////////////////////////////////////////////////////////////
		
		download: function( path ) {
			let type = this.getType( path );
			$( '#download' )
			.attr( 'src', 'components/filemanager/download.php?path=' + encodeURIComponent( path ) + '&type=' + type );
		},
		
		//////////////////////////////////////////////////////////////////
		// Return extension
		//////////////////////////////////////////////////////////////////
		
		getExtension: function( path ) {
			return path.split( '.' )
			.pop();
		},
		
		//////////////////////////////////////////////////////////////////
		// Return the node name (sans path)
		//////////////////////////////////////////////////////////////////
		
		getShortName: function( path ) {
			return path.split( '/' )
			.pop();
		},
		
		//////////////////////////////////////////////////////////////////
		// Return type
		//////////////////////////////////////////////////////////////////
		
		getType: function( path ) {
			
			if( path.match( /\\/g ) ) {
				
				path = path.replace( '\\', '\\\\' );
			}
			
			return $( '#file-manager a[data-path="' + path + '"]' ).attr( 'data-type' );
		},
		
		//////////////////////////////////////////////////////////////////
		// Loop out all files and folders in directory path
		//////////////////////////////////////////////////////////////////
		
		opened_folders: [],
		files: [],
		
		get_indexes: async function( path ) {
			
			let r = await $.get( this.controller + '?action=index&path=' + encodeURIComponent( path ) );
			return r;
		},
		
		get_index_list: async function( filters = {}, callbacks = [] ) {
			
			let _this = codiad.filemanager;
			let data = [];
			let response = await _this.get_indexes( codiad.project.getCurrent() );
			
			response = codiad.jsend.parse( response );
			
			if( response.index ) {
				
				data = response.index;
			}
			
			let children = _this.create_indexes( data, null, filters, callbacks );
			let div = $( '<div class="file-manager"></div>' );
			
			div.html( children );
			return div
		},
		
		find_index: function( path, files ) {
			
			let _this = this;
			let index = {};
			let total = ( !!files ) ? files.length : 0;
			
			for( let i = 0;i < total;i++ ) {
				
				if( path == files[i].dirname ) {
					
					index = files[i];
					break;
				} else {
					
					if( files[i].children !== undefined ) {
						
						index = _this.find_index( path, files[i].children );
						
						if( Object.keys( index ).length > 0 ) {
							
							break;
						}
					}
				}
			}
			return index;
		},
		
		set_index_children: function( path, files, children ) {
			
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
						
						index = _this.set_index_children( path, files[i].children, children );
						
						if( Object.keys( index ).length > 0 ) {
							
							break;
						}
					}
				}
			}
			return index;
		},
		
		index: async function( path, rescan ) {
			
			let _this = codiad.filemanager;
			let node = $( '#file-manager a[data-path="' + path + '"]' );
			let parentNode = node.parent();
			let span = node.prev();
			let total_saved = _this.files.length;
			let container = $( '<ul></ul>' );
			let files = [];
			let open_children = $( '#file-manager a[data-type="root"]' ).parent().find( 'a.open' );
			let root = false;
			let file = _this.find_index( path, _this.files );
			let children = 0;
			
			_this.opened_folders = [];
			
			if( rescan === undefined ) {
				
				rescan = false;
			}
			
			if( node.hasClass( "directory" ) ) {
				
				node.droppable({
					accept: _this.object_accept,
					drop: _this.object_drop,
					over: _this.object_over,
					out: _this.object_out
				});
			}
			
			if( file.children ) {
				
				children = file.children.length;
			}
			
			node.addClass( 'loading' );
			
			for( let i = open_children.length;i--; ) {
				
				_this.opened_folders.push( $( open_children[i] ).attr( "data-path" ) );
			}
			
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
					
					_this.set_index_children( path, _this.files, result );
					files = result;
				}
				
				let total_opened = _this.opened_folders.length;
				for( let i = 0;i < total_opened;i++ ) {
					
					if( _this.is_child( path, _this.opened_folders[i] ) ) {
						
						_this.index( _this.opened_folders[i], rescan );
					}
				}
			} else {
				
				files = file.children;
			}
			
			//Add Legacy support for new file layout
			for( let i = files.length;i--; ) {
				
				files[i].name = files[i].path;
			}
			
			console.log( file, files );
			
			/* Notify listener */
			amplify.publish( "filemanager.onIndex", {
				path: path,
				files: files
			});
			
			root = ( node.attr( 'data-type' ) == 'root' );
			let plus = span.hasClass( 'plus' );
			let minus = span.hasClass( 'minus' );
			let trigger = ( plus || span.hasClass( 'none' ) || root || ( rescan && minus ) );
			
			console.log( plus, span.hasClass( 'none' ), root, ( rescan && minus ) );
			console.log( trigger );
			
			_this.toggle_open_close( node.parent(), trigger );
			
			if( trigger ) {
				
				_this.create_indexes( files, container );
				let ul = node.parent( 'li' ).children( 'ul' );
				
				if( ul.length ) {
					
					ul.replaceWith( container );
				} else {
					
					$( container ).insertAfter( node );
				}
				
				console.log( ul, container );
			}
			node.removeClass( 'loading' );
		},
		
		filemanager_index: function( node, container, file, files ) {
			
			let _this = this;
			let link = node.children( 'a' );
			
			node.draggable({
				
				opacity: 0.85,
				revert: true,
				start: _this.object_start,
				stop: _this.object_stop,
				zIndex: 100
			});
			
			if( file.type == "directory" ) {
				
				link.droppable({
					accept: _this.object_accept,
					drop: _this.object_drop,
					over: _this.object_over,
					out: _this.object_out
				});
			}
		},
		
		create_indexes: function( files, container = null, filters = {}, callbacks = [] ) {
			
			let _this = this;
			let total_files = files.length;
			let root = null;
			
			if( ! container ) {
				
				let project_path = $( '#project-root' ).attr( 'data-path' );
				let project_name = $( '#project-root' ).text();
				
				let a = $( `<a class="directory" data-path="${project_path}">${project_name}</a>` );
				let li = $( '<li></li>' );
				root = $( '<ul></ul>' );
				container = $( '<ul></ul>' );
				
				li.html( a );
				li.append( container );
				root.html( li );
				
				console.log( a, li, root, container );
			} else {
				
				root = container
			}
			
			for( let i = 0;i < total_files;i++ ) {
				
				let value = files[i];
				let expanded = _this.opened_folders.includes( value.path );
				let ext = '';
				let name = '';
				let nodeClass = 'none';
				let entry = $( "<li></li>" );
				let span = $( "<span></span>" );
				let link = $( "<a></a>" );
				let type = null;
				
				if( value.type == "file" ) {
					
					if( filters.type == "directories" ) {
						
						continue;
					}
					
					ext = "ext-" + value.extension;
					name = value.basename;
					type = 'file';
					link.addClass( ext );
				} else {
					
					if( filters.type == "files" ) {
						
						continue;
					}
					
					if( value.children !== null ) {
						
						if( expanded ) {
							
							let sub_container = $( '<ul></ul>' );
							
							nodeClass = 'minus';
							link.addClass( 'open' );
							_this.create_indexes( value.children, sub_container, filters, callbacks );
							$( sub_container ).insertAfter( container );
						} else {
							
							nodeClass = 'plus';
						}
					}
					
					name = value.basename;
					type = 'directory';
				}
				
				span.addClass( nodeClass );
				link.addClass( type );
				link.attr( "data-type", type );
				link.attr( "data-path", value.path );
				link.text( name );
				
				entry.append( span, link );
				container.append( entry );
				
				if( typeof callbacks == "function" ) {
					
					callbacks( entry, container, value, files );
				} else if( Array.isArray( callbacks ) ) {
					
					let total_callbacks = callbacks.length;
					for( let j = 0;j < total_callbacks;j++ ) {
						
						callbacks[j]();
					}
				}
			}
			return root;
		},
		
		is_child: function( parent, child ) {
			
			if( child === parent ) {
				
				return false;
			}
			
			let parentTokens = parent.split( '/' ).filter( i => i.length );
			return parentTokens.every( ( t, i ) => child.split( '/' )[i] === t )
		},
		
		//////////////////////////////////////////////////////////////////
		// Listen for dbclick events on nodes
		//////////////////////////////////////////////////////////////////
		
		nodeListener: function() {
			
			let _this = this;
			
			$( '#file-manager' )
			.on( 'click', 'a', function() {
				
				// Open or Expand
				if( codiad.editor.settings.fileManagerTrigger ) {
					
					if( $( this ).hasClass( 'directory' ) ) {
						
						_this.index( $( this ).attr( 'data-path' ) );
					} else {
						
						_this.openFile( $( this ).attr( 'data-path' ) );
					}
				}
			})
			.on( 'click', 'span', function() {
				
				// Open or Expand
				if( $( this ).parent().children( "a" ).attr( 'data-type' ) == 'directory' ) {
					
					_this.index( $( this ).parent().children( "a" ).attr( 'data-path' ) );
				} else {
					
					_this.openFile( $( this ).parent().children( "a" ).attr( 'data-path' ) );
				}
			})
			.on( "contextmenu", 'a', function( e ) {
				
				// Context Menu
				e.preventDefault();
				_this.contextMenuShow(
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
						
						_this.index( $( this ).attr( 'data-path' ) );
					} else {
						
						_this.openFile( $( this ).attr( 'data-path' ) );
					}
				}
			})
			.on( 'selectstart', false );
		},
		
		object_accept: function( e, i ) {
			
			return true;
		},
		
		object_drag: function( e, i ) {
			
		},
		
		object_drop: function( e, i ) {
			
			let _this = codiad.filemanager;
			let drag = i.helper[0];
			let drop = e.target;
			
			$( drop ).removeClass( "drag_over" );
			
			console.log( drop );
			console.log( drag );
			
			let drop_path = $( drop ).attr( "data-path" );
			let drag_path = $( drag ).children( "a" ).attr( "data-path" );
			let path = drag_path;
			let newPath = `${drop_path}/` + path.split( "/" ).pop();
			
			_this.rename( path, newPath );
		},
		
		object_out: function( e, i ) {
			
			let drag = i.helper[0];
			let drop = e.target;
			
			$( drop ).removeClass( "drag_over" );
		},
		
		object_over: function( e, i ) {
			
			let drag = i.helper[0];
			let drop = e.target;
			
			$( drop ).addClass( "drag_over" );
		},
		
		object_start: function( e, i ) {
			
			let drag = i.helper[0];
			$( drag ).addClass( "drag_start" );
			$( drag ).children( 'a' ).removeClass( "a:hover" );
		},
		
		object_stop: function( e, i ) {
			
			let drag = i.helper[0];
			$( drag ).removeClass( "drag_start" );
			//$( drag ).removeClass( "hover" );
		},
		
		open_selector: async function( type, callback, limit ) {
			
			let _this = this;
			return new Promise( function( resolve, reject ) {
				
				codiad.modal.load(
					300,
					_this.dialog,
					{
						action: 'selector',
						type: type,
					},
					async function( container ) {
						
						let _this = codiad.filemanager;
						let div = await _this.get_index_list( {type: type}, [] );
						_this.selector_listeners( div, limit );
						container.html( div );
						
						let select = $( '<button class="btn-left">Select</button>' );
						let cancel = $( '<button class="btn-right">Cancel</button>' );
						
						container.append( select );
						container.append( cancel );
						
						select.on( 'click', function( e ) {
							
							codiad.modal.unload();
							resolve( _this.selected );
						});
						cancel.on( 'click', function( e ) {
							
							codiad.modal.unload();
							reject( null );
						});
					},
				);
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Open File
		//////////////////////////////////////////////////////////////////
		
		openFile: function( path, focus=true ) {
			
			/* Notify listeners. */
			amplify.publish( 'filemanager.onFileWillOpen', {
				path: path
			});
			
			let node = $( '#file-manager a[data-path="' + path + '"]' );
			let ext = this.getExtension( path );
			
			if( $.inArray( ext.toLowerCase(), this.noOpen ) < 0 ) {
				
				node.addClass( 'loading' );
				$.get( this.controller + '?action=open&path=' + encodeURIComponent( path ), function( data ) {
					
					let openResponse = codiad.jsend.parse( data );
					if( openResponse != 'error' ) {
						
						node.removeClass( 'loading' );
						codiad.active.open( path, openResponse.content, openResponse.mtime, false, focus, openResponse.read_only );
					}
				});
			} else {
				
				if( ! codiad.project.isAbsPath( path ) ) {
					
					if( $.inArray( ext.toLowerCase(), this.noBrowser ) < 0 ) {
						
						this.download( path );
					} else {
						
						this.openInModal( path );
					}
				} else {
					
					codiad.message.error( i18n( 'Unable to open file in Browser while using absolute path.' ) );
				}
			}
		},
		
		//////////////////////////////////////////////////////////////////
		// Open in browser
		//////////////////////////////////////////////////////////////////
		
		openInBrowser: function( path ) {
			
			let _this = this;
			
			$.ajax( {
				url: this.controller + '?action=open_in_browser&path=' + encodeURIComponent( path ),
				success: function( data ) {
					
					console.log( data )
					
					let openIBResponse = codiad.jsend.parse( data );
					
					console.log( openIBResponse );
					
					if( openIBResponse != 'error' ) {
						
						_this.preview = window.open( openIBResponse, '_newtab' );
						
						let editor = codiad.editor.getActive();
						
						if( _this.auto_reload && editor !== null ) {
							
							codiad.editor.getActive().addEventListener( "change", _this.refreshPreview );
						}
						
						
					}
				},
				async: false
			});
		},
		
		openInModal: function( path ) {
			
			let type = "";
			let ext = this.getExtension( path ).toLowerCase();
			
			if( this.noAudio.includes( ext ) ) {
				
				type = 'music_preview';
			} else if( this.noImages.includes( ext ) ) {
				
				type = 'preview';
			}
			
			codiad.modal.load( 250, this.dialog, {
				action: type,
				path: path
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Paste
		//////////////////////////////////////////////////////////////////
		
		pasteNode: function( path ) {
			let _this = this;
			if( this.clipboard == '' ) {
				codiad.message.error( i18n( 'Nothing in Your Clipboard' ) );
			} else if( path == this.clipboard ) {
				codiad.message.error( i18n( 'Cannot Paste Directory Into Itself' ) );
			} else {
				let shortName = _this.getShortName( _this.clipboard );
				if( $( '#file-manager a[data-path="' + path + '/' + shortName + '"]' )
				.length ) { // Confirm overwrite?
					codiad.modal.load( 400, this.dialog, {
						action: 'overwrite',
						path: path + '/' + shortName
					});
					$( '#modal-content form' )
					.live( 'submit', function( e ) {
						e.preventDefault();
						let duplicate = false;
						if( $( '#modal-content form select[name="or_action"]' ).val() == 1 ) {
							duplicate = true;
							//console.log( 'Dup!' );
						}
						_this.processPasteNode( path, duplicate );
					});
				} else { // No conflicts; proceed...
					_this.processPasteNode( path, false );
				}
			}
		},
		
		processPasteNode: function( path, duplicate ) {
			let _this = this;
			let shortName = this.getShortName( this.clipboard );
			let type = this.getType( this.clipboard );
			
			$.get( this.controller + '?action=duplicate&path=' +
				encodeURIComponent( this.clipboard ) + '&destination=' +
				encodeURIComponent( path + '/' + shortName ) + '&duplicate=' + encodeURIComponent( duplicate ),
				function( data ) {
					let pasteResponse = codiad.jsend.parse( data );
					if( pasteResponse != 'error' ) {
						_this.createObject( path, path + '/' + shortName, type );
						codiad.modal.unload();
						/* Notify listeners. */
						amplify.publish( 'filemanager.onPaste', {
							path: path,
							shortName: shortName,
							duplicate: duplicate
						});
						codiad.filemanager.rescan( path );
					}
				});
		},
		
		refreshPreview: function( event ) {
			
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
				
				if( _this.preview == null ) {
					
					return;
				}
				
				try {
					
					if( ( typeof _this.preview.location.reload ) == "undefined" ) {
						
						_this.preview = null;
						codiad.editor.getActive().removeEventListener( "change", _this.refreshPreview );
						return;
					}
					_this.preview.location.reload( true );
				} catch ( e ) {
					
					console.log( e );
					codiad.message.error( 'Please close your previously opened preview window.' );
					_this.preview = null;
					codiad.editor.getActive().removeEventListener( "change", _this.refreshPreview );
				}
			}, 500 );
		},
		
		rename: function( path, newPath ) {
			
			let _this = this;
			$.get( _this.controller, {
				action: 'rename',
				path: path,
				destination: newPath
			}, function( data ) {
				
				let type = _this.getType( path );
				let renameResponse = codiad.jsend.parse( data );
				let renamedMessage = "";
				let project = codiad.project.getCurrent();
				
				if( renameResponse != 'error' ) {
					
					if( type == undefined ) {
						
						renamedMessage = 'Successfully Renamed'
					} else {
						
						renamedMessage = type.charAt( 0 ).toUpperCase() + type.slice( 1 ) + ' Renamed'
					}
					
					codiad.message.success( renamedMessage );
					let node = $( '#file-manager a[data-path="' + path + '"]' );
					// Change pathing and name for node
					node.attr( 'data-path', newPath ).html( newPath.split( "/" ).pop() );
					if( type == 'file' ) {
						
						// Change icons for file
						curExtClass = 'ext-' + _this.getExtension( path );
						newExtClass = 'ext-' + _this.getExtension( newPath );
						$( '#file-manager a[data-path="' + newPath + '"]' )
						.removeClass( curExtClass )
						.addClass( newExtClass );
					} else {
						
						// Change pathing on any sub-files/directories
						_this.repathSubs( path, newPath );
					}
					// Change any active files
					codiad.active.rename( path, newPath );
					codiad.modal.unload();
					
					let parent = path.split( '/' );
					let newParent = newPath.split( '/' );
					parent.pop();
					newParent.pop();
					
					codiad.filemanager.rescan( parent.join( '/' ) );
					codiad.filemanager.rescan( newParent.join( '/' ) );
					
					/* Notify listeners. */
					amplify.publish( 'filemanager.onRename', {
						path: path,
						newPath: newPath,
						project: project
					});
				}
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Rename
		//////////////////////////////////////////////////////////////////
		
		renameNode: function( path ) {
			let shortName = this.getShortName( path );
			let type = this.getType( path );
			let _this = this;
			codiad.modal.load( 250, this.dialog, {
				action: 'rename',
				path: path,
				short_name: shortName,
				type: type
			});
			$( '#modal-content form' )
			.live( 'submit', function( e ) {
				let project = codiad.project.getCurrent();
				e.preventDefault();
				let newName = $( '#modal-content form input[name="object_name"]' ).val();
				// Build new path
				let arr = path.split( '/' );
				let temp = new Array();
				for( i = 0; i < arr.length - 1; i++ ) {
					temp.push( arr[i] )
				}
				let newPath = temp.join( '/' ) + '/' + newName;
				_this.rename( path, newPath );
			});
		},
		
		repathSubs: function( oldPath, newPath ) {
			$( '#file-manager a[data-path="' + newPath + '"]' )
			.siblings( 'ul' )
			.find( 'a' )
			.each( function() {
				// Hit the children, hit 'em hard
				let curPath = $( this )
				.attr( 'data-path' );
				let revisedPath = curPath.replace( oldPath, newPath );
				$( this )
				.attr( 'data-path', revisedPath );
			});
		},
		
		rescanChildren: [],
		rescanCounter: 0,
		
		rescan: function( path ) {
			
			let _this = this;
			_this.index( path, true );
		},
		
		//////////////////////////////////////////////////////////////////
		// Save file
		//////////////////////////////////////////////////////////////////
		
		saveFile: function( path, content, callbacks, messages = true ) {
			this.saveModifications( path, {
				content: content
			}, callbacks, messages );
		},
		
		saveModifications: function( path, data, callbacks, messages = true ) {
			
			callbacks = callbacks || {};
			let _this = this, action;
			let notifySaveErr = function() {
				
				codiad.message.error( i18n( 'File could not be saved' ) );
				if( typeof callbacks.error === 'function' ) {
					
					let context = callbacks.context || _this;
					callbacks.error.apply( context, [data] );
				}
			}
			let post = {
				"data": JSON.stringify( data )
			};
			$.post( this.controller + '?action=modify&path=' + encodeURIComponent( path ), post, function( resp ) {
				
				resp = $.parseJSON( resp );
				if( resp.status == 'success' ) {
					if( messages === true ) {
						codiad.message.success( i18n( 'File saved' ) );
					}
					if( typeof callbacks.success === 'function' ) {
						let context = callbacks.context || _this;
						callbacks.success.call( context, resp.data.mtime );
					}
				} else {
					if( resp.message == 'Client is out of sync' ) {
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
					//} else codiad.message.error( i18n( 'File could not be saved' ) );
					} else codiad.message.error( i18n( resp.message ) );
					
					if( typeof callbacks.error === 'function' ) {
						
						let context = callbacks.context || _this;
						callbacks.error.apply( context, [resp.data] );
					}
				}
			}).error( notifySaveErr );
		},
		
		savePatch: function( path, patch, mtime, callbacks, alerts ) {
			if( patch.length > 0 )
				this.saveModifications( path, {
					patch: patch,
					mtime: mtime
				}, callbacks, alerts );
			else if( typeof callbacks.success === 'function' ) {
				let context = callbacks.context || this;
				callbacks.success.call( context, mtime );
			}
		},
		
		/////////////////////////////////////////////////////////////////
		// saveSearchResults
		/////////////////////////////////////////////////////////////////
		saveSearchResults: function( searchText, searchType, fileExtensions, searchResults ) {
			let lastSearched = {
				searchText: searchText,
				searchType: searchType,
				fileExtension: fileExtensions,
				searchResults: searchResults
			};
			localStorage.setItem( "lastSearched", JSON.stringify( lastSearched ) );
		},
		
		//////////////////////////////////////////////////////////////////
		// Search
		//////////////////////////////////////////////////////////////////
		
		search: function( path ) {
			codiad.modal.load( 500, this.dialog, {
				action: 'search',
				path: path
			});
			
			codiad.modal.load_process.done( async function() {
				let lastSearched = JSON.parse( await codiad.settings.get_option( "lastSearched" ) );
				if( lastSearched ) {
					
					$( '#modal-content form input[name="search_string"]' ).val( lastSearched.searchText );
					$( '#modal-content form input[name="search_file_type"]' ).val( lastSearched.fileExtension );
					$( '#modal-content form select[name="search_type"]' ).val( lastSearched.searchType );
					if( lastSearched.searchResults != '' ) {
						$( '#filemanager-search-results' ).slideDown().html( lastSearched.searchResults );
					}
				}
			});
			codiad.modal.hideOverlay();
			let _this = this;
			$( '#modal-content form' ).live( 'submit', function( e ) {
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
					_this.saveSearchResults( searchString, searchType, fileExtensions, results );
					$( '#filemanager-search-processing' )
					.hide();
				});
			});
		},
		
		selected: [],
		
		selector_listeners: function( node, limit ) {
			
			let _this = codiad.filemanager;
			
			$( node )
			.on( 'click', 'a', async function( e ) {
				
				let i = $( e.target );
				
				// Select or Expand
				if( codiad.editor.settings.fileManagerTrigger ) {
					
					_this.toggle_open_close( i.parent(), null, async function() {
						
						let result = await _this.get_indexes( i.attr( 'data-path' ) );
						result = codiad.jsend.parse( result );
						let indexes = result.index;
						let ul = $( '<ul></ul>' );
						let children = _this.create_indexes( indexes, ul, {type: 'directories'}, [] );
						i.parent().children( 'ul' ).remove();
						i.parent().append( ul );
					},
					async function() {
						
						i.parent().children( 'ul' ).remove();
					});
				} else {
					
					_this.toggle_select_node( $( e.target ), limit );
				}
			})
			.on( 'click', 'span', async function( e ) {
				
				let i = $( e.target ).parent().children( 'a' );
				
				_this.toggle_open_close( i.parent(), null, async function() {
					
					let result = await _this.get_indexes( i.attr( 'data-path' ) );
					result = codiad.jsend.parse( result );
					let indexes = result.index;
					let ul = $( '<ul></ul>' );
					let children = _this.create_indexes( indexes, ul, {type: 'directories'}, [] );
					i.parent().children( 'ul' ).remove();
					i.parent().append( ul );
				},
				async function() {
					
					i.parent().children( 'ul' ).remove();
				});
			})
			.on( 'dblclick', 'a', async function( e ) {
				
				let i = $( e.target );
				
				if( ! codiad.editor.settings.fileManagerTrigger ) {
					
					_this.toggle_open_close( i.parent(), null, async function() {
						
						let result = await _this.get_indexes( i.attr( 'data-path' ) );
						result = codiad.jsend.parse( result );
						let indexes = result.index;
						let ul = $( '<ul></ul>' );
						let children = _this.create_indexes( indexes, ul, {type: 'directories'}, [] );
						i.parent().children( 'ul' ).remove();
						i.parent().append( ul );
					},
					async function() {
						
						i.parent().children( 'ul' ).remove();
					});
				} else {
					
					_this.toggle_select_node( $( e.target ), limit );
				}
			})
			.on( 'selectstart', false );
		},
		
		toggle_select_node: function( node, limit = 0 ) {
			
			let _this = codiad.filemanager;
			let selected = false;
			let path = node.attr( 'data-path' );
			let i = 1;
			
			for( i = _this.selected.length;i--; ) {
				
				if( _this.selected[i] == path ) {
					
					selected = true;
					break;
				}
			}
			
			if( limit > 0 && _this.selected.length >= limit ) {
				
				for( i = _this.selected.length;i--; ) {
					
					$( `[data-path='${_this.selected[i]}']` ).css( "background", "" );
				}
				_this.selected = [];
				_this.selected.push( path );
			} else {
				
				if( selected ) {
					
					_this.selected.splice( i, 1 );
				} else {
					
					_this.selected.push( path );
				}
			}
			
			console.log( path, _this.selected );
		},
		
		toggle_open_close: function( node, open, open_callback, close_callback ) {
			
			let span = node.children( 'span' );
			let a = node.children( 'a' );
			let plus = span.hasClass( 'plus' );
			let minus = span.hasClass( 'minus' );
			let ul = node.children( 'ul' );
			
			if( open === null ) {
				
				open = ( plus || span.hasClass( 'none' ) || a.attr( 'data-type' ) == 'root' );
			}
			
			if( open ) {
				
				if( plus ) {
					
					span.removeClass( 'plus' )
					span.addClass( 'minus' );
				}
				a.addClass( 'open' );
				
				if( typeof open_callback == "function" ) {
					
					open_callback();
				}
			} else {
				
				span.removeClass( 'minus' );
				span.addClass( 'plus' );
				node.children( 'ul' )
				.slideUp( 300, function() {
					
					$( this ).remove();
					node.removeClass( 'open' );
					node.children( 'span' ).removeClass( 'minus' ).addClass( 'plus' );
					node.children().find( 'span' ).removeClass( 'minus' ).addClass( 'plus' );
					node.children().find( 'a' ).removeClass( 'open' );
					
					if( typeof close_callback == "function" ) {
						
						close_callback();
					}
				});
			}
		},
		
		//////////////////////////////////////////////////////////////////
		// Upload
		//////////////////////////////////////////////////////////////////
		
		upload: async function( files, destination = null ) {
			
			let _this = this;
			let uploads = [];
			
			if( ! destination ) {
				
				destination = await codiad.filemanager.open_selector( 'directories', null, 1 );
			}
			
			if( `${destination}`.charAt( destination.length - 1 ) !== "/" ) {
				
				destination = destination + "/";
			}
			
			console.log( files );
			console.log( destination );
			
			for( let i = files.length;i--; ) {
				
				let entry = files[i];
				
				if( entry.isFile ) {
					
					entry.file( function( file ) {
						
						_this.upload_blobs( file, destination + file.name );
					});
				} else if( entry.isDirectory ) {
					
					_this.upload_read_directory( entry, destination, _this.upload_blobs );
				}
			}
		},
		
		upload_blob: function( blob, path ) {
			
			return new Promise( function( resolve, reject ) {
				
				let _this = codiad.filemanager;
				let form = new FormData();
				
				form.append( 'path', path );
				form.append( 'data', blob );
				$.ajax({
					type: 'POST',
					url: _this.controller + '?action=upload',
					data: form,
					processData: false,
					contentType: false,
					success: function( data ) {
						
						resolve( data );
					},
					error: function( data ) {
						
						reject( data );
					},
				});
			});
		},
		
		upload_blobs: function( file, path, start = 0, status = null ) {
			
			console.log( file, path );
			let _this = codiad.filemanager;
			let blob_size = 0;
			let total_size = file.size;
			let current = 0;
			let reader = new FileReader();
			let blob = null
			let upload_status = null;
			let total_blobs = 0;
			
			if( isNaN( _this.post_max_size ) || _this.post_max_size > ( 1024*1024*2 ) ) {
				
				blob_size = ( 1024*1024*2 );
			} else {
				
				blob_size = ( _this.post_max_size / 4 ) * 3;
			}
			
			console.log( total_size, blob_size, ( total_size / blob_size ) );
			
			if( total_size < blob_size ) {
				
				blob_size = total_size;
				current = start + blob_size;
				blob = file;
			} else {
				
				total_blobs = ( Math.round( ( total_size / blob_size ) ) );
				current = start + blob_size;
				blob = file.slice( current, current + blob_size );
			}
			
			console.log( current, blob_size, total_blobs );
			
			if( status === null ) {
				
				status = $().toastmessage( 'showToast', {
					text: 'Uploading blobs 0%',
					sticky: true,
					position: 'top-right',
					type: 'warning',
				});
			}
			
			reader.onload = async function( e ) {
				
				console.log( path, current, total_size )
				upload_status = await _this.upload_blob( e.target.result, path );
				try {
					
					console.log( upload_status )
					
					let result = JSON.parse( upload_status );
					if( result.bytes > 0 && current <= total_size ) {
						
						status.text( ( ( current / total_size )*100 ).toFixed( 2 ) + '%' );
						
						if( current < total_size ) {
							
							_this.upload_blobs( file, path, current, status );
						}
					} else {
						
						$().toastmessage( 'removeToast', status );
						_this.rescan( path.substring( 0, path.lastIndexOf( "/" ) ) );
					}
				} catch( exception ) {
					
					console.log( 'done?', upload_status );
					console.log( exception )
				}
			};
			reader.readAsDataURL( blob );
		},
		
		upload_data: {
			
			timers: {
				off: null,
			},
			entries: [],
		},
		
		upload_drop: function( e ) {
			
			let _this = codiad.filemanager;
			let data = [];
			let drop = $( '.drop-overlay' );
			let items = e.originalEvent.dataTransfer.items;
			
			for( let i = items.length;i--; ) {
				
				data.push( items[i].webkitGetAsEntry() );
			}
			
			_this.upload_overlay_off();
			_this.upload( data )
		},
		
		upload_overlay_off: function() {
			
			$( '.drop-overlay' ).css( 'display', 'none' );
			$( document ).off( 'keypress', codiad.filemanager.upload_overlay_off );
		},
		
		upload_overlay_on: function( e ) {
			
			e.preventDefault();
			e.stopPropagation();
			
			let _this = codiad.filemanager;
			let drop = $( e.target );
			let path = drop.attr( 'data-path' );
			
			if( _this.file_.timers.off ) {
				
				clearTimeout( _this.upload_data.timers.off );
			}
			
			_this.upload_data.timers.off = setTimeout( _this.upload_overlay_off, 1500 );
		},
		
		upload_read_directory: function( item, path, file_callback ) {
			
			let _this = codiad.filemanager;
			let files = [];
			
			if( item.isFile ) {
				
				// Get file
				item.file( function( file ) {
					
					file_callback( file, path + file.name );
				});
			} else if( item.isDirectory ) {
				
				// Get folder contents
				let dirReader = item.createReader();
				dirReader.readEntries( function( entries ) {
					
					let total = entries.length;
					for( let i = 0;i < total; i++ ) {
						
						_this.upload_read_directory( entries[i], path + item.name + "/", file_callback );
					}
				});
			}
		},
		
		uploadToNode: function( path ) {
			
			let _this = codiad.filemanager;
			codiad.modal.load(
				500,
				this.dialog, {
					action: 'upload',
				},
				async function( container ) {
					
					let text = $( '<p style="max-width: 40vw;"></p>' )
					let input = $( '<input type="file" multiple style="display: none;">' );
					
					text.html( `Codiad has a new file uploader!<br>Drag and drop a file or folder anywhere over the screen at any time <br>( you dont even have to have this window open ) and the upload will prompt for a destination.<br>Or<br>You can click here to upload files.` );
					
					
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
				}
			);
		},
	};
})( this, jQuery );