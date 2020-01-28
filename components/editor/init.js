/*
 *  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
 *  as-is and without warranty under the MIT License. See
 *  [root]/license.txt for more. This information must remain intact.
 */
( function( global, $ ) {
	
	// Classes from Ace
	var VirtualRenderer = ace.require( 'ace/virtual_renderer' ).VirtualRenderer;
	var Editor = ace.require( 'ace/editor' ).Editor;
	var EditSession = ace.require( 'ace/edit_session' ).EditSession;
	var ModeList = ace.require( "ace/ext/modelist" );
	var UndoManager = ace.require( "ace/undomanager" ).UndoManager;
	
	// Editor modes that have been loaded
	var editorModes = {};
	
	var codiad = global.codiad;
	codiad._cursorPoll = null;
	
	var separatorWidth = 3;
	
	$( function() {
		codiad.editor.init();
	});
	
	function SplitContainer( root, children, splitType ) {
		var _this = this;
		
		this.root = root;
		this.splitType = splitType;
		this.childContainers = {};
		this.childElements = {};
		this.splitProp = 0.5;
		
		this.setChild( 0, children[0] );
		this.setChild( 1, children[1] );
		
		this.splitter = $( '<div>' )
		.addClass( 'splitter' )
		.appendTo( root )
		.draggable( {
			axis: ( splitType === 'horizontal' ? 'x' : 'y' ),
			drag: function( e, ui ) {
				if( _this.splitType === 'horizontal' ) {
					var w1, w2;
					w1 = ui.position.left - separatorWidth / 2;
					w2 = _this.root.width() - ui.position.left - separatorWidth / 2;
					_this.splitProp = w1 / _this.root.width();
					_this.childElements[0]
					.width( w1 )
					.trigger( 'h-resize', [true, true] );
					_this.childElements[1]
					.width( w2 )
					.css( 'left', w1 + separatorWidth + 'px' )
					.trigger( 'h-resize', [true, true] );
					_this.splitProp = ui.position.left / _this.root.width();
				} else {
					var h1, h2;
					h1 = ui.position.top - separatorWidth / 2;
					h2 = _this.root.width() - ui.position.top -
						separatorWidth / 2;
					_this.splitProp = h1 / _this.root.height();
					_this.childElements[0]
					.height( h1 )
					.trigger( 'v-resize', [true, true] );
					_this.childElements[1]
					.height( h2 )
					.css( 'top', h1 + separatorWidth + 'px' )
					.trigger( 'v-resize', [true, true] );
				}
			}
		});
		
		if( splitType === 'horizontal' ) {
			this.splitter
			.addClass( 'h-splitter' )
			.width( separatorWidth )
			.height( root.height() );
		} else if( splitType === 'vertical' ) {
			this.splitter
			.addClass( 'v-splitter' )
			.height( separatorWidth )
			.width( root.width() );
		}
		
		this.root.on( 'h-resize', function( e, percolateUp, percolateDown ) {
			e.stopPropagation();
			if( _this.splitType === 'horizontal' ) {
				var w1, w2;
				w1 = _this.root.width() * _this.splitProp -
					separatorWidth / 2;
				w2 = _this.root.width() * ( 1 - _this.splitProp ) -
					separatorWidth / 2;
				_this.childElements[0]
				.width( w1 );
				_this.childElements[1]
				.width( w2 )
				.css( 'left', w1 + separatorWidth );
				_this.splitter.css( 'left', w1 );
			} else if( _this.splitType === 'vertical' ) {
				var w = _this.root.width();
				_this.childElements[0]
				.width( w );
				_this.childElements[1]
				.width( w );
				_this.splitter.width( w );
			}
			if( percolateUp ) {
				_this.root.parent( '.editor-wrapper' )
				.trigger( 'h-resize', [true, false] );
			}
			if( !percolateDown ) return;
			if( _this.childContainers[0] ) {
				_this.childContainers[0].root
				.trigger( 'h-resize', [false, true] );
			} else if( _this.childContainers[1] ) {
				_this.childContainers[1].root
				.trigger( 'h-resize', [false, true] );
			}
		});
		
		this.root.on( 'v-resize', function( e, percolateUp, percolateDown ) {
			e.stopPropagation();
			if( _this.splitType === 'horizontal' ) {
				var h = _this.root.height();
				_this.childElements[0]
				.height( h );
				_this.childElements[1]
				.height( h );
				_this.splitter.height( h );
			} else if( _this.splitType === 'vertical' ) {
				var h1 = _this.root.height() * _this.splitProp -
					separatorWidth / 2;
				var h2 = _this.root.height() * ( 1 - _this.splitProp ) -
					separatorWidth / 2;
				_this.childElements[0]
				.height( h1 );
				_this.childElements[1]
				.height( h2 )
				.css( 'top', h1 + separatorWidth );
				_this.splitter.css( 'top', h1 );
			}
			if( percolateUp ) {
				_this.root.parent( '.editor-wrapper' )
				.trigger( 'v-resize', [true, false] );
			}
			if( !percolateDown ) return;
			if( _this.childContainers[0] ) {
				_this.childContainers[0].root
				.trigger( 'v-resize', [false, true] );
			} else if( _this.childContainers[1] ) {
				_this.childContainers[1].root
				.trigger( 'v-resize', [false, true] );
			}
		});
		
		this.root
		.trigger( 'h-resize', [false, false] )
		.trigger( 'v-resize', [false, false] );
	}
	
	SplitContainer.prototype = {
		setChild: function( idx, el ) {
			
			if( el instanceof SplitContainer ) {
				this.childElements[idx] = el.root;
				this.childContainers[idx] = el;
				el.idx = idx;
			} else {
				this.childElements[idx] = el;
			}
			
			this.childElements[idx].appendTo( this.root );
			this.cssInit( this.childElements[idx], idx );
		},
		cssInit: function( el, idx ) {
			var props = {};
			var h1, h2, w1, w2, rh, rw;
			
			rh = this.root.height();
			rw = this.root.width();
			
			if( this.splitType === 'horizontal' ) {
				
				w1 = rw * this.splitProp - separatorWidth / 2;
				w2 = rw * ( 1 - this.splitProp ) - separatorWidth / 2;
				
				if( idx === 0 ) {
					props = {
						left: 0,
						width: w1,
						height: rh,
						top: 0
					};
				} else {
					props = {
						left: w1 + separatorWidth,
						width: w2,
						height: rh,
						top: 0
					};
				}
				
			} else if( this.splitType === 'vertical' ) {
				
				h1 = rh * this.splitProp - separatorWidth / 2;
				h2 = rh * ( 1 - this.splitProp ) - separatorWidth / 2;
				
				if( idx === 0 ) {
					props = {
						top: 0,
						height: h1,
						width: rw,
						left: 0
					};
				} else {
					props = {
						top: h1 + separatorWidth,
						height: h2,
						width: rw,
						left: 0
					};
				}
				
			}
			
			el.css( props );
		}
	};
	
	//////////////////////////////////////////////////////////////////
	//
	// Editor Component for Codiad
	// ---------------------------
	// Manage the lifecycle of Editor instances
	//
	//////////////////////////////////////////////////////////////////
	
	codiad.editor = {
		
		/// Editor instances - One instance corresponds to an editor
		/// pane in the user interface. Different EditSessions
		/// (ace/edit_session)
		instances: [],
		
		/// Currently focussed editor
		activeInstance: null,
		
		// Settings for Editor instances
		settings: {
			autocomplete: false,
			theme: 'twilight',
			fontSize: '13px',
			printMargin: false,
			printMarginColumn: 80,
			highlightLine: true,
			indentGuides: true,
			wrapMode: false,
			softTabs: false,
			persistentModal: true,
			rightSidebarTrigger: false,
			fileManagerTrigger: false,
			tabSize: 4
		},
		
		multi_line: false,
		
		rootContainer: null,
		
		fileExtensionTextMode: {},
		
		init: function() {
			this.createSplitMenu();
			this.createModeMenu();
			
			var er = $( '#editor-region' );
			
			er.on( 'h-resize-init', function() {
				$( '#editor-region > .editor-wrapper' )
				.width( $( this ).width() )
				.trigger( 'h-resize' );
				
			}).on( 'v-resize-init', function() {
				$( '#editor-region > .editor-wrapper' )
				.height( $( this ).height() )
				.trigger( 'v-resize' );
			});
			
			$( "#root-editor-wrapper" ).live( "contextmenu", function( e ) {
				
				// Context Menu
				e.preventDefault();
				_this = codiad.editor
				
				if( _this.getActive() ) {
					
					let path = codiad.active.getPath();
					$( e.target ).attr( 'data-path', path );
					codiad.filemanager.display_context_menu( e, path, 'editor', 'editor' );
					$( this ).addClass( 'context-menu-active' );
				}
			});
			
			$( window ).resize( function() {
				$( '#editor-region' )
				.trigger( 'h-resize-init' )
				.trigger( 'v-resize-init' );
			});
			
			//Load settings when initially starting up that way the first editor 
			//we open doesn't have the default settings.
			this.getSettings();
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Retrieve editor settings from database
		//
		//////////////////////////////////////////////////////////////////
		
		getSettings: async function() {
			
			let boolVal = null;
			let _this = this;
			let options = [
				'editor.fontSize',
				'editor.overScroll',
				'editor.printMarginColumn',
				'editor.tabSize',
				'editor.theme',
			];
			let bool_options = [
				'editor.autocomplete',
				'settings.autosave',
				'editor.printMargin',
				'editor.highlightLine',
				'editor.indentGuides',
				'editor.wrapMode',
				'editor.rightSidebarTrigger',
				'editor.fileManagerTrigger',
				'editor.softTabs',
				'editor.persistentModal',
			];
			
			let user_settings = await codiad.settings.get_options();
			
			//console.log( user_settings );
			
			$.each( options, function( idx, key ) {
				
				let localValue = user_settings['codiad.' + key];
				if( localValue != null ) {
					
					_this.settings[key.split( '.' ).pop()] = localValue;
				}
			});
			
			$.each( bool_options, async function( idx, key ) {
				
				let localValue = user_settings['codiad.' + key];
				if( localValue != null ) {
					
					_this.settings[key.split( '.' ).pop()] = ( localValue == 'true' );
				}
			});
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Apply configuration settings
		//
		// Parameters:
		//   i - {Editor}
		//
		/////////////////////////////////////////////////////////////////
		
		applySettings: function( i ) {
			// Check user-specified settings
			this.getSettings();
			
			// Apply the current configuration settings:
			i.setTheme( 'ace/theme/' + this.settings.theme );
			i.setFontSize( this.settings.fontSize );
			i.setPrintMarginColumn( this.settings.printMarginColumn );
			i.setShowPrintMargin( this.settings.printMargin );
			i.setHighlightActiveLine( this.settings.highlightLine );
			i.setDisplayIndentGuides( this.settings.indentGuides );
			i.getSession().setUseWrapMode( this.settings.wrapMode );
			this.setTabSize( this.settings.tabSize, i );
			this.setSoftTabs( this.settings.softTabs, i );
			this.setOverScroll( this.settings.overScroll, i );
			i.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: this.settings.autocomplete
			});
			
			if( i.getSession().read_only ) {
				
				i.setReadOnly( true );
			}
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Create a new editor instance attached to given session
		//
		// Parameters:
		//   session - {EditSession} Session to be used for new Editor instance
		//
		//////////////////////////////////////////////////////////////////
		
		addInstance: function( session, where ) {
			var el = $( '<div class="editor">' );
			var chType, chArr = [],
				sc = null,
				chIdx = null;
			var _this = this;
			
			if( this.instances.length == 0 ) {
				// el.appendTo($('#editor-region'));
				el.appendTo( $( '#root-editor-wrapper' ) );
			} else {
				
				var ch = this.activeInstance.el;
				var root;
				
				chIdx = ( where === 'top' || where === 'left' ) ? 0 : 1;
				chType = ( where === 'top' || where === 'bottom' ) ?
					'vertical' : 'horizontal';
				
				chArr[chIdx] = el;
				chArr[1 - chIdx] = ch;
				
				root = $( '<div class="editor-wrapper">' )
				.height( ch.height() )
				.width( ch.width() )
				.addClass( 'editor-wrapper-' + chType )
				.appendTo( ch.parent() );
				
				sc = new SplitContainer( root, chArr, chType );
				
				if( this.instances.length > 1 ) {
					var pContainer = this.activeInstance.splitContainer;
					var idx = this.activeInstance.splitIdx;
					pContainer.setChild( idx, sc );
				}
			}
			
			ace.require( "ace/ext/language_tools" );
			var i = ace.edit( el[0] );
			var resizeEditor = function() {
				i.resize();
			};
			
			if( sc ) {
				i.splitContainer = sc;
				i.splitIdx = chIdx;
				
				this.activeInstance.splitContainer = sc;
				this.activeInstance.splitIdx = 1 - chIdx;
				
				sc.root
				.on( 'h-resize', resizeEditor )
				.on( 'v-resize', resizeEditor );
				
				if( this.instances.length === 1 ) {
					var re = function() {
						_this.instances[0].resize();
					};
					sc.root
					.on( 'h-resize', re )
					.on( 'v-resize', re );
				}
			}
			
			i.el = el;
			this.setSession( session, i );
			
			this.changeListener( i );
			this.cursorTracking( i );
			this.clickListener( i );
			this.bindKeys( i );
			
			this.instances.push( i );
			
			i.on( 'focus', function() {
				_this.focus( i );
			});
			
			return i;
		},
		
		createSplitMenu: function() {
			var _this = this;
			var _splitOptionsMenu = $( '#split-options-menu' );
			
			this.initMenuHandler( $( '#split' ), _splitOptionsMenu );
			
			$( '#split-horizontally a' ).click( function( e ) {
				e.stopPropagation();
				_this.addInstance( _this.activeInstance.getSession(), 'bottom' );
				_splitOptionsMenu.hide();
			});
			
			$( '#split-vertically a' ).click( function( e ) {
				e.stopPropagation();
				_this.addInstance( _this.activeInstance.getSession(), 'right' );
				_splitOptionsMenu.hide();
			});
			
			$( '#merge-all a' ).click( function( e ) {
				e.stopPropagation();
				var s = _this.activeInstance.getSession();
				_this.exterminate();
				_this.addInstance( s );
				_splitOptionsMenu.hide();
			});
		},
		
		createModeMenu: function() {
			var _this = this;
			var _thisMenu = $( '#changemode-menu' );
			var modeColumns = new Array();
			var modeOptions = new Array();
			var maxOptionsColumn = 15;
			var firstOption = 0;
			
			this.initMenuHandler( $( '#current-mode' ), _thisMenu );
			
			var modes = Object.keys( ModeList.modesByName ).sort();
			
			$.each( modes, function( i ) {
				modeOptions.push( '<li><a>' + modes[i] + '</a></li>' );
			});
			
			var html = '<table><tr>';
			while( true ) {
				html += '<td><ul>';
				if( ( modeOptions.length - firstOption ) < maxOptionsColumn ) {
					max = modeOptions.length;
				} else {
					max = firstOption + maxOptionsColumn;
				}
				var currentcolumn = modeOptions.slice( firstOption, max );
				for( var option in currentcolumn ) {
					html += currentcolumn[option];
				}
				html += '</ul></td>';
				firstOption = firstOption + maxOptionsColumn;
				if( firstOption >= modeOptions.length ) {
					break;
				}
			}
			
			html += '</tr></table>';
			_thisMenu.html( html );
			
			$( '#changemode-menu a' ).click( function( e ) {
				e.stopPropagation();
				var newMode = "ace/mode/" + $( e.currentTarget ).text();
				var actSession = _this.activeInstance.getSession();
				
				// handle async mode change
				var fn = function() {
					_this.setModeDisplay( actSession );
					actSession.removeListener( 'changeMode', fn );
				};
				actSession.on( "changeMode", fn );
				
				actSession.setMode( newMode );
				_thisMenu.hide();
				
			});
		},
		
		initMenuHandler: function( button, menu ) {
			var _this = this;
			var thisButton = button;
			var thisMenu = menu;
			
			thisMenu.appendTo( $( 'body' ) );
			
			thisButton.click( function( e ) {
				var wh = $( window ).height();
				
				e.stopPropagation();
				
				// close other menus
				_this.closeMenus( thisMenu );
				
				thisMenu.css( {
					// display: 'block',
					bottom: ( ( wh - $( this ).offset().top ) + 8 ) + 'px',
					left: ( $( this ).offset().left - 13 ) + 'px'
				});
				
				thisMenu.slideToggle( 'fast' );
				
				// handle click-out autoclosing
				var fn = function() {
					thisMenu.hide();
					$( window ).off( 'click', fn );
				};
				$( window ).on( 'click', fn );
			});
		},
		
		closeMenus: function( exclude ) {
			var menuId = exclude.attr( "id" );
			if( menuId != 'split-options-menu' ) $( '#split-options-menu' ).hide();
			if( menuId != 'changemode-menu' ) $( '#changemode-menu' ).hide();
		},
		
		setModeDisplay: function( session ) {
			var currMode = session.getMode().$id;
			if( currMode ) {
				currMode = currMode.substring( currMode.lastIndexOf( '/' ) + 1 );
				$( '#current-mode' ).html( currMode );
			} else {
				$( '#current-mode' ).html( 'text' );
			}
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Remove all Editor instances and clean up the DOM
		//
		//////////////////////////////////////////////////////////////////
		
		exterminate: function() {
			$( '.editor' ).remove();
			$( '.editor-wrapper' ).remove();
			$( '#editor-region' ).append( $( '<div>' ).attr( 'id', 'editor' ) );
			$( '#current-file' ).html( '' );
			$( '#current-mode' ).html( '' );
			this.instances = [];
			this.activeInstance = null;
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Detach EditSession session from all Editor instances replacing
		// them with replacementSession
		//
		//////////////////////////////////////////////////////////////////
		
		removeSession: function( session, replacementSession ) {
			for( var k = 0; k < this.instances.length; k++ ) {
				if( this.instances[k].getSession().path === session.path ) {
					this.instances[k].setSession( replacementSession );
				}
			}
			if( $( '#current-file' ).text() === session.path ) {
				$( '#current-file' ).text( replacementSession.path );
			}
			
			this.setModeDisplay( replacementSession );
		},
		
		isOpen: function( session ) {
			for( var k = 0; k < this.instances.length; k++ ) {
				if( this.instances[k].getSession().path === session.path ) {
					return true;
				}
			}
			return false;
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Convenience function to iterate over Editor instances
		//
		// Parameters:
		//   fn - {Function} callback called with each member as an argument
		//
		/////////////////////////////////////////////////////////////////
		
		forEach: function( fn ) {
			for( var k = 0; k < this.instances.length; k++ ) {
				fn.call( this, this.instances[k] );
			}
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Get the currently active Editor instance
		//
		// In a multi-pane setup this would correspond to the
		// editor pane user is currently working on.
		//
		/////////////////////////////////////////////////////////////////
		
		getActive: function() {
			return this.activeInstance;
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Set an editor instance as active
		//
		// Parameters:
		//   i - {Editor}
		//
		/////////////////////////////////////////////////////////////////
		
		setActive: function( i ) {
			if( !i ) return;
			this.activeInstance = i;
			$( '#current-file' ).text( i.getSession().path );
			this.setModeDisplay( i.getSession() );
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Change the EditSession of Editor instance
		//
		// Parameters:
		//   session - {EditSession}
		//   i - {Editor}
		//
		/////////////////////////////////////////////////////////////////
		
		setSession: function( session, i ) {
			
			i = i || this.getActive();
			if( !this.isOpen( session ) ) {
				
				if( !i ) {
					
					i = this.addInstance( session );
				} else {
					
					i.setSession( session );
				}
			} else {
				// Proxy session is required because scroll-position and
				// cursor position etc. are shared among sessions.
				
				var proxySession = new EditSession( session.getDocument(),
					session.getMode() );
				proxySession.setUndoManager( new UndoManager() );
				proxySession.path = session.path;
				proxySession.listThumb = session.listThumb;
				proxySession.tabThumb = session.tabThumb;
				if( !i ) {
					i = this.addInstance( proxySession );
				} else {
					i.setSession( proxySession );
				}
			}
			this.applySettings( i );
			this.cursorTracking( i );
			this.setActive( i );
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Select file mode by extension case insensitive
		//
		// Parameters:
		// e - {String} File extension
		//
		/////////////////////////////////////////////////////////////////
		
		selectMode: function( e ) {
			if( typeof( e ) != 'string' ) {
				return 'text';
			}
			e = e.toLowerCase();
			return ( ModeList.getModeForPath( e ) );
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Add an text mode for an extension
		//
		// Parameters:
		// extension - {String} File Extension
		// mode - {String} TextMode for this extension
		//
		/////////////////////////////////////////////////////////////////
		
		addFileExtensionTextMode: function( extension, mode ) {
			if( typeof( extension ) != 'string' || typeof( mode ) != 'string' ) {
				if( console ) {
					console.warn( 'wrong usage of addFileExtensionTextMode, both parameters need to be string' );
				}
				return;
			}
			mode = mode.toLowerCase();
			this.fileExtensionTextMode[extension] = mode;
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// clear all extension-text mode joins
		//
		/////////////////////////////////////////////////////////////////
		
		clearFileExtensionTextMode: function() {
			this.fileExtensionTextMode = {};
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Set the editor mode
		//
		// Parameters:
		//   m - {TextMode} mode
		//   i - {Editor} Editor (Defaults to active editor)
		//
		/////////////////////////////////////////////////////////////////
		
		setMode: function( m, i ) {
			i = i || this.getActive();
			
			// Check if mode is already loaded
			if( !editorModes[m] ) {
				
				// Load the Mode
				var modeFile = 'components/editor/ace-editor/mode-' + m + '.js';
				$.loadScript( modeFile, function() {
					
					// Mark the mode as loaded
					editorModes[m] = true;
					var EditorMode = ace.require( 'ace/mode/' + m ).Mode;
					i.getSession().setMode( new EditorMode() );
				}, true );
			} else {
				
				var EditorMode = ace.require( 'ace/mode/' + m ).Mode;
				i.getSession().setMode( new EditorMode() );
				
			}
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Set the editor theme
		//
		// Parameters:
		//   t - {String} theme eg. twilight, cobalt etc.
		//   i - {Editor} Editor instance (If omitted, Defaults to all editors)
		//
		// For a list of themes supported by Ace - refer :
		//   https://github.com/ajaxorg/ace/tree/master/lib/ace/theme
		//
		// TODO: Provide support for custom themes
		//
		/////////////////////////////////////////////////////////////////
		
		setTheme: function( t, i ) {
			if( i ) {
				// If a specific instance is specified, change the theme for
				// this instance
				i.setTheme( 'ace/theme/' + t );
			} else {
				// Change the theme for the existing editor instances
				// and make it the default for new instances
				this.settings.theme = t;
				for( var k = 0; k < this.instances.length; k++ ) {
					this.instances[k].setTheme( 'ace/theme/' + t );
				}
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.theme', t );
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Set contents of the editor
		//
		// Parameters:
		//   c - {String} content
		//   i - {Editor} (Defaults to active editor)
		//
		/////////////////////////////////////////////////////////////////
		
		setContent: function( c, i ) {
			i = i || this.getActive();
			i.getSession().setValue( c );
		},
		
		/////////////////////////////////////////////////////////////////
		//
		// Set Font Size
		//
		// Set the font for all Editor instances and remember
		// the value for Editor instances to be created in
		// future
		//
		// Parameters:
		//   s - {Number} font size
		//   i - {Editor} Editor instance  (If omitted, Defaults to all editors)
		//
		/////////////////////////////////////////////////////////////////
		
		setFontSize: function( s, i ) {
			if( i ) {
				i.setFontSize( s );
			} else {
				this.settings.fontSize = s;
				this.forEach( function( i ) {
					i.setFontSize( s );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.fontSize', s );
		},
		
		
		/////////////////////////////////////////////////////////////////
		//
		// Enable/disable Highlighting of active line
		//
		// Parameters:
		//   h - {Boolean}
		//   i - {Editor} Editor instance ( If left out, setting is
		//                    applied to all editors )
		//
		/////////////////////////////////////////////////////////////////
		
		setHighlightLine: function( h, i ) {
			if( i ) {
				i.setHighlightActiveLine( h );
			} else {
				this.settings.highlightLine = h;
				this.forEach( function( i ) {
					i.setHighlightActiveLine( h );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.highlightLine', h );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Show/Hide print margin indicator
		//
		// Parameters:
		//   p - {Number} print margin column
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setPrintMargin: function( p, i ) {
			if( i ) {
				i.setShowPrintMargin( p );
			} else {
				this.settings.printMargin = p;
				this.forEach( function( i ) {
					i.setShowPrintMargin( p );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.printMargin', p );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Set print margin column
		//
		// Parameters:
		//   p - {Number} print margin column
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setPrintMarginColumn: function( p, i ) {
			if( i ) {
				i.setPrintMarginColumn( p );
			} else {
				this.settings.printMarginColumn = p;
				this.forEach( function( i ) {
					i.setPrintMarginColumn( p );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.printMarginColumn', p );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Show/Hide indent guides
		//
		// Parameters:
		//   g - {Boolean}
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setIndentGuides: function( g, i ) {
			if( i ) {
				i.setDisplayIndentGuides( g );
			} else {
				this.settings.indentGuides = g;
				this.forEach( function( i ) {
					i.setDisplayIndentGuides( g );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.indentGuides', g );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Enable/Disable Code Folding
		//
		// Parameters:
		//   f - {Boolean}
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setCodeFolding: function( f, i ) {
			if( i ) {
				i.setFoldStyle( f );
			} else {
				this.forEach( function( i ) {
					i.setFoldStyle( f );
				});
			}
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Enable/Disable Line Wrapping
		//
		// Parameters:
		//   w - {Boolean}
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setWrapMode: function( w, i ) {
			if( i ) {
				i.getSession().setUseWrapMode( w );
			} else {
				this.forEach( function( i ) {
					i.getSession().setUseWrapMode( w );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.wrapMode', w );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Set last position of modal to be saved
		//
		// Parameters:
		//   t - {Boolean} (false for Automatic Position, true for Last Position)
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setPersistentModal: function( t, i ) {
			this.settings.persistentModal = t;
			//Database
			//codiad.settings.update_option( 'codiad.editor.persistentModal', t );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Set trigger for opening the right sidebar
		//
		// Parameters:
		//   t - {Boolean} (false for Hover, true for Click)
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setRightSidebarTrigger: function( t, i ) {
			this.settings.rightSidebarTrigger = t;
			//Database
			//codiad.settings.update_option( 'codiad.editor.rightSidebarTrigger', t );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Set trigger for clicking on the filemanager
		//
		// Parameters:
		//   t - {Boolean} (false for Hover, true for Click)
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setFileManagerTrigger: function( t, i ) {
			this.settings.fileManagerTrigger = t;
			//Database
			//codiad.settings.update_option( 'codiad.editor.fileManagerTrigger', t );
			codiad.project.loadSide();
		},
		
		
		//////////////////////////////////////////////////////////////////
		//
		// set Tab Size
		//
		// Parameters:
		//   s - size
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setTabSize: function( s, i ) {
			if( i ) {
				i.getSession().setTabSize( parseInt( s ) );
			} else {
				this.forEach( function( i ) {
					i.getSession().setTabSize( parseInt( s ) );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.tabSize', s );
			
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Enable or disable Soft Tabs
		//
		// Parameters:
		//   t - true / false
		//   i - {Editor}  (If omitted, Defaults to all editors)
		//
		//////////////////////////////////////////////////////////////////
		
		setSoftTabs: function( t, i ) {
			if( i ) {
				i.getSession().setUseSoftTabs( t );
			} else {
				this.forEach( function( i ) {
					i.getSession().setUseSoftTabs( t );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.softTabs', t );
			
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Get content from editor
		//
		// Parameters:
		//   i - {Editor} (Defaults to active editor)
		//
		//////////////////////////////////////////////////////////////////
		
		getContent: function( i ) {
			i = i || this.getActive();
			if( !i ) return;
			var content = i.getSession().getValue();
			if( !content ) {
				content = ' ';
			} // Pass something through
			return content;
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Resize the editor - Trigger the editor to readjust its layout
		// esp if the container has been resized manually.
		//
		// Parameters:
		//   i - {Editor} (Defaults to active editor)
		//
		//////////////////////////////////////////////////////////////////
		
		resize: function( i ) {
			i = i || this.getActive();
			if( !i ) return;
			i.resize();
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Mark the instance as changed (in the user interface)
		// upon change in the document content.
		//
		// Parameters:
		//   i - {Editor}
		//
		//////////////////////////////////////////////////////////////////
		
		changeListener: function( i ) {
			
			var _this = this;
			i.on( 'change', function() {
				
				codiad.active.markChanged( _this.getActive().getSession().path );
				codiad.active.savePosition();
			});
		},
		
		clickListener: function( i ) {
			
			var _this = this;
			i.on( 'click', function() {
				
				codiad.active.savePosition();
			});
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Get Selected Text
		//
		// Parameters:
		//   i - {Editor} (Defaults to active editor)
		//
		//////////////////////////////////////////////////////////////////
		
		getSelectedText: function( i ) {
			i = i || this.getActive();
			if( !i ) return;
			return i.getCopyText();
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Insert text
		//
		// Parameters:
		//   val - {String} Text to be inserted
		//   i - {Editor} (Defaults to active editor)
		//
		//////////////////////////////////////////////////////////////////
		
		insertText: function( val, i ) {
			i = i || this.getActive();
			if( !i ) return;
			i.insert( val );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Move the cursor to a particular line
		//
		// Parameters:
		//   line - {Number} Line number
		//   i - {Editor} Editor instance
		//
		//////////////////////////////////////////////////////////////////
		
		gotoLine: function( line, i ) {
			i = i || this.getActive();
			if( !i ) return;
			i.gotoLine( line, 0, true );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Focus an editor
		//
		// Parameters:
		//   i - {Editor} Editor instance (Defaults to current editor)
		//
		//////////////////////////////////////////////////////////////////
		
		focus: function( i ) {
			i = i || this.getActive();
			this.setActive( i );
			if( !i ) return;
			i.focus();
			codiad.active.focus( i.getSession().path );
			this.cursorTracking( i );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Setup Cursor Tracking
		//
		// Parameters:
		//   i - {Editor} (Defaults to active editor)
		//
		//////////////////////////////////////////////////////////////////
		
		cursorTracking: function( i ) {
			
			i = i || this.getActive();
			if( !i ) return;
			
			/**
			 * Update the cursor position now so that when a new file opens,
			 * we do not have the old cursor data.
			 */
			
			$( '#cursor-position' )
			.html( i18n( 'Ln' ) + ': ' +
				( i.getCursorPosition().row + 1 ) +
				' &middot; ' + i18n( 'Col' ) + ': ' +
				i.getCursorPosition().column
			);
			
			//Register the changecursor function so updates continue
			i.selection.on( "changeCursor", function( e ) {
				
				codiad.active.savePosition();
				$( '#cursor-position' )
				.html( i18n( 'Ln' ) + ': ' +
					( i.getCursorPosition().row + 1 ) +
					' &middot; ' + i18n( 'Col' ) + ': ' +
					i.getCursorPosition().column
				);
			});
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Setup Key bindings
		//
		// Parameters:
		//   i - {Editor}
		//
		//////////////////////////////////////////////////////////////////
		
		bindKeys: function( i ) {
			
			//Add key bindings to editor so we overwrite any already Setup
			//by the ace editor.
			var _this = this;
			
			codiad.keybindings.bindings.forEach( function( m, j, a ) {
				
				i.commands.addCommand( m );
			});
		},
		
		open_goto: function() {
			
			if( this.getActive() ) {
				
				codiad.modal.load( 400, 'components/editor/dialog.php?action=line' );
				codiad.modal.hideOverlay();
			} else {
				codiad.message.error( 'No Open Files' );
			}
		},
		
		goto_line: function() {
			
			let line = $( '#modal input[name="goto_line"]' ).val();
			this.gotoLine( line );
			codiad.modal.unload();
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Present the Search (Find + Replace) dialog box
		//
		// Parameters:
		//   type - {String} Optional, defaults to find. Provide 'replace' for replace dialog.
		//
		//////////////////////////////////////////////////////////////////
		
		openSearch: function( type ) {
			
			if( this.getActive() ) {
				
				let selected = codiad.active.getSelectedText();
				
				codiad.modal.load(
					400,
					'components/editor/dialog.php?action=search&type=' + type,
					{},
				)
				.then( function( c ) {
						
						let input = c.find( 'input:first' );
						let textarea = c.find( 'textarea:first' );
						
						if( input.css( 'display' ) !== 'none' ) {
							
							input.val( selected )
							input.focus();
						} else if( textarea.css( 'display' ) !== 'none' ) {
							
							textarea.val( selected )
							textarea.focus();
						}
						console.log( input, textarea );
						codiad.modal.hideOverlay();
					}
				);
			}
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Perform Search (Find + Replace) operation
		//
		// Parameters:
		//   action - {String} find | replace | replaceAll
		//   i - {Editor} Defaults to active Editor instance
		//
		//////////////////////////////////////////////////////////////////
		
		search: function( action, i ) {
			i = i || this.getActive();
			if( !i ) return;
			if( this.multi_line ) {
				
				var find = $( '#modal textarea[name="find"]' )
				.val();
				var replace = $( '#modal textarea[name="replace"]' )
				.val();
			} else {
				
				var find = $( '#modal input[name="find"]' )
				.val();
				var replace = $( '#modal input[name="replace"]' )
				.val();
			}
			
			console.log( action, i, find, replace );
			
			switch ( action ) {
				case 'find':
					
					i.find( find, {
						backwards: false,
						wrap: true,
						caseSensitive: false,
						wholeWord: false,
						regExp: false
					});
					
					break;
					
				case 'replace':
					
					i.find( find, {
						backwards: false,
						wrap: true,
						caseSensitive: false,
						wholeWord: false,
						regExp: false
					});
					i.replace( replace );
					
					break;
					
				case 'replaceAll':
					
					i.find( find, {
						backwards: false,
						wrap: true,
						caseSensitive: false,
						wholeWord: false,
						regExp: false
					});
					i.replaceAll( replace );
					
					break;
			}
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Enable editor
		//
		// Parameters:
		//   i - {Editor} (Defaults to active editor)
		//
		//////////////////////////////////////////////////////////////////
		
		enableEditing: function( i ) {
			i = i || this.getActive();
			if( !i ) return;
			i.textInput.setReadOnly( false );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Disable editor
		//
		// Parameters:
		//   i - {Editor} (Defaults to active editor)
		//
		//////////////////////////////////////////////////////////////////
		
		disableEditing: function( i ) {
			i = i || this.getActive();
			if( !i ) return;
			i.textInput.setReadOnly( true );
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Set Overscroll
		//
		// Parameters:
		//   i - {Editor} (Defaults to active editor)
		//
		//////////////////////////////////////////////////////////////////
		
		setOverScroll: function( s, i ) {
			
			if( i ) {
				i.setOption( "scrollPastEnd", s );
			} else {
				this.settings.overScroll = s;
				this.forEach( function( i ) {
					i.setOption( "scrollPastEnd", s );
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.overScroll', s );
		},
		
		setLiveAutocomplete: function( s, i ) {
			
			if( i ) {
				i.setOptions( {
					enableLiveAutocompletion: s
				});
			} else {
				this.settings.autocomplete = s;
				this.forEach( function( i ) {
					i.setOptions( {
						enableLiveAutocompletion: s
					});
				});
			}
			//Database
			//codiad.settings.update_option( 'codiad.editor.autocomplete', s );
		},
		
		toggleMultiLine: function( e ) {
			
			if( e.innerText === "Multi Line" ) {
				
				this.multi_line = true;
				e.innerText = "Single Line";
				$( 'input[name="find"]' ).hide();
				$( 'textarea[name="find"]' ).show();
				$( 'textarea[name="find"]' ).val( $( 'input[name="find"]' ).val() );
				
				$( 'input[name="replace"]' ).hide();
				$( 'textarea[name="replace"]' ).show();
				$( 'textarea[name="replace"]' ).val( $( 'input[name="replace"]' ).val() );
			} else {
				
				this.multi_line = false;
				e.innerText = "Multi Line";
				$( 'input[name="find"]' ).show();
				$( 'textarea[name="find"]' ).hide();
				$( 'input[name="find"]' ).val( $( 'textarea[name="find"]' ).val() );
				
				$( 'input[name="replace"]' ).show();
				$( 'textarea[name="replace"]' ).hide();
				$( 'input[name="replace"]' ).val( $( 'textarea[name="replace"]' ).val() );
			}
		},
		
		paste: function() {
			
			navigator.clipboard.readText().then( text => {
				codiad.editor.getActive().insert( text )
			});
		},
		
		openSort: function() {
			
			let selected = codiad.active.getSelectedText();
			
			if( this.getActive() && selected != "" ) {
				
				codiad.modal.load(
					400,
					'components/editor/dialog.php?action=sort',
					{},
					function( c ) {
						
						let textarea = c.find( 'textarea:first' );
						
						textarea.val( selected )
						textarea.focus();
						codiad.modal.hideOverlay();
					}
				);
				codiad.modal.hideOverlay();
			} else {
				
				codiad.message.error( 'No text selected' );
			}
		},
		
		sort: function( eol ) {
			
			let text = $( '#modal textarea[name="sort"]' ).val();
			let array = text.split( eol );
			array = array.sort( codiad.editor.sort_a );
			let sorted = array.join( eol );
			
			console.log( text, eol, array, sorted );
			codiad.modal.unload();
			codiad.editor.getActive().insert( sorted );
			codiad.editor.getActive().focus();
		},
		
		sort_a: function( a, b ) {
			
			let pos = 0;
			let case_sensitive = $( '#modal input[name="case_sensitive"]' ).prop( 'checked' )
			
			if( !case_sensitive ) {
				
				a = a.toLowerCase();
				b = b.toLowerCase();
			}
			
			if( a < b ) {
				
				pos = -1;
			} else if( a > b ) {
				
				pos = 1;
			}
			
			return pos;
		}
	};
	
})( this, jQuery );
