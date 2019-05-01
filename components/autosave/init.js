/*
 *  Copyright (c) Codiad, Kent Safranski (codiad.com), and Isaac Brown (telaaedifex.com), distributed
 *  as-is and without warranty under the MIT License. See
 *  [root]/license.txt for more. This information must remain intact.
 */

(function(global, $){
	
	// Define core
	var codiad = global.codiad,
	scripts = document.getElementsByTagName('script'),
	path = scripts[scripts.length-1].src.split('?')[0],
	curpath = path.split('/').slice(0, -1).join('/')+'/';
	
	// Instantiates plugin
	$( function() {
		
		amplify.subscribe( 'settings.save', async function() {
            
            let option = await codiad.settings.get_option( 'codiad.settings.autosave' );
            
            if( option != codiad.auto_save.settings.autosave ) {
                
                //codiad.auto_save.reload_interval();
                window.location.reload();
            }
		});
		
		codiad.auto_save.init();
	});
	
	codiad.auto_save = {
		
		// Allows relative `this.path` linkage
		auto_save_trigger: null,
		change: null,
		content: null,
		editor: null,
		invalid_states: [ "", " ", null, undefined ],
		path: curpath,
		saving: false,
		settings: {
			autosave: true,
			toggle: true,
		},
		verbose: false,
		
		init: async function() {
			
			let _this = codiad.auto_save;
			_this.settings.autosave = await codiad.settings.get_option( 'codiad.settings.autosave' );
			
			// Check if the auto save setting is true or false
			// Also check to see if the editor is any of the invalid states
			if( this.settings.autosave == false || this.settings.autosave == "false" ) {
				
				window.clearInterval( this.auto_save_trigger );
				
				if( codiad.auto_save.verbose ) {
					
					console.log( 'Auto save disabled' );
				}
				return;
			}
			
			$( window ).focus( function() {
			
				//Turn auto save on if the user comes back the tab.
				codiad.auto_save.settings.toggle = true;
				if( codiad.auto_save.verbose ) {
					
					console.log( 'Auto save resumed' );
				}
			});
		
			$( window ).blur( function() {
				
				//Turn auto save off if the user leaves the tab.
				codiad.auto_save.settings.toggle = false;
				if( codiad.auto_save.verbose ) {
					
					console.log( 'Auto save paused' );
				}
			});
			
			if( codiad.auto_save.verbose ) {
					
				console.log( 'Auto save Enabled' );
			}
			
			let content = codiad.editor.getContent();
			
			if( ! codiad.active.getPath() == null && ! codiad.auto_save.invalid_states.includes( content ) ) {
				
				this.content = content;
			}
			
			/* Subscribe to know when a file is being closed. */
			amplify.subscribe( 'active.onClose', function( path ) {
				
				let _this = codiad.auto_save;
				try {
					
					_this.editor.removeEventListener( "change", _this.change );
				} catch( e ) {
					
					/**
					 * If the listener is not currently on file and we
					 * try to close it, the program will throw an exception and
					 * stop you from closing the file
					 */
				}
			});
			
			/* Subscribe to know when a file become active. */
			amplify.subscribe( 'active.onFocus', function( path ) {
				
				let _this = codiad.auto_save;
				
				if( ! _this.editor == null && path == _this.editor.getSession().path ) {
					
					return;
				}
				
				_this.editor = codiad.editor.getActive();
				_this.content = codiad.editor.getContent();
				_this.change = _this.editor.addEventListener( "change", _this.auto_save );
			});
		},
		
		/**
		* 
		* This is where the core functionality goes, any call, references,
		* script-loads, etc...
		* 
		*/
		
		auto_save: function() {
			
			let _this = codiad.auto_save;
			_this.saving = true;
			let tabs = document.getElementsByClassName( "tab-item" );
			let path = codiad.active.getPath();
			let content = codiad.editor.getContent();
			
			if( _this.settings.toggle == false  || _this.settings.autosave == false || codiad.auto_save.invalid_states.includes( content ) ) {
				
				_this.saving = false;
				return;
			}
			
			if( path == null ) {
				
				_this.saving = false;
				return;
			}
			
			if( _this.verbose ) {
				
				console.log( content, _this.content );
			}
			
			if( content == _this.content ) {
				
				let session = codiad.active.sessions[path];
				if( typeof session != 'undefined' ) {
					
					session.untainted = content;
					session.serverMTime = session.serverMTime;
					if ( session.listThumb ) {
						
						session.listThumb.removeClass('changed');
					}
					
					if ( session.tabThumb ) {
						
						session.tabThumb.removeClass('changed');
					}
				}
				return;
			}
			
			/*
			
			this code caused issues even though it is the proper way to save something.
			Whenever in collaboration, the server constantly gave a wrong file version error.
			
			let path = codiad.active.getPath();
			codiad.active.save( path, false );
			_this.saving = false;
			
			*/
			
			_this.content = content;
			codiad.active.save;
			codiad.filemanager.saveFile( path, content, localStorage.removeItem( path ), false );
			let session = codiad.active.sessions[path];
			if( typeof session != 'undefined' ) {
				
				session.untainted = content;
				session.serverMTime = session.serverMTime;
				if ( session.listThumb ) {
					
					session.listThumb.removeClass('changed');
				}
				
				if ( session.tabThumb ) {
					
					session.tabThumb.removeClass('changed');
				}
			}
			_this.saving = false;
			
			setTimeout(function() {
				
				//Call the function again after one second so that if we missed the last change we resave the file.
				let _this = codiad.auto_save;
				_this.auto_save();
			}, 1000);
		},
		
		reload_interval: async function() {
			
			codiad.auto_save.settings.autosave = await codiad.settings.get_option( 'codiad.settings.autosave' );
			try {
				
				window.clearInterval( codiad.autosave.auto_save_trigger );
				window.clearInterval( this.auto_save_trigger );
			} catch( error ) {}
			
			if( codiad.auto_save.settings.autosave == true || codiad.auto_save.settings.autosave == "true" ) {
				
				codiad.auto_save.auto_save_trigger = setInterval( codiad.auto_save.auto_save, 256 );
			}
		}
	};
})( this, jQuery );