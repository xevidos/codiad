/*
*  Copyright (c) Codiad, distributed
*  as-is and without warranty under the MIT License. See
*  [root]/license.txt for more. This information must remain intact.
*/

(function(global, $) {
	
	$(function() {
		
		codiad.settings.init();
	});
	
	codiad.settings = {
		
		controller: 'components/settings/controller.php',
		
		init: function() {
			
			var _this = this;
			
			/*
			*  Storage Event:
			*  Note: Event fires only if change was made in different window and not in this one
			*  Details: http://dev.w3.org/html5/webstorage/#dom-localstorage
			*  
			*  Workaround for Storage-Event:
			*/
			$( 'body' ).append( '<iframe src="components/settings/dialog.php?action=iframe"></iframe>' );
			
			//Load Settings
			this.load();
		},
		
		get_option: async function( option ) {
			
			let result;
			
			try {
				
				result = await $.ajax({
					
					url: this.controller + '?action=get_option',
					type: "POST",
					dataType: 'html',
					data: {
						option: option
					},
				});
				
				return result;
			} catch (error) {
				
				console.log(error);
				throw error;
			}
		},
		
		//////////////////////////////////////////////////////////////////
		// Save Settings
		//////////////////////////////////////////////////////////////////
		
		save: function( settings ) {
			
			var systemRegex = /^codiad/;
			var pluginRegex = /^codiad.plugin/;
			
			/* Notify listeners */
			amplify.publish( 'settings.save', {} );
			console.log( settings );
			$.post( this.controller + '?action=save', {settings: JSON.stringify( settings )}, function( data ) {
				
				parsed = codiad.jsend.parse( data );
			});
		},
		
		//////////////////////////////////////////////////////////////////
		// Load Settings
		//////////////////////////////////////////////////////////////////
		
		load: function() {
			
			amplify.publish( 'settings.loaded', {} );
			codiad.editor.getSettings();
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// Show Settings Dialog
		//
		//  Parameter
		//
		//  data_file - {String} - Location of settings file based on BASE_URL
		//
		//////////////////////////////////////////////////////////////////
		
		show: function( data_file ) {
			
			var _this = this;
			codiad.modal.load( 800, 'components/settings/dialog.php?action=settings' );
			codiad.modal.hideOverlay();
			codiad.modal.load_process.done( function() {
				
				if ( typeof( data_file ) == 'string' ) {
					
					codiad.settings._showTab( data_file );
				} else {
					
					_this._loadTabValues( 'components/settings/settings.editor.php' );
				}
				/* Notify listeners */
				amplify.publish( 'settings.dialog.show', {} );
			});
		},
		
		update_option: function( option, value ) {
			
			jQuery.ajax({
					
				url: this.controller + '?action=update_option',
				type: "POST",
				dataType: 'html',
				data: {
					option: option,
					value: value
				},
			});
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// {Private} Show Specific Tab
		//
		//  Parameter
		//
		//  data_file - {String} - Location of settings file based on BASE_URL
		//
		//////////////////////////////////////////////////////////////////
		
		_showTab: function( data_file ) {
			
			var _this = this;
			if ( typeof( data_file ) != 'string' ) {
				
				return false;
			}
			$( '.settings-view .config-menu .active' ).removeClass( 'active' );
			$( '.settings-view .config-menu li[data-file="' + data_file + '"]' ).addClass( 'active' );
			$( '.settings-view .panels .active' ).hide().removeClass( 'active' );
			//Load panel
			if( $( '.settings-view .panel[data-file="' + data_file + '"]' ).length === 0 ) {
				
				$( '.settings-view .panels' ).append( '<div class="panel active" data-file="' + data_file + '"></div>' );
				$( '.settings-view .panel[data-file="' + data_file + '"]' ).load( data_file, function() {
					
					//TODO Show and hide loading information
					/* Notify listeners */
					var name = $( '.settings-view .config-menu li[data-file="' + data_file + '"]' ).attr( 'data-name' );
					amplify.publish( 'settings.dialog.tab_loaded', name );
					_this._loadTabValues( data_file );
				});
			} else {
				
				$( '.settings-view .panel[data-file="' + data_file + '"]' ).show().addClass( 'active' );
			}
		},
		
		//////////////////////////////////////////////////////////////////
		//
		// {Private} Load Settings of Specific Tab
		//
		//  Parameter
		//
		//  data_file - {String} - Location of settings file based on BASE_URL
		//
		//////////////////////////////////////////////////////////////////
		_loadTabValues: function( data_file ) {
			
			//Load settings
			var key, value;
			$( '.settings-view .panel[data-file="' + data_file + '"] .setting').each( async function( i, item ) {
				
				key   = $( item ).attr( 'data-setting' );
				value = await codiad.settings.get_option( key );
				if ( value !== null ) {
					
					$( item ).val( value );
				}
			});
		}
	};
	
})(this, jQuery);