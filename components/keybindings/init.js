/**
 * Copyright (c) Codiad & Kent Safranski (codiad.com), Isaac Brown ( telaaedifex.com ),
 * distributed as-is and without warranty under the MIT License. See
 * [root]/license.txt for more. This information must remain intact.
 */

(function( global, $ ) {
	
	var codiad = global.codiad;
	
	//////////////////////////////////////////////////////////////////////
	// CTRL Key Bind
	//////////////////////////////////////////////////////////////////////
	
	$.ctrl = function(key, callback, args) {
		
		$(document).keydown(function(e) {
			
			if ( !args ) args = [];
			if ( e.keyCode == key && ( e.ctrlKey || e.metaKey ) ) {
				
				if ( ! ( e.ctrlKey && e.altKey ) ) {
					
					callback.apply( this, args );
					return false;
				}
			}
		});
	};
	
	$(function() {
		
		codiad.keybindings.init();
	});
	
	//////////////////////////////////////////////////////////////////////
	// Bindings
	//////////////////////////////////////////////////////////////////////
	
	codiad.keybindings = {
		
		bindings: [
			{
				name: 'Find',
				bindKey: {
					win: 'Ctrl-F',
					mac: 'Command-F'
				},
				exec: function( e ) {
					
					codiad.editor.openSearch( 'find' );
				}
			},
			{
				name: 'Goto Line',
				bindKey: {
					win: 'Ctrl-L',
					mac: 'Command-L'
				},
				exec: function( e ) {
					
					codiad.editor.open_goto();
				}
			},
			{
				name: 'Move Down',
				bindKey: {
					win: 'Ctrl-down',
					mac: 'Command-up'
				},
				exec: function( e ) {
					codiad.active.move( 'down' );
				}
			},
			{
				name: 'Move Up',
				bindKey: {
					win: 'Ctrl-up',
					mac: 'Command-up'
				},
				exec: function( e ) {
					
					codiad.active.move( 'up' );
				}
			},
			{
				name: 'Replace',
				bindKey: {
					win: 'Ctrl-R',
					mac: 'Command-R'
				},
				exec: function( e ) {
					
					codiad.editor.openSearch( 'replace' );
				}
			}
		],
		
		init: function() {
			
			// Active List Next [CTRL+DOWN] //////////////////////////////
			$.ctrl( '40', function() {
				
				codiad.active.move('down');
			});
			
			// Active List Previous [CTRL+UP] ////////////////////////////
			$.ctrl( '38', function() {
				
				codiad.active.move('up');
			});
			
			// Autocomplete [CTRL+SPACE] /////////////////////////////////
			$.ctrl( '32', function() {
				
				codiad.autocomplete.suggest();
			});
			
			// Close Modals //////////////////////////////////////////////
			$( document ).keyup( function( e ) {
				
				if( e.keyCode == 27 ) {
					
					codiad.modal.unload();
				}
			});
			
			// Find [CTRL+F] /////////////////////////////////////////////
			$.ctrl( '70', function() {
				
				codiad.editor.openSearch( 'find' );
			});
			
			// Find [CTRL+L] /////////////////////////////////////////////
			$.ctrl( '76', function() {
				
				codiad.editor.open_goto();
			});
			
			// Open in browser [CTRL+O] //////////////////////////////////
			$.ctrl( '79', function() {
				
				codiad.active.openInBrowser();
			});
			
			// Replace [CTRL+R] //////////////////////////////////////////
			$.ctrl( '82', function() {
				
				codiad.editor.openSearch( 'replace' );
			});
			
			// Save [CTRL+S] /////////////////////////////////////////////
			$.ctrl( '83', function() {
				
				codiad.active.save();
			});
			
			// Search Files [CTRL+G] /////////////////////////////////////
			$.ctrl( '71', function() {
				
				if( codiad.finder ) {
					
					codiad.finder.expandFinder();
				}
			});
		}
	};
})(this, jQuery);
